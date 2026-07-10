<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $section = $this->input('_section', 'info');

        if ($section === 'avatar') {
            return [
                'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ];
        }

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20', Rule::unique(User::class, 'phone')->ignore($this->user()->id)],
            'email'     => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'locale'    => ['nullable', 'string', Rule::in(['fa', 'en'])],
            'timezone'  => ['nullable', 'string', 'timezone'],
        ];
    }
}
