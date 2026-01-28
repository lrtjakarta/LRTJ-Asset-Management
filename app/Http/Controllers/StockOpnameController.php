<?php

namespace App\Http\Controllers;

use App\Models\AssetProject;
use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\TemplateProcessor;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StockOpnameController extends Controller
{
    protected function canReadStockOpname()
    {
        $u = auth()->user();
        return $u && method_exists($u, 'hasAction') ? $u->hasAction('STOCK_OPN', 'R') : true;
    }

    protected function canCreateStockOpname()
    {
        $u = auth()->user();
        return $u && method_exists($u, 'hasAction') ? $u->hasAction('STOCK_OPN', 'C') : true;
    }

    public function index()
    {
        abort_unless($this->canReadStockOpname(), 403);
        return view('stock_opname.stock_opname');
    }
    public function datatable(Request $request)
    {
        if (!$this->canReadStockOpname()) {
            $draw = (int) $request->input('draw', 1);
            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim(data_get($request->input('search', []), 'value', ''));

        $source      = $request->input('source');
        $tfType      = $request->input('tf_type');
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');
        $assetLike   = $request->input('asset');
        $users       = $request->input('users');
        $projectUuid = $request->input('project_uuid');

        // ===== Transfer base =====
        $t = DB::table('assets_transfers as t')
            ->join('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->leftJoin('assets_assignment as aa', 'aa.asset_uuid', '=', 'a.uuid')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('master_user_code as uo', 'uo.kode', '=', 'aa.asset_owner')
            ->leftJoin('master_user_code as uu', 'uu.kode', '=', 'aa.asset_user')
            ->leftJoin('master_user_code as um', 'um.kode', '=', 'aa.asset_maintenance')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 't.project_uuid')
            ->selectRaw("
            t.uuid,
            t.asset_uuid,
            t.project_uuid,

            a.asset_code,
            a.description as asset_description,

            (COALESCE(a.kode_location,'') || ' - ' || COALESCE(ml.name,'')) as asset_location,
            (COALESCE(aa.asset_owner,'') || ' - ' || COALESCE(uo.department,'')) as owner,
            (COALESCE(aa.asset_user,'') || ' - ' || COALESCE(uu.department,'')) as \"user\",
            (COALESCE(aa.asset_maintenance,'') || ' - ' || COALESCE(um.department,'')) as maintenance,
            (COALESCE(a.kode_status,'') || ' - ' || COALESCE(ms.name,'')) as asset_status,

            COALESCE(p.name,'') as project_name,

            t.transfer_code       as code,
            'transfer'            as source_type,
            t.type                as tf_type,
            COALESCE(t.before->>'value','') as before_val,
            COALESCE(t.after->>'value','')  as after_val,

            t.pic_request_uid,
            t.pic_approve_uid,
            t.note,
            t.file_name,
            t.file_path,
            t.flow_file_name,
            t.flow_file_path,
            NULL::text            as ba_file_name,
            NULL::text            as ba_file_path,
            t.updated_at
        ")
            ->whereNull('t.deleted_at')
            ->whereNotNull('t.project_uuid')
            ->where('t.kode_status', 'ACC');

        // ===== Disposal base =====
        $d = DB::table('assets_disposals as d')
            ->join('assets as a', 'a.uuid', '=', 'd.asset_uuid')
            ->leftJoin('assets_assignment as aa', 'aa.asset_uuid', '=', 'a.uuid')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('master_user_code as uo', 'uo.kode', '=', 'aa.asset_owner')
            ->leftJoin('master_user_code as uu', 'uu.kode', '=', 'aa.asset_user')
            ->leftJoin('master_user_code as um', 'um.kode', '=', 'aa.asset_maintenance')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 'd.project_uuid')
            ->selectRaw("
            d.uuid,
            d.asset_uuid,
            d.project_uuid,

            a.asset_code,
            a.description as asset_description,

            (COALESCE(a.kode_location,'') || ' - ' || COALESCE(ml.name,'')) as asset_location,
            (COALESCE(aa.asset_owner,'') || ' - ' || COALESCE(uo.department,'')) as owner,
            (COALESCE(aa.asset_user,'') || ' - ' || COALESCE(uu.department,'')) as \"user\",
            (COALESCE(aa.asset_maintenance,'') || ' - ' || COALESCE(um.department,'')) as maintenance,
            (COALESCE(a.kode_status,'') || ' - ' || COALESCE(ms.name,'')) as asset_status,

            COALESCE(p.name,'') as project_name,

            d.disposal_code       as code,
            'disposal'            as source_type,
            NULL::text            as tf_type,
            COALESCE(d.before_status,'') as before_val,
            COALESCE('DIS','')          as after_val,

            d.pic_request_uid,
            d.pic_approve_uid,
            d.note,
            d.file_name,
            d.file_path,
            d.flow_file_name,
            d.flow_file_path,
            d.ba_file_name,
            d.ba_file_path,
            d.updated_at
        ")
            ->whereNull('d.deleted_at')
            ->whereNotNull('d.project_uuid')
            ->where('d.kode_status', 'ACC');

        // union source
        if ($source === 'transfer') {
            $union = $t;
        } elseif ($source === 'disposal') {
            $union = $d;
        } else {
            $union = $t->unionAll($d);
        }

        // totalAll (tanpa filter/search)
        $base     = DB::query()->fromSub($union, 'u');
        $totalAll = (clone $base)->count();

        // query for filter/search/paging
        $q = DB::query()->fromSub($union, 'u');

        // ===== Filters =====
        if ($tfType) {
            $q->where('source_type', 'transfer')->where('tf_type', $tfType);
        }
        if ($dateFrom) {
            $q->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('updated_at', '<=', $dateTo);
        }
        if ($projectUuid) {
            $q->where('project_uuid', $projectUuid);
        }

        if ($assetLike) {
            $q->where(function ($qq) use ($assetLike) {
                $qq->where('asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_description', 'ilike', "%{$assetLike}%")
                    ->orWhere('project_name', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_location', 'ilike', "%{$assetLike}%")
                    ->orWhere('owner', 'ilike', "%{$assetLike}%")
                    ->orWhere('user', 'ilike', "%{$assetLike}%")
                    ->orWhere('maintenance', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_status', 'ilike', "%{$assetLike}%");
            });
        }

        if ($users) {
            $q->where('pic_request_uid', $users);
        }

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('code', 'ilike', "%{$search}%")
                    ->orWhere('asset_code', 'ilike', "%{$search}%")
                    ->orWhere('asset_description', 'ilike', "%{$search}%")
                    ->orWhere('project_name', 'ilike', "%{$search}%")
                    ->orWhere('asset_location', 'ilike', "%{$search}%")
                    ->orWhere('owner', 'ilike', "%{$search}%")
                    ->orWhere('user', 'ilike', "%{$search}%")
                    ->orWhere('maintenance', 'ilike', "%{$search}%")
                    ->orWhere('asset_status', 'ilike', "%{$search}%")
                    ->orWhere('note', 'ilike', "%{$search}%")
                    ->orWhere('pic_request_uid', 'ilike', "%{$search}%")
                    ->orWhere('pic_approve_uid', 'ilike', "%{$search}%");
            });
        }

        $totalFiltered = (clone $q)->count();

        // ===== Ordering (match frontend 18 columns) =====
        // 0 code
        // 1 asset_code
        // 2 asset_description
        // 3 project
        // 4 asset_location
        // 5 owner
        // 6 user
        // 7 maintenance
        // 8 asset_status
        // 9 source
        // 10 type
        // 11 detail (no)
        // 12 note
        // 13 requester
        // 14 approver
        // 15 file (no)
        // 16 updated_at
        // 17 actions (no)
        $orderColIdx = (int) data_get($request->input('order', []), '0.column', 16);
        $orderDir    = data_get($request->input('order', []), '0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $orderMap = [
            0  => 'code',
            1  => 'asset_code',
            2  => 'asset_description',
            3  => 'project_name',
            4  => 'asset_location',
            5  => 'owner',
            6  => 'user',
            7  => 'maintenance',
            8  => 'asset_status',
            9  => 'source_type',
            10 => 'tf_type',
            11 => null,
            12 => 'note',
            13 => 'pic_request_uid',
            14 => 'pic_approve_uid',
            15 => null,
            16 => 'updated_at',
            17 => null,
        ];

        $orderBy = $orderMap[$orderColIdx] ?? 'updated_at';
        if ($orderBy) $q->orderBy($orderBy, $orderDir);
        else $q->orderBy('updated_at', 'desc');

        $rows = $q->skip($start)->take($length)->get()->map(function ($r) {
            $isTransfer = $r->source_type === 'transfer';

            // detail
            if ($isTransfer) {
                $beforeLabel = $r->before_val;
                $afterLabel  = $r->after_val;

                if ($r->before_val) {
                    $beforeUc = MasterUserCode::with('division')->where('kode', $r->before_val)->first();
                    if ($beforeUc) $beforeLabel = $beforeUc->kode . ' - ' . $beforeUc->department;
                }
                if ($r->after_val) {
                    $afterUc = MasterUserCode::with('division')->where('kode', $r->after_val)->first();
                    if ($afterUc) $afterLabel = '<strong>' . $afterUc->kode . ' - ' . $afterUc->department . '</strong>';
                }

                $detail = strtoupper((string)$r->tf_type) . ' - ' . $beforeLabel . ' → ' . $afterLabel;
            } else {
                $detail = 'DISPOSAL';
            }

            // files
            $files = [];
            if (!empty($r->file_path)) {
                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';
                $files[] = '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            if (!empty($r->flow_file_path)) {
                $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                $name = $r->flow_file_name ?: 'Signed Form';
                $files[] = '<a class="btn btn-sm btn-light-success me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            if (!empty($r->ba_file_path)) {
                $url  = url('storage/' . ltrim($r->ba_file_path, '/'));
                $name = $r->ba_file_name ?: 'Berita Acara';
                $files[] = '<a class="btn btn-sm btn-light-warning" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            $file = implode(' ', $files);

            // actions
            $actions = '';
            if ($isTransfer) {
                if (in_array($r->tf_type, ['owner', 'user', 'maintenance'], true)) {
                    $formUrl = route('stockopname.transfer.download.form', $r->uuid);
                    $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-primary btn-sm me-1" target="_blank">Form</a>';
                }
            } else {
                $formUrl = route('stockopname.disposal.download.form', $r->uuid);
                $baUrl   = route('stockopname.disposal.download.ba', $r->uuid);
                $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-info btn-sm me-1" target="_blank">Form</a>';
                $actions .= '<a href="' . e($baUrl) . '" class="btn btn-light-warning btn-sm" target="_blank">BA</a>';
            }

            return [
                'uuid'              => $r->uuid,
                'asset_uuid'        => $r->asset_uuid,
                'project_uuid'      => $r->project_uuid,

                'asset_code'        => $r->asset_code,
                'asset_description' => $r->asset_description ?? '',
                'project'           => $r->project_name ?? '',

                'asset_location'    => $r->asset_location ?? '',
                'owner'             => $r->owner ?? '',
                'user'              => $r->user ?? '',
                'maintenance'       => $r->maintenance ?? '',
                'asset_status'      => $r->asset_status ?? '',

                'code'              => $r->code,
                'source'            => $isTransfer ? 'MOVEMENT' : 'DISPOSAL',
                'type'              => $isTransfer ? strtoupper((string)$r->tf_type) : 'DISPOSAL',
                'detail'            => $detail,
                'note'              => $r->note,
                'pic_request_uid'   => $r->pic_request_uid,
                'pic_approve_uid'   => $r->pic_approve_uid,
                'file'              => $file,
                'updated_at'        => $r->updated_at,
                'actions'           => $actions,
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalAll,
            'recordsFiltered' => $totalFiltered,
            'data'            => $rows,
        ]);
    }
    public function dataByAsset(string $uuid, Request $request)
    {
        if (!$this->canReadStockOpname()) {
            $draw = (int) $request->input('draw', 1);
            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim(data_get($request->input('search', []), 'value', ''));

        $source      = $request->input('source');
        $tfType      = $request->input('tf_type');
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');
        $assetLike   = $request->input('asset');
        $users       = $request->input('users');

        // ===== Transfer base =====
        $t = DB::table('assets_transfers as t')
            ->join('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->leftJoin('assets_assignment as aa', 'aa.asset_uuid', '=', 'a.uuid')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('master_user_code as uo', 'uo.kode', '=', 'aa.asset_owner')
            ->leftJoin('master_user_code as uu', 'uu.kode', '=', 'aa.asset_user')
            ->leftJoin('master_user_code as um', 'um.kode', '=', 'aa.asset_maintenance')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 't.project_uuid')
            ->selectRaw("
            t.uuid,
            t.asset_uuid,
            t.project_uuid,

            a.asset_code,
            a.description as asset_description,

            (COALESCE(a.kode_location,'') || ' - ' || COALESCE(ml.name,'')) as asset_location,
            (COALESCE(aa.asset_owner,'') || ' - ' || COALESCE(uo.department,'')) as owner,
            (COALESCE(aa.asset_user,'') || ' - ' || COALESCE(uu.department,'')) as \"user\",
            (COALESCE(aa.asset_maintenance,'') || ' - ' || COALESCE(um.department,'')) as maintenance,
            (COALESCE(a.kode_status,'') || ' - ' || COALESCE(ms.name,'')) as asset_status,

            COALESCE(p.name,'') as project_name,

            t.transfer_code       as code,
            'transfer'            as source_type,
            t.type                as tf_type,
            COALESCE(t.before->>'value','') as before_val,
            COALESCE(t.after->>'value','')  as after_val,

            t.pic_request_uid,
            t.pic_approve_uid,
            t.note,
            t.file_name,
            t.file_path,
            t.flow_file_name,
            t.flow_file_path,
            NULL::text            as ba_file_name,
            NULL::text            as ba_file_path,
            t.updated_at
        ")
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', 'ACC')
            ->whereNotNull('t.project_uuid')
            ->where('t.asset_uuid', $uuid);

        // ===== Disposal base =====
        $d = DB::table('assets_disposals as d')
            ->join('assets as a', 'a.uuid', '=', 'd.asset_uuid')
            ->leftJoin('assets_assignment as aa', 'aa.asset_uuid', '=', 'a.uuid')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('master_user_code as uo', 'uo.kode', '=', 'aa.asset_owner')
            ->leftJoin('master_user_code as uu', 'uu.kode', '=', 'aa.asset_user')
            ->leftJoin('master_user_code as um', 'um.kode', '=', 'aa.asset_maintenance')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 'd.project_uuid')
            ->selectRaw("
            d.uuid,
            d.asset_uuid,
            d.project_uuid,

            a.asset_code,
            a.description as asset_description,

            (COALESCE(a.kode_location,'') || ' - ' || COALESCE(ml.name,'')) as asset_location,
            (COALESCE(aa.asset_owner,'') || ' - ' || COALESCE(uo.department,'')) as owner,
            (COALESCE(aa.asset_user,'') || ' - ' || COALESCE(uu.department,'')) as \"user\",
            (COALESCE(aa.asset_maintenance,'') || ' - ' || COALESCE(um.department,'')) as maintenance,
            (COALESCE(a.kode_status,'') || ' - ' || COALESCE(ms.name,'')) as asset_status,

            COALESCE(p.name,'') as project_name,

            d.disposal_code       as code,
            'disposal'            as source_type,
            NULL::text            as tf_type,
            COALESCE(d.before_status,'') as before_val,
            COALESCE('DIS','')          as after_val,

            d.pic_request_uid,
            d.pic_approve_uid,
            d.note,
            d.file_name,
            d.file_path,
            d.flow_file_name,
            d.flow_file_path,
            d.ba_file_name,
            d.ba_file_path,
            d.updated_at
        ")
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', 'ACC')
            ->whereNotNull('d.project_uuid')
            ->where('d.asset_uuid', $uuid);

        if ($source === 'transfer') $union = $t;
        elseif ($source === 'disposal') $union = $d;
        else $union = $t->unionAll($d);

        $base     = DB::query()->fromSub($union, 'u');
        $totalAll = (clone $base)->count();

        $q = DB::query()->fromSub($union, 'u');

        // filters
        if ($tfType) {
            $q->where('source_type', 'transfer')->where('tf_type', $tfType);
        }
        if ($dateFrom) $q->whereDate('updated_at', '>=', $dateFrom);
        if ($dateTo)   $q->whereDate('updated_at', '<=', $dateTo);

        if ($assetLike) {
            $q->where(function ($qq) use ($assetLike) {
                $qq->where('asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_description', 'ilike', "%{$assetLike}%")
                    ->orWhere('project_name', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_location', 'ilike', "%{$assetLike}%")
                    ->orWhere('owner', 'ilike', "%{$assetLike}%")
                    ->orWhere('user', 'ilike', "%{$assetLike}%")
                    ->orWhere('maintenance', 'ilike', "%{$assetLike}%")
                    ->orWhere('asset_status', 'ilike', "%{$assetLike}%");
            });
        }

        if ($users) $q->where('pic_request_uid', $users);

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('code', 'ilike', "%{$search}%")
                    ->orWhere('asset_code', 'ilike', "%{$search}%")
                    ->orWhere('asset_description', 'ilike', "%{$search}%")
                    ->orWhere('project_name', 'ilike', "%{$search}%")
                    ->orWhere('asset_location', 'ilike', "%{$search}%")
                    ->orWhere('owner', 'ilike', "%{$search}%")
                    ->orWhere('user', 'ilike', "%{$search}%")
                    ->orWhere('maintenance', 'ilike', "%{$search}%")
                    ->orWhere('asset_status', 'ilike', "%{$search}%")
                    ->orWhere('note', 'ilike', "%{$search}%")
                    ->orWhere('pic_request_uid', 'ilike', "%{$search}%")
                    ->orWhere('pic_approve_uid', 'ilike', "%{$search}%");
            });
        }

        $totalFiltered = (clone $q)->count();

        // ordering - match global 18 cols
        $orderColIdx = (int) data_get($request->input('order', []), '0.column', 16);
        $orderDir    = data_get($request->input('order', []), '0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $orderMap = [
            0  => 'code',
            1  => 'asset_code',
            2  => 'asset_description',
            3  => 'project_name',
            4  => 'asset_location',
            5  => 'owner',
            6  => 'user',
            7  => 'maintenance',
            8  => 'asset_status',
            9  => 'source_type',
            10 => 'tf_type',
            11 => null,
            12 => 'note',
            13 => 'pic_request_uid',
            14 => 'pic_approve_uid',
            15 => null,
            16 => 'updated_at',
            17 => null,
        ];

        $orderBy = $orderMap[$orderColIdx] ?? 'updated_at';
        if ($orderBy) $q->orderBy($orderBy, $orderDir);
        else $q->orderBy('updated_at', 'desc');

        $rows = $q->skip($start)->take($length)->get()->map(function ($r) {
            $isTransfer = $r->source_type === 'transfer';

            if ($isTransfer) {
                $beforeLabel = $r->before_val;
                $afterLabel  = $r->after_val;

                if ($r->before_val) {
                    $beforeUc = MasterUserCode::with('division')->where('kode', $r->before_val)->first();
                    if ($beforeUc) $beforeLabel = $beforeUc->kode . ' - ' . $beforeUc->department;
                }
                if ($r->after_val) {
                    $afterUc = MasterUserCode::with('division')->where('kode', $r->after_val)->first();
                    if ($afterUc) $afterLabel = '<strong>' . $afterUc->kode . ' - ' . $afterUc->department . '</strong>';
                }

                $detail = strtoupper((string)$r->tf_type) . ' - ' . $beforeLabel . ' → ' . $afterLabel;
            } else {
                $detail = 'DISPOSAL';
            }

            $files = [];
            if (!empty($r->file_path)) {
                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';
                $files[] = '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            if (!empty($r->flow_file_path)) {
                $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                $name = $r->flow_file_name ?: 'Signed Form';
                $files[] = '<a class="btn btn-sm btn-light-success me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            if (!empty($r->ba_file_path)) {
                $url  = url('storage/' . ltrim($r->ba_file_path, '/'));
                $name = $r->ba_file_name ?: 'Berita Acara';
                $files[] = '<a class="btn btn-sm btn-light-warning" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            }
            $file = implode(' ', $files);

            $actions = '';
            if ($isTransfer) {
                if (in_array($r->tf_type, ['owner', 'user', 'maintenance'], true)) {
                    $formUrl = route('stockopname.transfer.download.form', $r->uuid);
                    $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-primary btn-sm me-1" target="_blank">Form</a>';
                }
            } else {
                $formUrl = route('stockopname.disposal.download.form', $r->uuid);
                $baUrl   = route('stockopname.disposal.download.ba', $r->uuid);
                $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-info btn-sm me-1" target="_blank">Form</a>';
                $actions .= '<a href="' . e($baUrl) . '" class="btn btn-light-warning btn-sm" target="_blank">BA</a>';
            }

            return [
                'uuid'              => $r->uuid,
                'asset_uuid'        => $r->asset_uuid,
                'project_uuid'      => $r->project_uuid,

                'asset_code'        => $r->asset_code,
                'asset_description' => $r->asset_description ?? '',
                'project'           => $r->project_name ?? '',

                'asset_location'    => $r->asset_location ?? '',
                'owner'             => $r->owner ?? '',
                'user'              => $r->user ?? '',
                'maintenance'       => $r->maintenance ?? '',
                'asset_status'      => $r->asset_status ?? '',

                'code'              => $r->code,
                'source'            => $isTransfer ? 'MOVEMENT' : 'DISPOSAL',
                'type'              => $isTransfer ? strtoupper((string)$r->tf_type) : 'DISPOSAL',
                'detail'            => $detail,
                'note'              => $r->note,
                'pic_request_uid'   => $r->pic_request_uid,
                'pic_approve_uid'   => $r->pic_approve_uid,
                'file'              => $file,
                'updated_at'        => $r->updated_at,
                'actions'           => $actions,
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalAll,
            'recordsFiltered' => $totalFiltered,
            'data'            => $rows,
        ]);
    }
    public function store_transfer(Request $request)
    {
        abort_unless($this->canCreateStockOpname(), 403);

        $data = $request->validate([
            'project_uuid' => ['required', 'uuid'],
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'is_same'      => ['nullable', 'boolean'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'max:51200'],
            'flow_file'    => ['nullable', 'file', 'max:51200'],
        ]);

        $projectUuid = $data['project_uuid'] ?? null;
        abort_if(!$projectUuid, 422, 'project_uuid required');

        $user = auth()->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        $projectOk = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->where('status', 'OPEN')
            ->exists();
        abort_if(!$projectOk, 422, 'Project not found or CLOSED');

        $result = DB::transaction(function () use ($request, $data, $uid, $projectUuid) {
            // lock asset row
            $assetSv = Assets::where('uuid', $data['asset_uuid'])->lockForUpdate()->firstOrFail();

            // ✅ Auto-attach asset to project (NO abort)
            DB::table('asset_project_assets')->updateOrInsert(
                [
                    'project_uuid' => $projectUuid,
                    'asset_uuid'   => $assetSv->uuid,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // BEFORE (current)
            $before = null;
            switch ($data['type']) {
                case 'owner':
                    $before = optional($assetSv->assignment)->asset_owner;
                    break;
                case 'user':
                    $before = optional($assetSv->assignment)->asset_user;
                    break;
                case 'maintenance':
                    $before = optional($assetSv->assignment)->asset_maintenance;
                    break;
                case 'status':
                    $before = $assetSv->kode_status;
                    break;
                case 'location':
                    $before = $assetSv->kode_location;
                    break;
            }

            $isSame = (bool)($data['is_same'] ?? false);

            // AFTER (requested)
            $after = data_get($data, 'after.value');
            if ($isSame) $after = $before; // hard enforce "Sesuai"

            // Update asset only if not sesuai + changed
            if (!$isSame && (string)$after !== (string)$before) {
                switch ($data['type']) {
                    case 'owner':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_owner' => $after]
                        );
                        break;
                    case 'user':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_user' => $after]
                        );
                        break;
                    case 'maintenance':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_maintenance' => $after]
                        );
                        break;
                    case 'status':
                        $assetSv->kode_status = $after;
                        $assetSv->save();
                        break;
                    case 'location':
                        $assetSv->kode_location = $after;
                        $assetSv->save();
                        break;
                }
            }

            $beforeLabel = $this->resolveLabel($data['type'], $before);
            $afterLabel  = $this->resolveLabel($data['type'], $after);

            $note = trim((string)($data['note'] ?? ''));
            if ((string)$after === (string)$before) {
                $note = trim('[SESUAI] ' . $note);
            }

            // Code generator
            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Transfer::where('transfer_code', 'like', $prefix . '%')
                ->orderBy('transfer_code', 'desc')
                ->first();

            $seq  = $last ? ((int)substr($last->transfer_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Uploads
            [$path, $orig, $mime, $size] = $this->saveUploadTf($request->file('file'), $assetSv, $code, null);
            [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadTf($request->file('flow_file'), $assetSv, $code, null);

            $transfer = Transfer::create([
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'       => $assetSv->uuid,
                'project_uuid'     => $projectUuid,

                'transfer_code'    => $code,
                'type'             => $data['type'],
                'before'           => ['value' => $before],
                'after'            => ['value' => $after],
                'before_label'     => $beforeLabel,
                'after_label'      => $afterLabel,

                'kode_status'      => 'ACC',
                'note'             => $note ?: null,

                'pic_request_uid'  => $uid,
                'pic_approve_uid'  => $uid,

                'file_path'        => $path,
                'file_name'        => $orig,
                'file_mime'        => $mime,
                'file_size'        => $size,

                'flow_file_path'   => $flowPath,
                'flow_file_name'   => $flowOrig,
                'flow_file_mime'   => $flowMime,
                'flow_file_size'   => $flowSize,
            ]);

            return $transfer;
        });

        return response()->json([
            'ok'   => true,
            'id'   => $result->uuid,
            'code' => $result->transfer_code
        ]);
    }

    public function store_disposal(Request $request)
    {
        abort_unless($this->canCreateStockOpname(), 403);

        $data = $request->validate([
            'project_uuid'   => ['required', 'uuid'],
            'asset_uuid'     => ['required', 'uuid', 'exists:assets,uuid'],
            'note'           => ['nullable', 'string', 'max:1000'],
            'target_status'  => ['nullable', 'string'],
            'file'           => ['nullable', 'file', 'max:51200'],
            'flow_file'      => ['required', 'file', 'max:51200'],
            'ba_file'        => ['required', 'file', 'max:51200'],
            'reason'         => ['required', Rule::in(['Sale', 'Waste', 'Donate', 'Held'])],
        ]);

        $projectUuid = $data['project_uuid'] ?? null;
        abort_if(!$projectUuid, 422, 'project_uuid required');

        $projectOk = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->where('status', 'OPEN')
            ->exists();
        abort_if(!$projectOk, 422, 'Project not found or CLOSED');

        $user = auth()->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        $result = DB::transaction(function () use ($request, $data, $uid, $projectUuid) {
            $asset = Assets::where('uuid', $data['asset_uuid'])->lockForUpdate()->firstOrFail();

            // ✅ Auto-attach asset to project (NO abort)
            DB::table('asset_project_assets')->updateOrInsert(
                [
                    'project_uuid' => $projectUuid,
                    'asset_uuid'   => $asset->uuid,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $target = $data['target_status'] ?? 'DIS';

            $ok = MasterStatus::where('kode', $target)
                ->where(function ($q) {
                    $q->where('type', 'Asset')->orWhereNull('type');
                })
                ->exists();
            abort_unless($ok, 422, 'Target disposal status not valid');

            // Code generator
            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Disposal::where('disposal_code', 'like', $prefix . '%')
                ->orderBy('disposal_code', 'desc')
                ->first();

            $seq  = $last ? ((int)substr($last->disposal_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Uploads
            [$path, $orig, $mime, $size] = $this->saveUploadDis($request->file('file'), $asset, $code, null);
            [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadDis($request->file('flow_file'), $asset, $code, null);
            [$baPath, $baOrig, $baMime, $baSize] = $this->saveUploadDis($request->file('ba_file'), $asset, $code, null);

            $note = trim((string)($data['note'] ?? ''));

            $row = Disposal::create([
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'       => $asset->uuid,
                'project_uuid'     => $projectUuid,

                'disposal_code'    => $code,
                'target_status'    => $target,
                'kode_status'      => 'ACC',

                'note'             => $note ?: null,
                'reason'           => $data['reason'],

                'before_status'    => $asset->kode_status,

                'file_path'        => $path,
                'file_name'        => $orig,
                'file_mime'        => $mime,
                'file_size'        => $size,

                'flow_file_path'   => $flowPath,
                'flow_file_name'   => $flowOrig,
                'flow_file_mime'   => $flowMime,
                'flow_file_size'   => $flowSize,

                'ba_file_path'     => $baPath,
                'ba_file_name'     => $baOrig,
                'ba_file_mime'     => $baMime,
                'ba_file_size'     => $baSize,

                'pic_request_uid'  => $uid,
                'pic_approve_uid'  => $uid,
            ]);

            // update asset status
            $asset->kode_status = $target;
            $asset->save();

            return $row;
        });

        return response()->json([
            'ok'   => true,
            'id'   => $result->uuid,
            'code' => $result->disposal_code
        ]);
    }
    public function downloadTransferForm($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $transfer = Transfer::with([
            'asset.assignment.owner.division',
            'asset.assignment.user.division',
            'asset.assignment.maintenance.division',
            'asset.location',
            'asset.value.uom'
        ])->findOrFail($uuid);

        $asset = $transfer->asset;
        $tfNote = $transfer->note ?? '';
        $assign = $asset?->assignment;
        $beforeVal = data_get($transfer->before, 'value');
        $afterVal = data_get($transfer->after, 'value');
        $createdAt = $transfer->created_at?->timezone('Asia/Jakarta');

        $flowRaw   = $transfer->flow ?? null;
        $flowArray = null;

        if (is_string($flowRaw) && $flowRaw !== '') {
            $decoded = json_decode($flowRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flowArray = $decoded;
            }
        } elseif (is_array($flowRaw)) {
            $flowArray = $flowRaw;
        }
        $doneLines = [];
        if (!empty($flowArray['steps']) && is_array($flowArray['steps'])) {
            foreach ($flowArray['steps'] as $st) {
                if (!empty($st['approved_at'])) {
                    $label = $st['label'] ?? ($st['code'] ?? '');
                    $role  = $st['role'] ?? '';
                    $by    = $st['approved_by'] ?? '';
                    $atRaw = $st['approved_at'];

                    try {
                        $at = \Carbon\Carbon::parse($atRaw, 'Asia/Jakarta')
                            ->timezone('Asia/Jakarta')
                            ->format('d-m-Y H:i');
                    } catch (\Throwable $e) {
                        $at = $atRaw;
                    }

                    // e.g. "Create Disposal Request (User Departemen) - 21-11-2025 15:53 - Administrator"
                    $line = trim(
                        ($label ?: '') .
                            ($role ? ' (' . $role . ')' : '') .
                            ($at ? ' - ' . $at : '') .
                            ($by ? ' - ' . $by : '')
                    );

                    if ($line !== '') {
                        $doneLines[] = $line;
                    }
                }
            }
        }

        $flowText = implode("\n", $doneLines);
        $template = storage_path('app/public/template/form_transfer_asset.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        // Determine FROM usercode based on transfer type
        $beforeUc = null;
        switch ($transfer->type) {
            case 'owner':
                $beforeUc = $assign?->owner;
                break;
            case 'user':
                $beforeUc = $assign?->user;
                break;
            case 'maintenance':
                $beforeUc = $assign?->maintenance;
                break;
        }

        // Get AFTER usercode
        $afterUc = $afterVal
            ? MasterUserCode::with('division')->where('kode', $afterVal)->first()
            : null;

        // Build FROM labels
        $fromDeptLabel = '';
        $fromDivLabel = '';
        if ($beforeUc) {
            $fromDeptLabel = trim($beforeUc->kode . ' - ' . $beforeUc->department);
            if ($beforeUc->division) {
                $fromDivLabel = trim($beforeUc->division->kode . ' - ' . $beforeUc->division->name);
            }
        } elseif ($beforeVal !== null && $beforeVal !== '') {
            $fromDeptLabel = $beforeVal;
        }

        // Build TO labels
        $toDeptLabel = '';
        $toDivLabel = '';
        if ($afterUc) {
            $toDeptLabel = trim($afterUc->kode . ' - ' . $afterUc->department);
            if ($afterUc->division) {
                $toDivLabel = trim($afterUc->division->kode . ' - ' . $afterUc->division->name);
            }
        } elseif ($afterVal !== null && $afterVal !== '') {
            $toDeptLabel = $afterVal;
        }

        // Location labels
        $loc = $asset?->location;
        $locLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';
        $fromLocLabel = $locLabel;
        $toLocLabel = $locLabel;

        // V11: Transfer Code
        $sheet->setCellValue('V11', $transfer->transfer_code ?? '');

        // FROM section (G15-G18)
        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $createdAt ? $createdAt->format('d-m-Y') : '');

        // TO section (S15-S18)
        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $createdAt ? $createdAt->format('d-m-Y') : '');

        // Asset details (row 23)
        $sheet->setCellValue('C23', $asset?->asset_code ?? '');
        $sheet->setCellValue('D23', $asset?->description ?? '');

        $qty = $asset?->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset?->value?->uom?->name
            ?? $asset?->value?->kode_uom
            ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset?->notes ?? '');
        $sheet->setCellValue('C30', $tfNote ?? '');

        $sheet->setCellValue('C34', $flowText);
        $sheet->getStyle('C34')->getAlignment()->setWrapText(true);

        // Signature sections
        $sheet->setCellValue('C36', $fromDeptLabel);

        $sheet->setCellValue('I36', $toDeptLabel);

        $fileName = 'Form Transfer Asset - ' . ($transfer->transfer_code ?? 'MOV') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function downloadDisposalForm($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $disposal = Disposal::with([
            'asset.location',
            'asset.value',
            'asset.assignment.owner.division',
        ])->findOrFail($uuid);

        $asset = $disposal->asset;
        $reason   = $disposal->reason ?? '';
        $assign = $asset?->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $createdAt = $disposal->created_at?->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = \Carbon\Carbon::parse($asset->value->actual_date)->timezone('Asia/Jakarta');
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0;
        $comNbv = $ledger?->nbv_sum ?? 0;
        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $justification = $disposal->note ?? '';

        // ========= NEW: build "done" flow steps text =========
        $flowRaw   = $disposal->flow ?? null;
        $flowArray = null;

        if (is_string($flowRaw) && $flowRaw !== '') {
            $decoded = json_decode($flowRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flowArray = $decoded;
            }
        } elseif (is_array($flowRaw)) {
            $flowArray = $flowRaw;
        }

        $doneLines = [];
        if (!empty($flowArray['steps']) && is_array($flowArray['steps'])) {
            foreach ($flowArray['steps'] as $st) {
                if (!empty($st['approved_at'])) {
                    $label = $st['label'] ?? ($st['code'] ?? '');
                    $role  = $st['role'] ?? '';
                    $by    = $st['approved_by'] ?? '';
                    $atRaw = $st['approved_at'];

                    try {
                        $at = \Carbon\Carbon::parse($atRaw, 'Asia/Jakarta')
                            ->timezone('Asia/Jakarta')
                            ->format('d-m-Y H:i');
                    } catch (\Throwable $e) {
                        $at = $atRaw;
                    }

                    // e.g. "Create Disposal Request (User Departemen) - 21-11-2025 15:53 - Administrator"
                    $line = trim(
                        ($label ?: '') .
                            ($role ? ' (' . $role . ')' : '') .
                            ($at ? ' - ' . $at : '') .
                            ($by ? ' - ' . $by : '')
                    );

                    if ($line !== '') {
                        $doneLines[] = $line;
                    }
                }
            }
        }

        $flowText = implode("\n", $doneLines);
        // ========= END NEW =========

        $template = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Disposal Aset Tetap') ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7',  $companyName);
        $sheet->setCellValue('H8',  $disposal->disposal_code ?? '');
        $sheet->setCellValue('H9',  $createdAt ? $createdAt->format('d-m-Y') : '');
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', $reason);

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost); // Commercial Acquisition Cost (IDR)
        $sheet->setCellValue('H19', $comAcc);  // Commercial Accumulated Depreciation (IDR)
        $sheet->setCellValue('H20', $comNbv);  // Commercial Net Book Value (IDR)
        $sheet->setCellValue('H21', $taxNbv);  // Tax Net Book Value (IDR)

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', $justification);

        $sheet->setCellValue('C26', $flowText);
        $sheet->getStyle('C26')->getAlignment()->setWrapText(true);

        $fileName = 'Form Disposal Aset Tetap - ' . ($disposal->disposal_code ?? 'DSP') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function downloadDisposalBa($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $disposal = Disposal::with([
            'asset.location',
            'asset.assignment.owner',
        ])->findOrFail($uuid);

        $asset = $disposal->asset;
        $location = $asset?->location;
        $owner = $asset?->assignment?->owner;

        $assetName = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode = $asset?->asset_code ?? '';
        $formNumber = $disposal->disposal_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' - ' . ($location->name ?? ''))
            : '';
        $ownerDept = $owner?->department ?? '';
        $keterangan = $disposal->note ?? '';

        $date = $disposal->updated_at
            ? $disposal->updated_at->copy()->timezone('Asia/Jakarta')
            : now()->timezone('Asia/Jakarta');

        \Carbon\Carbon::setLocale('id');

        $hari = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI' => $hari,
            'TANGGAL_BULAN_TAHUN' => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET' => $assetName,
            'NOMOR_ASET' => $assetCode,
            'NOMOR_FORM' => $formNumber,
            'LOKASI' => $locationLbl,
            'DEPARTEMEN_OWNER' => $ownerDept,
            'KETERANGAN' => $keterangan,
        ]);

        $fileName = 'BA_Disposal_' . ($disposal->disposal_code ?? $uuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    // Preview endpoints: generate templates based on selected asset (no record required)
    public function previewTransferForm(Request $request, $assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with(['assignment.owner.division', 'assignment.user.division', 'assignment.maintenance.division', 'location', 'value.uom'])->findOrFail($assetUuid);

        $template = storage_path('app/public/template/form_transfer_asset.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        $assign = $asset->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $fromDeptLabel = $owner ? trim($owner->kode . ' - ' . $owner->department) : '';
        $fromDivLabel = $ownerDiv ? $ownerDiv->name : '';

        $loc = $asset->location;
        $fromLocLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';

        $now = now()->timezone('Asia/Jakarta');

        // V11: Transfer Code
        $sheet->setCellValue('V11', '');

        // FROM section (G15-G18)
        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $now->format('d-m-Y'));

        // TO section (S15-S18) - based on target selector
        $targetType = $request->input('target_type');
        $targetValue = $request->input('target_value');

        $toDeptLabel = '';
        $toDivLabel = '';
        $toLocLabel = '';
        $toSignatureLabel = '';

        if ($targetType && $targetValue) {
            if ($targetType === 'owner') {
                $targetOwner = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetOwner) {
                    $toDeptLabel = trim($targetOwner->kode . ' - ' . $targetOwner->department);
                    $toDivLabel = $targetOwner->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'user') {
                $targetUser = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetUser) {
                    $toDeptLabel = trim($targetUser->kode . ' - ' . $targetUser->department);
                    $toDivLabel = $targetUser->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'maintenance') {
                $targetMaint = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetMaint) {
                    $toDeptLabel = trim($targetMaint->kode . ' - ' . $targetMaint->department);
                    $toDivLabel = $targetMaint->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'location') {
                $targetLoc = MasterLocation::where('kode', $targetValue)->first();
                if ($targetLoc) {
                    $toLocLabel = trim($targetLoc->kode . ' - ' . $targetLoc->name);
                }
                $toDeptLabel = $fromDeptLabel; // same department
                $toDivLabel = $fromDivLabel; // same division
                $toSignatureLabel = $toDeptLabel;
            }
        }

        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $now->format('d-m-Y'));

        // Asset details (row 23)
        $sheet->setCellValue('C23', $asset->asset_code ?? '');
        $sheet->setCellValue('D23', $asset->description ?? '');

        $qty = $asset->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset->value?->uom?->name ?? $asset->value?->kode_uom ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset->notes ?? '');

        // $sheet->setCellValue('C30', $tfNote ?? '');

        // $sheet->setCellValue('C34', $flowText);
        // $sheet->getStyle('C34')->getAlignment()->setWrapText(true);

        // Signature sections

        $sheet->setCellValue('C36', $fromDeptLabel);

        $sheet->setCellValue('I36', $toDeptLabel);

        $fileName = 'Form_Transfer_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewDisposalForm($assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with([
            'assignment.owner.division',
            'location',
            'value',
        ])->findOrFail($assetUuid);

        $assign = $asset?->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $now = now()->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = \Carbon\Carbon::parse($asset->value->actual_date)->timezone('Asia/Jakarta');
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0;
        $comNbv = $ledger?->nbv_sum ?? 0;
        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $template = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Disposal Aset Tetap') ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7', $companyName);
        $sheet->setCellValue('H8', '');
        $sheet->setCellValue('H9', $now->format('d-m-Y'));
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', '');

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost);
        $sheet->setCellValue('H19', $comAcc);
        $sheet->setCellValue('H20', $comNbv);
        $sheet->setCellValue('H21', $taxNbv);

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', '');

        $fileName = 'Form_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewDisposalBa($assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with([
            'location',
            'assignment.owner',
        ])->findOrFail($assetUuid);

        $location = $asset?->location;
        $owner = $asset?->assignment?->owner;

        $assetName = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode = $asset?->asset_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' - ' . ($location->name ?? ''))
            : '';
        $ownerDept = $owner?->department ?? '';

        $date = now()->timezone('Asia/Jakarta');

        \Carbon\Carbon::setLocale('id');

        $hari = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI' => $hari,
            'TANGGAL_BULAN_TAHUN' => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET' => $assetName,
            'NOMOR_ASET' => $assetCode,
            'NOMOR_FORM' => 'PREVIEW',
            'LOKASI' => $locationLbl,
            'DEPARTEMEN_OWNER' => $ownerDept,
            'KETERANGAN' => '',
        ]);

        $fileName = 'BA_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    private function saveUploadTf($file, Assets $asset, $code, $existing = null)
    {
        if (!$file) return [null, null, null, null];
        $disk = 'public';
        $dir = 'transfers/' . $asset->uuid;
        $ext = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs($dir, $name, $disk);
        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }
        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    private function saveUploadDis($file, Assets $asset, $code, $existing = null)
    {
        if (!$file) return [null, null, null, null];
        $disk = 'public';
        $dir = 'disposals/' . $asset->uuid;
        $ext = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs($dir, $name, $disk);
        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }
        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    private function resolveLabel($type, $code)
    {
        $type = is_string($type) ? trim($type) : '';
        $code = is_string($code) ? trim($code) : '';
        if ($code === '') return '(empty)';
        if ($type === '') return $code;

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                $uc = MasterUserCode::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $uc ? trim($uc->kode . ' - ' . $uc->name) : $code;
            case 'status':
                $s = MasterStatus::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $s ? trim($s->kode . ' - ' . $s->name) : $code;
            case 'location':
                $l = MasterLocation::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $l ? trim($l->kode . ' - ' . $l->name) : $code;
            default:
                return $code;
        }
    }
    public function selectIndex()
    {
        abort_unless(request()->user()?->hasAction('ASSETS', 'R'), 403);
        return view('stock_opname.project');
    }

    public function selectDatatable(Request $request)
    {
        abort_unless($request->user()?->hasAction('ASSETS', 'R'), 403);

        $lastDepr = DB::table('assets_depr_ledger_monthly as m')
            ->selectRaw("
                DISTINCT ON (m.asset_uuid)
                m.asset_uuid, m.period, m.accumulated_depr_end, m.ending_balance
            ")
            ->orderBy('m.asset_uuid')
            ->orderByDesc('m.period');

        $q = DB::table('assets as a')
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoinSub($lastDepr, 'dm', fn($j) => $j->on('dm.asset_uuid', '=', 'a.uuid'))
            ->leftJoin('master_location     as ml',   'ml.kode',   'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   'a.kode_status')
            ->leftJoin('master_user_code    as ou',   'ou.kode',   'g.asset_owner')
            ->leftJoin('master_user_code    as uu',   'uu.kode',   'g.asset_user')
            ->leftJoin('master_user_code    as um',   'um.kode',   'g.asset_maintenance')
            ->whereNull('a.deleted_at')
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.asset_number_parent',
                'a.asset_number_child',
                'a.description',
                'a.kode_location',
                'a.kode_status',
                'a.kode_asset_class',
                'v.total',
                DB::raw("COALESCE(dm.accumulated_depr_end, 0) as last_accumulated_depr"),
                DB::raw("COALESCE(v.total, 0) - COALESCE(dm.accumulated_depr_end, 0) as last_net_book_value"),
                DB::raw("
                    CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label,
                    CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label,
                    CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label,
                    CASE WHEN ou.department IS NULL THEN g.asset_owner ELSE g.asset_owner || ' - ' || ou.department END AS asset_owner_label,
                    CASE WHEN uu.department IS NULL THEN g.asset_user  ELSE g.asset_user  || ' - ' || uu.department END AS asset_user_label,
                    CASE WHEN um.department IS NULL THEN g.asset_maintenance ELSE g.asset_maintenance || ' - ' || um.department END AS asset_maintenance_label
                ")
            );

        // same filters as your index
        if ($assetClass = $request->input('asset_class')) $q->where('a.kode_asset_class', $assetClass);
        if ($transaction = $request->input('transaction')) $q->where('mac.kode_transaction', $transaction);
        if ($location = $request->input('location')) $q->where('a.kode_location', $location);
        if ($status = $request->input('status')) $q->where('a.kode_status', $status);
        if ($owner = $request->input('owner')) $q->where('g.asset_owner', $owner);
        if ($user = $request->input('user')) $q->where('g.asset_user', $user);
        if ($maintenance = $request->input('maintenance')) $q->where('g.asset_maintenance', $maintenance);
        if ($sumber = $request->input('sumber')) $q->where('a.kode_sumber', $sumber);

        if ($search = trim((string) $request->input('asset_q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('a.asset_code', 'ilike', "%{$search}%")
                    ->orWhere('a.description', 'ilike', "%{$search}%");
            });
        }

        $dt = DataTables::of($q);

        // add checkbox column
        $dt->addColumn('select_cb', function ($row) {
            $uuid = e($row->uuid);
            return '<input type="checkbox" class="row-select form-check-input" data-uuid="' . $uuid . '">';
        });

        // keep HTML
        $dt->rawColumns(['select_cb']);

        return $dt->make(true);
    }

    public function selectSubmit(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        $uuids = $request->input('uuids');
        if (is_string($uuids)) {
            $uuids = json_decode($uuids, true);
        }
        $uuids = is_array($uuids) ? array_values(array_unique(array_filter($uuids))) : [];

        // basic safety validation (exists)
        $valid = DB::table('assets')->whereIn('uuid', $uuids)->pluck('uuid')->all();

        // Example behavior: store to session for next module usage
        session(['selected_asset_uuids' => $valid]);

        return redirect()
            ->route('stockopname.assets.select.index')
            ->with('success', 'Selected assets saved: ' . count($valid));
    }
    public function assignProject(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'U') || $request->user()?->hasAction('STOCK_OPN', 'C'), 403);

        $data = $request->validate([
            'project_uuid'   => ['nullable', 'uuid'],
            'project_name' => ['nullable', 'string', 'max:150'],
            'uuids'        => ['required', 'array', 'min:1'],
            'uuids.*'      => ['uuid'],
        ]);

        $projectUuid = trim((string)($data['project_uuid'] ?? ''));
        $projectName = trim((string)($data['project_name'] ?? ''));

        if ($projectUuid === '' && $projectName === '') {
            return back()->withErrors(['project_name' => 'Select existing project or enter new project name.']);
        }

        $uuids = array_values(array_unique(array_filter($data['uuids'])));

        // Only assign existing assets
        $validUuids = DB::table('assets')
            ->whereNull('deleted_at')
            ->whereIn('uuid', $uuids)
            ->pluck('uuid')
            ->all();

        if (count($validUuids) === 0) {
            return back()->withErrors(['uuids' => 'No valid assets found to assign.']);
        }

        $finalProjectUuid = DB::transaction(function () use ($request, $projectUuid, $projectName, $validUuids) {

            // 1) Determine project UUID (must be OPEN if existing)
            if ($projectUuid !== '') {
                $existsOpen = DB::table('asset_projects')
                    ->whereNull('deleted_at')
                    ->where('uuid', $projectUuid)
                    ->where('status', 'OPEN')
                    ->exists();

                if (!$existsOpen) {
                    abort(422, 'Selected project not found or already CLOSED.');
                }

                $puuid = $projectUuid;
            } else {
                $puuid = (string) Str::uuid();

                DB::table('asset_projects')->insert([
                    'uuid'       => $puuid,
                    'name'       => $projectName,
                    'status'     => 'OPEN', // auto OPEN on create
                    'created_by' => $request->user()->id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2) Upsert pivot rows (project_uuid + asset_uuid)
            $now = now();
            $rows = array_map(fn($assetUuid) => [
                'project_uuid' => $puuid,
                'asset_uuid'   => $assetUuid,
                'created_at'   => $now,
                'updated_at'   => $now,
            ], $validUuids);

            DB::table('asset_project_assets')->upsert(
                $rows,
                ['project_uuid', 'asset_uuid'],
                ['updated_at']
            );

            return $puuid;
        });

        return redirect()
            ->route('stockopname.asset_projects.show', $finalProjectUuid)
            ->with('success', 'Assets assigned to project successfully.');
    }


    public function projectIndex(Request $request)
    {
        abort_unless($request->user()?->hasAction('ASSETS', 'R'), 403);
        return view('stock_opname.list_project');
    }

    public function projectDatatable(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        $q = DB::table('asset_projects as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->leftJoin('asset_project_assets as pa', 'pa.project_uuid', '=', 'p.uuid')
            ->whereNull('p.deleted_at')
            ->groupBy('p.uuid', 'p.name', 'p.status', 'p.created_at', 'u.name')
            ->selectRaw("
                p.uuid,
                p.name,
                p.status,
                p.created_at,
                COALESCE(u.name,'-') as created_by_name,
                COUNT(pa.asset_uuid) as asset_count
                ");

        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $uuid = e($row->uuid);
                $disabled = ($row->status === 'CLOSED') ? 'disabled' : '';
                return '
        <button class="btn btn-sm btn-light-danger btn-close-project"
          data-uuid="' . $uuid . '" ' . $disabled . '>
          Close
        </button>
      ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function projectShow(Request $request, AssetProject $project)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);
        abort_if($project->deleted_at, 404);

        // quick summary
        $assetCount = DB::table('asset_project_assets')
            ->where('project_uuid', $project->uuid)
            ->count();

        return view('stock_opname.project_show', compact('project', 'assetCount'));
    }

    public function assetsProjectDatatable(Request $request, AssetProject $project)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);
        abort_if($project->deleted_at, 404);

        $q = DB::table('asset_project_assets as pa')
            ->join('assets as a', 'a.uuid', '=', 'pa.asset_uuid')
            ->leftJoin('master_asset_class as mac', 'mac.kode', '=', 'a.kode_asset_class')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('assets_value as v', 'v.asset_uuid', '=', 'a.uuid')
            ->where('pa.project_uuid', $project->uuid)
            ->whereNull('a.deleted_at')
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.description',
                'pa.created_at as assigned_at',
                'pa.stock_opname_done',
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label"),
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label"),
                'v.total'
            );

        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $uuid = e($row->uuid);
                return '<button class="btn btn-sm btn-light-danger btn-remove-asset" data-uuid="' . $uuid . '">Remove</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function setStockOpnameDone(Request $request, AssetProject $project, string $assetUuid)
    {
        // abort_unless($request->user()?->hasAction('STOCK_OPN', 'C'), 403);
        // abort_if($project->deleted_at, 404);
        abort_if($project->status === 'CLOSED', 422, 'Project already CLOSED');

        $data = $request->validate([
            'done' => ['required', 'boolean'],
        ]);

        $exists = DB::table('asset_project_assets')
            ->where('project_uuid', $project->uuid)
            ->where('asset_uuid', $assetUuid)
            ->exists();

        abort_if(!$exists, 404, 'Asset not assigned to this project');

        $done = (bool) $data['done'];
        $by = $request->user()->name ?? $request->user()->email ?? null;

        DB::table('asset_project_assets')
            ->where('project_uuid', $project->uuid)
            ->where('asset_uuid', $assetUuid)
            ->update([
                'stock_opname_done' => $done,
                'stock_opname_at'   => $done ? now() : null,
                'stock_opname_by'   => $done ? $by : null,
                'updated_at'        => now(),
            ]);

        return response()->json(['ok' => true, 'done' => $done]);
    }

    public function bulkSetStockOpnameDone(Request $request, AssetProject $project)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'C'), 403);
        abort_if($project->deleted_at, 404);
        abort_if($project->status === 'CLOSED', 422, 'Project already CLOSED');

        $data = $request->validate([
            'done' => ['required', 'boolean'],
            'asset_uuids' => ['required', 'array', 'min:1', 'max:500'],
            'asset_uuids.*' => ['required', 'string', 'max:64'],
        ]);

        $done = (bool) $data['done'];
        $by = $request->user()->name ?? $request->user()->email ?? null;

        // update hanya asset yang memang assigned ke project tsb
        $affected = DB::table('asset_project_assets')
            ->where('project_uuid', $project->uuid)
            ->whereIn('asset_uuid', $data['asset_uuids'])
            ->update([
                'stock_opname_done' => $done,
                'stock_opname_at'   => $done ? now() : null,
                'stock_opname_by'   => $done ? $by : null,
                'updated_at'        => now(),
            ]);

        return response()->json([
            'ok' => true,
            'done' => $done,
            'affected' => (int) $affected,
        ]);
    }

    public function removeAssetProject(Request $request, AssetProject $project, string $assetUuid)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'C'), 403);
        abort_if($project->deleted_at, 404);

        DB::table('asset_project_assets')
            ->where('project_uuid', $project->uuid)
            ->where('asset_uuid', $assetUuid)
            ->delete();

        return response()->json(['ok' => true]);
    }
    public function projectAssetOptions(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        $projectUuid = trim((string) $request->get('project_uuid', ''));
        abort_if($projectUuid === '', 422, 'project_uuid is required');

        $projectOk = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->where('status', 'OPEN')
            ->exists();

        abort_if(!$projectOk, 404, 'Project not found or already CLOSED');

        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $builder = DB::table('asset_project_assets as pa')
            ->join('assets as a', 'a.uuid', '=', 'pa.asset_uuid')
            ->where('pa.project_uuid', $projectUuid)
            ->whereNull('a.deleted_at')
            ->orderBy('a.asset_code');

        if ($q !== '') {
            $builder->where(function ($w) use ($q) {
                $w->where('a.asset_code', 'ilike', "%{$q}%")
                    ->orWhere('a.description', 'ilike', "%{$q}%");
            });
        }

        $total = (clone $builder)->count();

        $rows = $builder->forPage($page, $perPage)->get([
            'a.uuid as id',
            DB::raw("(a.asset_code || ' — ' || COALESCE(a.description,'')) as text"),
        ]);

        return response()->json([
            'results' => $rows,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    // Select2 options: q + pagination
    public function projectOptions(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        $q = trim((string)$request->get('q', ''));
        $page = max(1, (int)$request->get('page', 1));
        $flag = $request->get('flag') ?? 0;
        $perPage = 20;

        $builder = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->orderBy('name');

        if ($flag != 1) $builder->where('status', 'OPEN');
        if ($q !== '') $builder->where('name', 'ilike', "%{$q}%");

        $total = (clone $builder)->count();
        $rows  = $builder->forPage($page, $perPage)->get(['uuid', 'name']);

        return response()->json([
            'results' => $rows->map(fn($r) => [
                'id'   => $r->uuid,
                'text' => $r->name
            ])->values(),
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
    public function projectClose(Request $request, \App\Models\AssetProject $project)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'C'), 403);

        if ($project->status === 'CLOSED') {
            return response()->json(['ok' => true, 'status' => 'CLOSED']);
        }

        $project->update(['status' => 'CLOSED']);
        return response()->json(['ok' => true, 'status' => 'CLOSED']);
    }
    public function reopenProject(Request $request, AssetProject $project)
    {
        // abort_unless($request->user()?->hasAction('STOCK_OPN', 'U'), 403);
        // abort_if($project->deleted_at, 404);

        if ($project->status !== 'CLOSED') {
            return back()->with('success', 'Project already OPEN.');
        }

        DB::table('asset_projects')
            ->where('uuid', $project->uuid)
            ->update([
                'status'     => 'OPEN',
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Project reopened.');
    }

    public function selectExportExcel(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        // --- last depr subquery (same as datatable) ---
        $lastDepr = DB::table('assets_depr_ledger_monthly as m')
            ->selectRaw("
            DISTINCT ON (m.asset_uuid)
            m.asset_uuid, m.period, m.accumulated_depr_end, m.ending_balance
        ")
            ->orderBy('m.asset_uuid')
            ->orderByDesc('m.period');

        // --- base query (same as datatable) ---
        $q = DB::table('assets as a')
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoinSub($lastDepr, 'dm', fn($j) => $j->on('dm.asset_uuid', '=', 'a.uuid'))
            ->leftJoin('master_location     as ml', 'ml.kode', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac', 'mac.kode', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms', 'ms.kode', 'a.kode_status')
            ->leftJoin('master_user_code    as ou', 'ou.kode', 'g.asset_owner')
            ->leftJoin('master_user_code    as uu', 'uu.kode', 'g.asset_user')
            ->leftJoin('master_user_code    as um', 'um.kode', 'g.asset_maintenance')
            ->whereNull('a.deleted_at')
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.asset_number_parent',
                'a.asset_number_child',
                'a.description',
                'a.kode_location',
                'a.kode_status',
                'a.kode_asset_class',
                DB::raw("COALESCE(v.total, 0) as total"),
                DB::raw("COALESCE(dm.accumulated_depr_end, 0) as last_accumulated_depr"),
                DB::raw("COALESCE(v.total, 0) - COALESCE(dm.accumulated_depr_end, 0) as last_net_book_value"),
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label"),
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label"),
                DB::raw("CASE WHEN ou.department IS NULL THEN g.asset_owner ELSE g.asset_owner || ' - ' || ou.department END AS asset_owner_label"),
                DB::raw("CASE WHEN uu.department IS NULL THEN g.asset_user  ELSE g.asset_user  || ' - ' || uu.department END AS asset_user_label"),
                DB::raw("CASE WHEN um.department IS NULL THEN g.asset_maintenance ELSE g.asset_maintenance || ' - ' || um.department END AS asset_maintenance_label")
            );

        // --- apply same filters ---
        if ($assetClass = $request->input('asset_class')) $q->where('a.kode_asset_class', $assetClass);
        if ($transaction = $request->input('transaction')) $q->where('mac.kode_transaction', $transaction);
        if ($location = $request->input('location')) $q->where('a.kode_location', $location);
        if ($status = $request->input('status')) $q->where('a.kode_status', $status);
        if ($owner = $request->input('owner')) $q->where('g.asset_owner', $owner);
        if ($user = $request->input('user')) $q->where('g.asset_user', $user);
        if ($maintenance = $request->input('maintenance')) $q->where('g.asset_maintenance', $maintenance);
        if ($sumber = $request->input('sumber')) $q->where('a.kode_sumber', $sumber);

        if ($search = trim((string)$request->input('asset_q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('a.asset_code', 'ilike', "%{$search}%")
                    ->orWhere('a.description', 'ilike', "%{$search}%");
            });
        }

        // optional ordering (match UI)
        $q->orderByDesc('a.asset_code');

        $filename = 'stockopname_assets_' . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($q) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Assets');

            // Header
            $headers = [
                'Asset Code',
                'Asset Class',
                'Description',
                'Location',
                'Owner',
                'User',
                'Maintenance',
                'Status',
                'Total',
                'Accum Depr',
                'NBV',
                'Checklist',
            ];

            $col = 1;
            foreach ($headers as $i => $h) {
                $col = Coordinate::stringFromColumnIndex($i + 1); // 1->A, 2->B ...
                $sheet->setCellValue($col . '1', $h);
            }

            // Style header
            $sheet->getStyle('A1:L1')->getFont()->setBold(true);
            $sheet->freezePane('A2');

            // Data rows (chunk to keep memory low)
            $rowNum = 2;

            $q->chunk(1000, function ($rows) use (&$rowNum, $sheet) {
                foreach ($rows as $r) {
                    // text columns
                    $sheet->setCellValueExplicit("A{$rowNum}", (string)$r->asset_code, DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$rowNum}", $r->kode_asset_class_label);
                    $sheet->setCellValue("C{$rowNum}", $r->description);
                    $sheet->setCellValue("D{$rowNum}", $r->kode_location_label);
                    $sheet->setCellValue("E{$rowNum}", $r->asset_owner_label);
                    $sheet->setCellValue("F{$rowNum}", $r->asset_user_label);
                    $sheet->setCellValue("G{$rowNum}", $r->asset_maintenance_label);
                    $sheet->setCellValue("H{$rowNum}", $r->kode_status_label);

                    // numeric columns
                    $sheet->setCellValue("I{$rowNum}", (float)$r->total);
                    $sheet->setCellValue("J{$rowNum}", (float)$r->last_accumulated_depr);
                    $sheet->setCellValue("K{$rowNum}", (float)$r->last_net_book_value);

                    $rowNum++;
                }
            });

            // Number formats
            $lastRow = max(2, $rowNum - 1);
            $sheet->getStyle("I2:K{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            // Autosize columns
            foreach (range('A', 'K') as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }

            // Output
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            // cleanup
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            'Pragma' => 'public',
        ]);
    }
    public function projectAssetsExportExcel(Request $request, AssetProject $project)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);
        abort_if($project->deleted_at, 404);

        $q = DB::table('asset_project_assets as pa')
            ->join('assets as a', 'a.uuid', '=', 'pa.asset_uuid')
            ->leftJoin('master_asset_class as mac', 'mac.kode', '=', 'a.kode_asset_class')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('assets_value as v', 'v.asset_uuid', '=', 'a.uuid')
            ->where('pa.project_uuid', $project->uuid)
            ->whereNull('a.deleted_at')
            ->orderBy('a.asset_code')
            ->selectRaw("
            a.asset_code,
            CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS asset_class,
            a.description,
            CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS location,
            CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS status,
            COALESCE(v.total, 0) as total,
            pa.created_at as assigned_at,
            pa.stock_opname_done
        ");

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $project->name);
        $filename = 'stockopname_project_' . $safeName . '_' . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($q) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Project Assets');

            $headers = [
                'Asset Code',
                'Asset Class',
                'Description',
                'Location',
                'Status',
                'Total',
                'Assigned At',
                'Stock Opname',
            ];

            foreach ($headers as $i => $h) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue($col . '1', $h);
            }

            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->freezePane('A2');

            $rowNum = 2;

            $q->chunk(1000, function ($rows) use (&$rowNum, $sheet) {
                foreach ($rows as $r) {
                    $sheet->setCellValueExplicit("A{$rowNum}", (string) $r->asset_code, DataType::TYPE_STRING);
                    $sheet->setCellValue("B{$rowNum}", (string) $r->asset_class);
                    $sheet->setCellValue("C{$rowNum}", (string) $r->description);
                    $sheet->setCellValue("D{$rowNum}", (string) $r->location);
                    $sheet->setCellValue("E{$rowNum}", (string) $r->status);
                    $sheet->setCellValue("F{$rowNum}", (float) $r->total);
                    $sheet->setCellValue("G{$rowNum}", (string) $r->assigned_at);
                    $sheet->setCellValue("H{$rowNum}", ((int)$r->stock_opname_done === 1) ? '✓' : '');
                    $rowNum++;
                }
            });

            $lastRow = max(2, $rowNum - 1);

            $sheet->getStyle("F2:F{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            foreach (range('A', 'H') as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
