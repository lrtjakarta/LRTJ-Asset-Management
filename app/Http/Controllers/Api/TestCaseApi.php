<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;

class TestCaseApi extends Controller
{
    // Redis keys
    private const KEY_ALL         = 'dummyjson:products:all';
    private const KEY_META        = 'dummyjson:products:meta';
    private const REDIS_HITS_KEY  = 'dummyjson:stats:hits';
    private const REDIS_MISS_KEY  = 'dummyjson:stats:misses';

    /**
     * #1 Just get from URL and show as-is.
     * Defaults to JSONPlaceholder /photos (≈5,000 items).
     * Supports pass-through params like _limit, _page, albumId.
     *
     * GET /api/products/raw?url=&_limit=&_page=&albumId=
     */
    public function raw(Request $request)
    {
        // Default to a biggish public dataset (JSONPlaceholder photos)
        $url = $request->query('url', 'https://jsonplaceholder.typicode.com/photos');

        // Pass through common params if provided
        $query = $request->only(['_limit', '_page', 'albumId', 'limit', 'skip']);

        $resp = Http::timeout(15)->retry(3, 200)->get($url, $query);
        if (!$resp->successful()) {
            return response()->json(['message' => 'Upstream error'], 502);
        }
        return response()->json($resp->json());
    }

    /**
     * #2 Use Redis only (no pagination).
     * GET /api/products/cache
     */
    public function cacheOnly()
    {
        [$all, $meta] = $this->readAll();
        return response()->json([
            'meta' => $meta + ['count' => count($all), 'source' => $meta['source'] ?? 'redis'],
            'data' => $all,
        ]);
    }

