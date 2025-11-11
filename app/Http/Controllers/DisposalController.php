<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterStatus;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class DisposalController extends Controller
{
    protected function canReadDisposal(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('DISPOSAL', 'R');
    }

    public function index()
    {
        $workflows = MasterStatus::query()
            ->where('type', 'Transfer')
            ->orderBy('kode')
            ->get(['kode', 'name']);
        return view('disposal.disposal', compact('workflows'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'C'), 403);
        $data = $request->validate([
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'file'          => [
                'nullable',
                'file',
                'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt',
                'max:20480',
            ],
        ]);
        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')
            ->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)
            ->where(function ($q) {
                $q->where('type', 'Asset')->orWhereNull('type');
            })
            ->exists();

        abort_unless($ok, 422, 'Target disposal status not valid.');

        $now    = Carbon::now();
        $prefix = 'DSP' . $now->format('ym');

        $last = Disposal::where('disposal_code', 'like', $prefix . '%')
            ->orderBy('disposal_code', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->disposal_code, -4);
            $seq     = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $uuid = (string) Str::uuid();

        [$path, $orig, $mime, $size] = $this->saveUpload(
            $request->file('file'),
            $asset,
            $code,
            null
        );

        $row = Disposal::create([
            'uuid'            => $uuid,
            'asset_uuid'      => $asset->uuid,
            'disposal_code'   => $code,
            'target_status'   => $target,
            'kode_status'     => 'APR',
            'note'            => $data['note'] ?? null,
            'file_path'       => $path,
            'pic_request_uid' => $uid,
            'file_name'       => $orig,
            'file_mime'       => $mime,
            'file_size'       => $size,
            'before_status'   => $asset->kode_status,
        ]);

        return response()->json([
            'ok'   => true,
            'id'   => $row->uuid,
            'code' => $row->disposal_code,
        ]);
    }

    public function show(string $uuid)
    {
        $d = Disposal::with(['asset'])->findOrFail($uuid);
        return response()->json([
            'uuid' => $d->uuid,
            'asset_uuid' => $d->asset_uuid,
            'asset_code' => $d->asset?->asset_code,
            'note' => $d->note,
            'target_status' => $d->target_status,
            'kode_status' => $d->kode_status,
            'file_url'    => $d->file_url,
            'file_name'    => $d->file_name,
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'U'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Only pending requests can be edited.');

        $data = $request->validate([
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'remove_file'   => ['nullable', Rule::in(['0', '1'])],
            'file'          => ['nullable', 'file', 'max:10240'],
        ]);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)->where(function ($q) {
            $q->where('type', 'Asset')->orWhereNull('type');
        })->exists();
        abort_unless($ok, 422, 'Target disposal status not valid.');

        $d->asset_uuid    = $data['asset_uuid'];
        $d->note          = $data['note'] ?? null;
        $d->target_status = $target;
        $asset = Assets::with(['assignment'])->findOrFail($data['asset_uuid']);

        // file handling
        if (($data['remove_file'] ?? '0') === '1') {
            if ($d->file_path) Storage::disk('public')->delete($d->file_path);
            $d->file_path = null;
        }
        if ($request->hasFile('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $d->disposal_code, $d);
            $file_path = $path;
            $file_name = $orig;
            $file_mime = $mime;
            $file_size = $size;
        }
        $d->fill([
            'note'        => $data['note'] ?? null,
            'file_path'   => $file_path ?? $d->file_path,
            'file_name'   => $file_name ?? $d->file_name,
            'file_mime'   => $file_mime ?? $d->file_mime,
            'file_size'   => $file_size ?? $d->file_size,
        ])->save();


        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Already processed.');

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($d, $uid) {
            $d->update([
                'kode_status'     => 'ACC',
                'pic_approve_uid' => $uid,
            ]);

            Assets::where('uuid', $d->asset_uuid)->update([
                'kode_status' => $d->target_status,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Already processed.');

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $d->update([
            'kode_status'     => 'REJ',
            'pic_approve_uid' => $uid,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'D'), 403);
        $d = Disposal::findOrFail($uuid);
        $d->delete();
        return response()->json(['ok' => true]);
    }

    public function datatable(Request $r, string $assetUuid)
    {
        if (!$this->canReadDisposal()) {
            return DataTables::of(collect())->toJson();
        }
        $q = Disposal::query()
            ->where('asset_uuid', $assetUuid)
            ->with(['status', 'target'])
            ->orderByDesc('created_at');
        $user = $r->user();
        $canEdit   = $user && $user->hasAction('DISPOSAL', 'U');
        $canDelete = $user && $user->hasAction('DISPOSAL', 'D');
        $canApr    = $user && $user->hasAction('DISPOSAL', 'APR');

        return DataTables::of($q)
            ->addColumn('workflow_label', fn($t) => $t->status ? $t->kode_status . ' - ' . $t->status->name : $t->kode_status)
            ->addColumn('target_label',   fn($t) => $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status)
            ->addColumn('file', function ($r) {
                if (!$r->file_path) return '';

                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';

                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            })
            ->addColumn('actions', function ($t) use ($canEdit, $canDelete, $canApr) {
                $pending = $t->kode_status === 'APR';
                $btns = '<div class="btn-group btn-group-sm">';
                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-ds-edit" data-id="' . $t->uuid . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-ds-approve" data-id="' . $t->uuid . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-ds-reject" data-id="' . $t->uuid . '">Reject</button>';
                    }
                }
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-ds-delete" data-id="' . $t->uuid . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    public function datatable_all(Request $request)
    {
        if (!$this->canReadDisposal()) {
            return DataTables::of(collect())->toJson();
        }

        $status      = trim((string) $request->input('status', ''));
        $requester   = trim((string) $request->input('requester', ''));
        $updatedFrom = $request->input('updated_from');
        $updatedTo   = $request->input('updated_to');
        $createdFrom = $request->input('created_from');
        $createdTo   = $request->input('created_to');
        $assetQ      = trim((string) $request->input('asset_q', ''));

        $q = Disposal::query()
            ->with(['status', 'target', 'asset'])
            ->orderByDesc('created_at');

        // old workflow param (keep for safety, but status takes priority)
        if ($request->filled('workflow') && $status === '') {
            $q->where('kode_status', $request->string('workflow'));
        }

        if ($status !== '') {
            $q->where('kode_status', $status);
        }

        if ($requester !== '') {
            $q->where('pic_request_uid', $requester);
        }

        if ($updatedFrom) {
            $q->whereDate('updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('updated_at', '<=', $updatedTo);
        }

        if ($createdFrom) {
            $q->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('created_at', '<=', $createdTo);
        }

        if ($assetQ !== '') {
            $q->whereHas('asset', function ($w) use ($assetQ) {
                $w->where('asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('description', 'ilike', "%{$assetQ}%");
            });
        }

        $user = $request->user();
        $canEdit   = $user && $user->hasAction('DISPOSAL', 'U');
        $canDelete = $user && $user->hasAction('DISPOSAL', 'D');
        $canApr    = $user && $user->hasAction('DISPOSAL', 'APR');

        return DataTables::of($q)
            ->addColumn('asset_label', fn($t) => $t->asset ? $t->asset->asset_code : $t->asset_uuid)
            ->addColumn('workflow_label', fn($t) => $t->status ? $t->kode_status . ' - ' . $t->status->name : $t->kode_status)
            ->addColumn('target_label',   fn($t) => $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status)
            ->addColumn('file', function ($r) {
                if (!$r->file_path) return '';
                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';
                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            })
            ->addColumn('actions', function ($t) use ($canEdit, $canDelete, $canApr) {
                $pending = $t->kode_status === 'APR';
                $btns = '<div class="btn-group btn-group-sm">';
                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-ds-edit" data-id="' . $t->uuid . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-ds-approve" data-id="' . $t->uuid . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-ds-reject" data-id="' . $t->uuid . '">Reject</button>';
                    }
                }
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-ds-delete" data-id="' . $t->uuid . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['file', 'actions'])
            ->toJson();
    }


    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?Disposal $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'disposals/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);
        $options = ['disk' => $disk];
        $options['visibility'] = 'public';

        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }
}
