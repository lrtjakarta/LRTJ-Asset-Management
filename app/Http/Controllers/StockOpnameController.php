<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StockOpnameController extends Controller
{
    public function dataByAsset(string $uuid, Request $request)
    {
        $qT = DB::table('assets_transfers as t')
            ->selectRaw("
        t.uuid,
        t.asset_uuid,
        t.transfer_code  as code,
        'transfer'       as source_type,
        t.type           as tf_type,
        COALESCE(t.before->>'value','') as before_label,
        COALESCE(t.after->>'value','')  as after_label,
        t.pic_request_uid,
        t.pic_approve_uid,
        t.note,
        t.file_name,
        t.file_path,
        t.updated_at
    ")
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', 'ACC')
            ->where('t.asset_uuid', $uuid);

        $qD = DB::table('assets_disposals as d')
            ->selectRaw("
        d.uuid,
        d.asset_uuid,
        d.disposal_code  as code,
        'disposal'       as source_type,
        NULL::text       as tf_type,
        COALESCE(d.before_status,'') as before_label,
        COALESCE('DIS','')  as after_label,
        d.pic_request_uid,
        d.pic_approve_uid,
        d.note,
        d.file_name,
        d.file_path,
        d.updated_at
    ")
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', 'ACC')
            ->where('d.asset_uuid', $uuid);

        $union = $qT->unionAll($qD);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($r) {
                $detail = $r->source_type === 'transfer'
                    ? (strtoupper($r->tf_type) . ' • ' . $r->before_label . ' → ' . $r->after_label)
                    : ('DISPOSAL' . ($r->before_label ? ' • ' . $r->before_label . ' → ' . $r->after_label : ''));

                $file = $r->file_path
                    ? '<a href="' . e(Storage::url($r->file_path)) . '" target="_blank">' . e($r->file_name ?: 'File') . '</a>'
                    : '';

                return [
                    'code'            => $r->code,
                    'source'          => $r->source_type === 'transfer' ? 'TRANSFER' : 'DISPOSAL',
                    'type'            => $r->source_type === 'transfer' ? strtoupper($r->tf_type) : 'DISPOSAL',
                    'detail'          => $detail,
                    'note'            => $r->note,
                    'pic_request_uid' => $r->pic_request_uid,
                    'pic_approve_uid' => $r->pic_approve_uid,
                    'file'            => $file,
                    'updated_at'      => $r->updated_at,
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
