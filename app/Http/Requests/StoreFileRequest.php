<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = User::query()->first(); //TODO replace to auth()->user()
        return $user->groups()->where('groups.id', $this->group_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'file' => ['required', 'file'],
            'status' => ['boolean'],
            'user_id' => [Rule::requiredIf(fn() => $this->status == 1), 'exists:users,id']
        ];
    }
}
