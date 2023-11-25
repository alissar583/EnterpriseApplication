<?php

namespace App\Http\Requests;

use App\Enums\FileStatusEnum;
use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckOutInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(FileStatusEnum::class)],
            'ids' => ['required', 'exists:files,id', 'array', $this->type == FileStatusEnum::OUT->value ? 'size:1' : 'array'],
            'ids.*' => ['distinct'],
            'file' => [Rule::requiredIf(fn () => $this->type == FileStatusEnum::OUT->value), 'file']
        ];
    }
}
