<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
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
            'name' => ['required', 'min:3', 'max:255', 'string'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->teacher?->user)],
            'password' => Rule::when($this->routeIs('admin.teachers.store'), [
                'nullable',
                'min:6',
                'max:255',
            ]),
            Rule::when($this->routeIs('admin.teachers.update'), [
                'nullable',
                'min:6',
                'max:255',
            ]),
            'faculty_id' => ['nullable'],
            'department_id' => ['nullable'],
            'teacher_number' => ['nullable', 'max:255', 'string', Rule::unique('teachers')->ignore($this->teacher),],
            'academic_title' => ['nullable', 'max:255', 'string'],
            'avatar' => ['nullable', 'mimes:png,jpg,jpeg,webp'],
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
            'teacher_number' => 'Nomor Induk Dosen',
            'academic_title' => 'Jabatan Akademik',
            'avatar' => 'Avatar',
        ];
    }
}
