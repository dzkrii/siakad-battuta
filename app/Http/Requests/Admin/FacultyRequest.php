<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required' . 'string' . 'min:3' . 'max:255'
            ],
            'logo' => Rule::when($this->routeIs('admin.faculties.store'), [
                'required',
                'mimes:png,jpg, jpeg, webp',
                'max:2048',
            ]),
        ];
    }
}
