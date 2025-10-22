<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:new,existing'],

            // existing-mode: require a valid parent
            // 'parent_uuid' => ['required_if:mode,existing', 'uuid', 'exists:assets,uuid,deleted_at,NULL'],

            // identity
            'description'             => ['required', 'string', 'max:500'],

            // masters on assets
            'kode_sumber'             => ['required', 'string', 'max:50'],
            'kode_location'           => ['required', 'string', 'max:50'],
            'kode_asset_class'        => ['nullable', 'string', 'max:50'],
            'kode_status'             => ['nullable', 'string', 'max:50'],

            // identifiers
            'asset_number_maximo'       => ['nullable', 'string', 'max:120'],
            'asset_number_dynamic_365'  => ['nullable', 'string', 'max:120'],
            'asset_number_internal'     => ['nullable', 'string', 'max:120'],
            'alias'             => ['nullable', 'string', 'max:500'],

            // classification
            'kode_asset_transaction'  => ['nullable', 'string', 'max:50'],
            'kode_asset_type'         => ['nullable', 'string', 'max:50'],
            'kode_category'           => ['nullable', 'string', 'max:50'],
            'kode_category_2'         => ['nullable', 'string', 'max:50'],
            'kode_sub_category'       => ['nullable', 'string', 'max:10'],

            // assignment
            'asset_owner'          => ['required', 'string', 'max:50'],
            'asset_user'           => ['required', 'string', 'max:50'],
            'asset_maintenance'    => ['required', 'string', 'max:50'],

            // value
            'price'                 => ['required', 'numeric', 'min:0'],
            'quantity'              => ['required', 'numeric', 'min:0'],
            'is_pajak'              => ['nullable', 'boolean'],
            'kode_uom'              => ['nullable', 'string', 'max:50'],
            'useful_life_month'     => ['nullable', 'integer', 'min:0', 'max:1200'],

            // QR/RFID
            'rfid_epc'              => ['nullable', 'string', 'max:128'],

            // document
            'no_po_perjanjian_spk' => ['nullable', 'string', 'max:120'],
            'nota_referensi'       => ['required', 'string', 'max:120'],
            'no_document'          => ['required', 'string', 'max:120'],
        ];
    }
}
