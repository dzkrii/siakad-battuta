<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperatorRequest extends FormRequest
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
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->operator?->user),
            ],
            'password' => Rule::when($this->routeIs('admin.operators.store'), [
                'required',
                'min:6',
                'max:255',
            ]),
            Rule::when($this->routeIs('admin.operators.update'), [
                'nullable',
                'min:6',
                'max:255',
            ]),
            'faculty_id' => [
                'required',
                'exists:faculties,id'
            ],
            'department_id' => [
                'required',
                'exists:departments,id'
            ],
            'avatar' => ['nullable', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'employee_number' => ['required', 'min:3', 'max:255', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'faculty_id' => 'Fakultas',
            'department_id' => 'Program Studi',
            'avatar' => 'Avatar',
            'employee_number' => 'Nomor Induk Karyawan',
        ];
    }
}
