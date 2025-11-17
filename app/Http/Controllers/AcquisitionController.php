<?php

namespace App\Http\Controllers;

use App\Models\AssetsValueHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AcquisitionController extends Controller
{
    public function index()
    {
        return view('acquisition.acquisition');
    }
    public function dtGlobal(Request $request)
    {
        if (!$this->canReadAcquisition()) {
            return DataTables::of(collect())->toJson();
        }

        $user      = $request->user();
        $canDelete = $user && $user->hasAction('ACQUISITION', 'D');

        $pic       = $request->input('pic');
        $pajak     = $request->input('pajak');
        $capFrom   = $request->input('cap_from');
        $capTo     = $request->input('cap_to');
        $assetLike = $request->input('asset');

        // 1) Subquery: latest created_at per asset
        $latestPerAsset = DB::table('assets_value_history')
            ->select(
                'asset_uuid',
                DB::raw('MAX(created_at) AS max_created_at')
            )
            ->whereNull('deleted_at')
            ->groupBy('asset_uuid');

        // 2) Join main history with that subquery
        $q = DB::table('assets_value_history as h')
            ->joinSub($latestPerAsset, 'lh', function ($join) {
                $join->on('h.asset_uuid', '=', 'lh.asset_uuid')
                    ->on('h.created_at', '=', 'lh.max_created_at');
            })
            ->join('assets as a', 'a.uuid', '=', 'h.asset_uuid')
            ->select(
                'h.uuid',
                'h.acq_code',
                'h.asset_uuid',
                'h.before_payload',
                'h.after_payload',
                'h.note',
                'h.pic_request_uid',
                'h.created_at',
                'a.asset_code',
                'a.description as asset_name',
            )
            ->whereNull('h.deleted_at')
            ->orderByDesc('h.created_at'); // latest snapshot first

        // 3) Apply filters on the *latest* row of each asset
        if ($pic) {
            $q->where('h.pic_request_uid', $pic);
        }

        if ($assetLike) {
            $q->where(function ($w) use ($assetLike) {
                $w->where('a.asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('a.description', 'ilike', "%{$assetLike}%");
            });
        }

        if ($pajak !== null && $pajak !== '') {
            if ($pajak === '1') {
                $q->whereRaw("
                coalesce(
                    (h.after_payload::jsonb->>'is_pajak')::int,
                    (h.before_payload::jsonb->>'is_pajak')::int,
                    0
                ) = 1
            ");
            } elseif ($pajak === '0') {
                $q->whereRaw("
                coalesce(
                    (h.after_payload::jsonb->>'is_pajak')::int,
                    (h.before_payload::jsonb->>'is_pajak')::int,
                    0
                ) = 0
            ");
            }
        }

        if ($capFrom) {
            $q->whereRaw("
            to_date(
                coalesce(
                    h.after_payload::jsonb->>'capitalization_date',
                    h.before_payload::jsonb->>'capitalization_date'
                ),
                'YYYY-MM-DD'
            ) >= ?
        ", [$capFrom]);
        }

        if ($capTo) {
            $q->whereRaw("
            to_date(
                coalesce(
                    h.after_payload::jsonb->>'capitalization_date',
                    h.before_payload::jsonb->>'capitalization_date'
                ),
                'YYYY-MM-DD'
            ) <= ?
        ", [$capTo]);
        }

        return DataTables::of($q)
            ->addColumn('asset_label', fn($r) => "{$r->asset_code} - {$r->asset_name}")
            ->addColumn('quantity', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $val    = data_get($after, 'quantity', data_get($before, 'quantity', 0));
                return (float) $val;
            })
            ->addColumn('kode_uom', function ($r) {
                static $uomMap = null;

                if ($uomMap === null) {
                    $uomMap = DB::table('master_uom')
                        ->whereNull('deleted_at')
                        ->pluck('name', 'kode')
                        ->toArray();
                }

                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $kode   = data_get($after, 'kode_uom', data_get($before, 'kode_uom'));

                if (!$kode) {
                    return null;
                }

                $name = $uomMap[$kode] ?? null;
                return $name ? "{$kode} - {$name}" : $kode;
            })
            ->addColumn('price', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $val    = data_get($after, 'price', data_get($before, 'price', 0));
                return (float) $val;
            })
            ->addColumn('vat_in', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $val    = data_get($after, 'vat_in', data_get($before, 'vat_in', 0));
                return (float) $val;
            })
            ->addColumn('total', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $val    = data_get($after, 'total', data_get($before, 'total', 0));
                return (float) $val;
            })
            ->addColumn('actual_date', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                return data_get($after, 'actual_date', data_get($before, 'actual_date'));
            })
            ->addColumn('capitalization_date', function ($r) {
                $after  = json_decode($r->after_payload ?? '[]', true) ?: [];
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                return data_get($after, 'capitalization_date', data_get($before, 'capitalization_date'));
            })
            ->addColumn('created_at_fmt', function ($r) {
                return Carbon::parse($r->created_at)
                    ->timezone('Asia/Jakarta')
                    ->format('d M Y H:i');
            })
            ->addColumn('actions', function ($r) use ($canDelete) {
                $id      = e($r->uuid);
                $btns = '<div class="btn-group btn-group-sm">';
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-delete" data-id="' . $id . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }



    private function parseDate(?string $v): ?string
    {
        if (!$v) return null;
        $v = trim($v);
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        foreach ($formats as $f) {
            try {
                return Carbon::createFromFormat($f, $v)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
    public function storeGlobal(Request $request)
    {
        abort_unless($request->user()?->hasAction('ACQUISITION', 'C'), 403);
        $data = $request->validate([
            'asset_uuid'          => ['required', 'uuid', 'exists:assets,uuid'],
            'quantity'            => ['required', 'numeric', 'min:0'],
            'kode_uom'            => ['required', 'string', 'max:50'],
            'price'               => ['required', 'numeric', 'min:0'],
            'is_pajak'            => ['nullable', 'boolean'],
            'useful_life_month'   => ['nullable', 'numeric', 'min:0'],
            'actual_date'         => ['nullable', 'string'],
            'capitalization_date' => ['nullable', 'string'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'nilai_pajak'         => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->store($data['asset_uuid'], $request);
    }

    public function store(string $assetUuid, Request $request)
    {
        $data = $request->validate([
            'quantity'            => ['required', 'numeric', 'min:0'],
            'kode_uom'            => ['required', 'string', 'max:50'],
            'price'               => ['required', 'numeric', 'min:0'],
            'is_pajak'            => ['nullable', 'boolean'],
            'useful_life_month'   => ['nullable', 'numeric', 'min:0'],
            'actual_date'         => ['nullable', 'string'],
            'capitalization_date' => ['nullable', 'string'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'nilai_pajak'         => ['nullable', 'numeric', 'min:0'],
        ]);

        $existsUom = DB::table('master_uom')
            ->whereNull('deleted_at')
            ->where('kode', $data['kode_uom'])
            ->exists();
        abort_unless($existsUom, 422, 'Invalid UOM.');

        $tableValues = DB::getSchemaBuilder()->hasTable('assets_values') ? 'assets_values' : 'assets_value';

        $existing = DB::table($tableValues)->where('asset_uuid', $assetUuid)->first();

        $nilaiPajak = isset($data['nilai_pajak']) ? (float) $data['nilai_pajak'] : 0.0;
        $rate       = $nilaiPajak / 100.0;

        $qty   = (float) $data['quantity'];
        $price = (float) $data['price'];

        $existingIsPajak = $existing ? (bool) $existing->is_pajak : false;
        $isPajak = array_key_exists('is_pajak', $data)
            ? (bool) $data['is_pajak']
            : $existingIsPajak;

        $subtotal = $qty * $price;
        $vat      = $isPajak ? round($price * $rate, 2) : 0.0;
        $total    = round($price + $vat, 2);

        $values = [
            'quantity'          => (float) $data['quantity'],
            'kode_uom'          => $data['kode_uom'],
            'price'             => (float) $data['price'],
            'is_pajak'          => $isPajak ? 1 : 0,
            'vat_in'            => $vat,
            'total'             => $total,
            'useful_life_month' => $data['useful_life_month'] ?? null,
            'useful_life_year'  => isset($data['useful_life_month'])
                ? round(((float) $data['useful_life_month']) / 12, 2)
                : null,
            'actual_date'         => $this->parseDate($data['actual_date'] ?? null),
            'capitalization_date' => $this->parseDate($data['capitalization_date'] ?? null),
        ];

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::beginTransaction();
        try {
            $action = $existing ? 'update' : 'create';

            if ($existing) {
                DB::table($tableValues)
                    ->where('asset_uuid', $assetUuid)
                    ->update(array_merge($values, [
                        'updated_at' => now(),
                    ]));
            } else {
                DB::table($tableValues)->insert(array_merge($values, [
                    'asset_uuid' => $assetUuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            $now    = Carbon::now();
            $prefix = 'ACQ' . $now->format('ym');
            $last   = AssetsValueHistory::where('acq_code', 'like', $prefix . '%')
                ->orderBy('acq_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->acq_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Log history
            AssetsValueHistory::create([
                'uuid'       => (string) Str::uuid(),
                'asset_uuid' => $assetUuid,
                'before_payload' => $existing ? [
                    'quantity'           => (float) $existing->quantity,
                    'kode_uom'           => $existing->kode_uom,
                    'price'              => (float) $existing->price,
                    'is_pajak'           => $existing->is_pajak ?? null,
                    'vat_in'             => (float) $existing->vat_in,
                    'total'              => (float) $existing->total,
                    'useful_life_month'  => $existing->useful_life_month,
                    'useful_life_year'   => $existing->useful_life_year,
                    'actual_date'        => $existing->actual_date,
                    'capitalization_date' => $existing->capitalization_date,
                ] : null,
                'after_payload'   => $values,
                'pic_request_uid' => $uid,
                'note'            => $data['note'] ?? null,
                'acq_code'        => $code,
            ]);

            DB::commit();
            return response()->json(['ok' => true, 'action' => $action]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function destroy(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('ACQUISITION', 'D'), 403);

        $tf = AssetsValueHistory::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }

    public function dataByAsset(string $assetUuid, Request $request)
    {
        if (!$this->canReadAcquisition()) {
            return DataTables::of(collect())->toJson();
        }

        $user      = $request->user();
        $canDelete = $user && $user->hasAction('ACQUISITION', 'D');

        $latestByAcq = DB::table('assets_value_history')
            ->select(
                'asset_uuid',
                'acq_code',
                DB::raw('MAX(created_at) AS max_created_at')
            )
            ->whereNull('deleted_at')
            ->where('asset_uuid', $assetUuid)
            ->groupBy('asset_uuid', 'acq_code');

        $q = DB::table('assets_value_history as h')
            ->joinSub($latestByAcq, 'lh', function ($join) {
                $join->on('h.asset_uuid', '=', 'lh.asset_uuid')
                    ->on('h.acq_code', '=', 'lh.acq_code')
                    ->on('h.created_at', '=', 'lh.max_created_at');
            })
            ->where('h.asset_uuid', $assetUuid)
            ->whereNull('h.deleted_at')
            ->orderByDesc('h.created_at')
            ->select(
                'h.before_payload',
                'h.after_payload',
                'h.note',
                'h.pic_request_uid',
                'h.created_at',
                'h.acq_code',
                'h.uuid'
            );

        $money = fn($v) => is_null($v) ? '—' : number_format((float) $v, 2);
        $num   = fn($v) => is_null($v) ? '—' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
        $dstr  = fn($v) => $v ? e(\Illuminate\Support\Str::of($v)->limit(32)) : '—';

        return datatables()->of($q)
            ->addColumn('detail', function ($r) use ($money, $num, $dstr) {
                $before = json_decode($r->before_payload ?? '[]', true) ?: [];
                $after  = json_decode($r->after_payload  ?? '[]', true) ?: [];

                $row = function ($label, $b, $a, $kind = 'text') use ($money, $num, $dstr) {
                    $fmt = fn($x) => match ($kind) {
                        'money' => $money($x),
                        'num'   => $num($x),
                        default => $dstr($x),
                    };
                    $fb = $fmt($b);
                    $fa = $fmt($a);
                    if ($fb === $fa) {
                        return "<div><span class='fw-semibold'>{$label}:</span> {$fa}</div>";
                    }
                    return "<div><span class='fw-semibold'>{$label}:</span> {$fb} <span class='mx-1'>→</span> <span class='fw-bold'>{$fa}</span></div>";
                };

                $html  = "<div class='small'>";
                $html .= $row('Quantity', data_get($before, 'quantity'), data_get($after, 'quantity'), 'num');
                $html .= $row('UOM',      data_get($before, 'kode_uom'), data_get($after, 'kode_uom'));
                $html .= $row('Price',    data_get($before, 'price'),    data_get($after, 'price'), 'money');
                $html .= $row('VAT In',   data_get($before, 'vat_in'),   data_get($after, 'vat_in'), 'money');
                $html .= $row('Total',    data_get($before, 'total'),    data_get($after, 'total'), 'money');

                $html .= $row('Useful Life (mo)', data_get($before, 'useful_life_month'), data_get($after, 'useful_life_month'), 'num');
                $html .= $row('Useful Life (yr)', data_get($before, 'useful_life_year'),  data_get($after, 'useful_life_year'),  'num');

                $html .= $row('Actual Date',    data_get($before, 'actual_date'),        data_get($after, 'actual_date'));
                $html .= $row('Capitalization', data_get($before, 'capitalization_date'), data_get($after, 'capitalization_date'));

                $html .= "</div>";
                return $html;
            })
            ->addColumn('note', fn($r) => $r->note)
            ->addColumn('pic_request_uid', fn($r) => $r->pic_request_uid)
            ->addColumn('created_at', fn($r) => $r->created_at)
            ->addColumn('acq_code', fn($r) => $r->acq_code)
            ->addColumn('actions', function ($r) use ($canDelete) {
                $id   = e($r->uuid);
                $btns = '<div class="btn-group btn-group-sm">';
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-delete" data-id="' . $id . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['detail', 'actions'])
            ->toJson();
    }


    public function latest(string $assetUuid)
    {
        $tableValues = DB::getSchemaBuilder()->hasTable('assets_values') ? 'assets_values' : 'assets_value';
        $v = DB::table($tableValues)->where('asset_uuid', $assetUuid)->first();
        return response()->json([
            'exists' => (bool)$v,
            'value'  => $v,
        ]);
    }
    protected function canReadAcquisition(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }
        return $user->hasAction('ACQUISITION', 'R');
    }
}
