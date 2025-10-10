<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'asset_uuid'  => ['required','uuid','exists:assets,uuid'],
            'type'        => ['required', Rule::in(['owner','user','maintenance','status','location'])],
            'after'       => ['required','array'],
            'note'        => ['nullable','string','max:1000'],
            'kode_status' => ['nullable'],
        ];
    }
}
