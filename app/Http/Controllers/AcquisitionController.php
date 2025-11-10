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
        $q = DB::table('assets_value_history as h')
            ->join('assets as a', 'a.uuid', '=', 'h.asset_uuid')
            ->select(
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
            ->orderByDesc('h.created_at');

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
        ]);

        $existsUom = DB::table('master_uom')
            ->whereNull('deleted_at')
            ->where('kode', $data['kode_uom'])
            ->exists();
        abort_unless($existsUom, 422, 'Invalid UOM.');

        $rate = (float) env('NILAI_PAJAK', 12) / 100;

        $qty       = (float) $data['quantity'];
        $price     = (float) $data['price'];
        $subtotal  = $qty * $price;
        $isPajak   = (bool) ($data['is_pajak'] ?? false);
        $vat       = $isPajak ? round($subtotal * $rate, 2) : 0.0;
        $total     = round($subtotal + $vat, 2);

        $values = [
            'quantity'          => (float) $data['quantity'],
            'kode_uom'          => $data['kode_uom'],
            'price'             => (float) $data['price'],
            'is_pajak'          => !empty($data['is_pajak']) ? 1 : 0,
            'vat_in'            => $vat,
            'total'             => $total,
            'useful_life_month' => $data['useful_life_month'] ?? null,
            'useful_life_year'  => isset($data['useful_life_month'])
                ? round(((float)$data['useful_life_month']) / 12, 2)
                : null,
            'actual_date'         => $this->parseDate($data['actual_date'] ?? null),
            'capitalization_date' => $this->parseDate($data['capitalization_date'] ?? null),
        ];

        $uid = auth()->user()?->username;
        abort_if(!$uid, 401, 'No session UID.');

        DB::beginTransaction();
        try {
            $tableValues = DB::getSchemaBuilder()->hasTable('assets_values') ? 'assets_values' : 'assets_value';

            $existing = DB::table($tableValues)->where('asset_uuid', $assetUuid)->first();
            $action   = $existing ? 'update' : 'create';

            if ($existing) {
                DB::table($tableValues)->where('asset_uuid', $assetUuid)->update(array_merge($values, [
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
            $last = AssetsValueHistory::where('acq_code', 'like', $prefix . '%')
                ->orderBy('acq_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->acq_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            AssetsValueHistory::create([
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'      => $assetUuid,
                'before_payload'  => $existing ? [
                    'quantity' => $existing->quantity,
                    'kode_uom' => $existing->kode_uom,
                    'price'    => $existing->price,
                    'is_pajak' => $existing->is_pajak ?? null,
                    'vat_in'   => $existing->vat_in,
                    'total'    => $existing->total,
                    'useful_life_month' => $existing->useful_life_month,
                    'useful_life_year'  => $existing->useful_life_year,
                ] : null,
                'after_payload'   => $values,
                'pic_request_uid' => $uid,
                'note'            => $data['note'] ?? null,
                'acq_code' => $code,
            ]);

            DB::commit();
            return response()->json(['ok' => true, 'action' => $action]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


    public function dataByAsset(string $assetUuid, Request $request)
    {
        $q = DB::table('assets_value_history as h')
            ->where('h.asset_uuid', $assetUuid)
            ->orderByDesc('h.created_at')
            ->select('h.before_payload', 'h.after_payload', 'h.note', 'h.pic_request_uid', 'h.created_at', 'h.acq_code');

        $money = fn($v) => is_null($v) ? '—' : number_format((float)$v, 2);
        $num   = fn($v) => is_null($v) ? '—' : rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.');
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

                $html .= $row('Actual Date',       data_get($before, 'actual_date'),       data_get($after, 'actual_date'));
                $html .= $row('Capitalization',    data_get($before, 'capitalization_date'), data_get($after, 'capitalization_date'));

                $aqty = (float) (data_get($after, 'quantity') ?? 0);
                $aprc = (float) (data_get($after, 'price') ?? 0);
                $avat = (float) (data_get($after, 'vat_in') ?? 0);
                $calc = $aqty > 0 || $aprc > 0 || $avat > 0;
                // if ($calc) {
                //     $calcStr = number_format($aqty, 2) . ' × ' . $money($aprc) . ' + ' . $money($avat) . ' = <span class="fw-bold">' . $money(($aqty * $aprc) + $avat) . '</span>';
                //     $html .= "<div class='mt-1 text-muted fst-italic'>{$calcStr}</div>";
                // }

                $html .= "</div>";
                return $html;
            })
            ->addColumn('note', fn($r) => $r->note)
            ->addColumn('pic_request_uid', fn($r) => $r->pic_request_uid)
            ->addColumn('created_at', fn($r) => $r->created_at)
            ->addColumn('acq_code', fn($r) => $r->acq_code)
            ->rawColumns(['detail'])
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
}
