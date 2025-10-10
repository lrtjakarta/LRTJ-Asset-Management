<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferDecisionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'decision' => ['required','in:approve,reject'],
            'note'     => ['nullable','string','max:1000'],
        ];
    }
}