    /**
     * #3 Pagination only (no Redis). Fetch upstream then paginate in-memory.
     * Works with /photos too.
     *
     * GET /api/products/page-only?url=&page=&per_page=&_limit=&_page=&albumId=
     */
    public function pageOnly(Request $request)
    {
        $url = $request->query('url', 'https://jsonplaceholder.typicode.com/photos');

        // Try to use upstream paging if the caller provides it; otherwise fetch all and paginate locally.
        $passthrough = $request->only(['_limit','_page','albumId']);
        $resp = Http::timeout(20)->retry(3, 300)->get($url, $passthrough);

        if (!$resp->successful()) {
            return response()->json(['message' => 'Upstream error'], 502);
        }

        $payload  = $resp->json();
        $products = is_array($payload) ? $payload : (array) data_get($payload, 'products', []);

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));

        // If upstream already applied _limit/_page, just return as-is with meta guessed from size
        if ($request->filled('_limit') || $request->filled('_page')) {
            $slice = $products;
            $total = is_array($payload) ? 5000 /* heuristic for /photos */ : count($products);
        } else {
            $total  = count($products);
            $offset = max(0, ($page - 1) * $perPage);
            $slice  = array_slice($products, $offset, $perPage);
        }

        return response()->json([
            'meta' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / max(1, $perPage)),
                'source'      => 'upstream',
            ],
            'data' => array_values($slice),
        ]);
    }

    /**
     * #4 Pagination + Redis (main listing) with optional search/sort/filter.
     * Works for either your synthetic dataset or cached /photos.
     *
     * GET /api/products?page=&per_page=&q=&category=&sort_by=&sort_dir=
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'page'     => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1','max:200'],
            'q'        => ['nullable','string','max:200'],
            'category' => ['nullable','string','max:100'],
            'sort_by'  => ['nullable', Rule::in(['id','price','rating','title'])],
            'sort_dir' => ['nullable', Rule::in(['asc','desc'])],
        ]);

        $page    = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 50);
        $q       = (string) ($data['q'] ?? '');
        $cat     = (string) ($data['category'] ?? '');
        $sortBy  = (string) ($data['sort_by'] ?? 'id');
        $sortDir = (string) ($data['sort_dir'] ?? 'asc');

        [$all, $meta] = $this->readAll();

        // Optional filtering/search (fields may not exist for /photos; that's fine)
        if ($cat !== '') {
            $all = array_values(array_filter($all, fn($p) => ($p['category'] ?? '') === $cat));
        }
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $all = array_values(array_filter($all, function ($p) use ($needle) {
                $hay = mb_strtolower(
                    ($p['title'] ?? '') . ' ' .
                    ($p['brand'] ?? '') . ' ' .
                    ($p['description'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        // Sort (if field missing, it's treated as null which will group at one side)
        $all = Arr::sort($all, fn($p) => $p[$sortBy] ?? null);
        if ($sortDir === 'desc') $all = array_reverse($all);

        $total  = count($all);
        $offset = max(0, ($page - 1) * $perPage);
        $slice  = array_slice($all, $offset, $perPage);

        Redis::incr(self::REDIS_HITS_KEY);

        return response()->json([
            'meta' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / max(1, $perPage)),
                'sorted_by'   => $sortBy,
                'sorted_dir'  => $sortDir,
                'source'      => $meta['source'] ?? 'redis',
                'count'       => $meta['count'] ?? $total,
                'last_refreshed_at' => $meta['last_refreshed_at'] ?? null,
            ],
            'data' => array_values($slice),
        ]);
    }

    /** GET /api/products/{id} (from Redis) */
    public function show(int $id)
    {
        [$all] = $this->readAll();
        foreach ($all as $p) {
            if ((int) ($p['id'] ?? 0) === $id) {
                Redis::incr(self::REDIS_HITS_KEY);
                return response()->json($p);
            }
        }
        Redis::incr(self::REDIS_MISS_KEY);
        return response()->json(['message' => 'Not found'], 404);
    }

    /**
     * POST /api/products/refresh
     * Create BIG synthetic payload directly in Redis (e.g., 50k/100k+).
     * Body: { "count": 50000, "ttl": 86400 }
     */
    public function refresh(Request $request)
    {
        $count = max(1, (int) $request->integer('count', 50000));
        $ttl   = max(60, (int) $request->integer('ttl', 86400));

        $products = $this->makeProducts($count);
        $meta = [
            'source'            => 'synthetic',
            'count'             => $count,
            'last_refreshed_at' => now()->toIso8601String(),
        ];

        $store = Cache::store('redis');
        $store->put(self::KEY_ALL,  $products, $ttl);
        $store->put(self::KEY_META, $meta,     $ttl);

        return response()->json(['message' => 'refreshed', 'meta' => $meta], 201);
    }

    /**
     * POST /api/products/refresh-photos
     * Pull JSONPlaceholder /photos (≈5,000 items) and cache into Redis.
     */
    public function refreshFromJsonPlaceholder()
    {
        $resp = Http::timeout(30)->retry(3, 300)
            ->get('https://jsonplaceholder.typicode.com/photos');

        if (!$resp->successful()) {
            return response()->json(['message' => 'fetch failed'], 502);
        }

        $photos = $resp->json(); // array of { albumId, id, title, url, thumbnailUrl }

        $meta = [
            'source'            => 'jsonplaceholder/photos',
            'count'             => is_array($photos) ? count($photos) : 0,
            'last_refreshed_at' => now()->toIso8601String(),
        ];

        $store = Cache::store('redis');
        $store->put(self::KEY_ALL,  $photos, 86400);
        $store->put(self::KEY_META, $meta,   86400);

        return response()->json(['message' => 'refreshed', 'meta' => $meta], 201);
    }

    /** GET /api/products/stats */
    public function stats()
    {
        $hits = (int) (Redis::get(self::REDIS_HITS_KEY) ?? 0);
        $miss = (int) (Redis::get(self::REDIS_MISS_KEY) ?? 0);
        $meta = Cache::store('redis')->get(self::KEY_META, []);
        return response()->json(['hits' => $hits, 'misses' => $miss, 'cache' => $meta]);
    }

    /** ---- helpers ---- */
    private function readAll(): array
    {
        $store = Cache::store('redis');
        $all  = $store->get(self::KEY_ALL, []);
        $meta = $store->get(self::KEY_META, []);
        return [is_array($all) ? $all : [], is_array($meta) ? $meta : []];
    }

    /**
     * Generate a BIG synthetic dataset for stress tests
     * (fields align with your previous "products" shape).
     */
    private function makeProducts(int $count): array
    {
        $brands     = ['Acme','Globex','Umbrella','Soylent','Initech'];
        $categories = ['electronics','computers','home','outdoors','toys','beauty','fashion'];
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id'          => $i,
                'title'       => "Product $i",
                'description' => "Desc for product $i",
                'price'       => ($i % 500) + 1,
                'rating'      => ($i % 50) / 10.0,
                'brand'       => $brands[$i % count($brands)],
                'category'    => $categories[$i % count($categories)],
                'thumbnail'   => "https://example.com/img/$i.jpg",
                'images'      => [],
            ];
        }
        return $rows;
    }
}
