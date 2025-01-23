<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->student?->user)],
            'password' => Rule::when($this->routeIs('admin.students.store'), [
                'required',
                'min:6',
                'max:255',
            ]),
            Rule::when($this->routeIs('admin.students.update'), [
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
            'fee_group_id' => [
                'required',
                'exists:fee_groups,id'
            ],
            'classroom_id' => [
                'required',
                'exists:classrooms,id'
            ],
            'student_number' => [
                'required',
                'string',
                'max:13'
            ],
            'semester' => [
                'required',
                'integer'
            ],
            'batch' => [
                'required',
                'integer'
            ],
            'avatar' => [
                'nullable',
                'mimes:png,jpg,jpeg,webp',
            ],
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
            'fee_group_id' => 'Golongan UKT',
            'student_number' => 'Nomor Induk Mahasiswa',
            'semester' => 'Semester',
            'batch' => 'Angkatan',
            'classroom_id' => 'Kelas',
        ];
    }
}
