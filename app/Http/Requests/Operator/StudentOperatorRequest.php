<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentOperatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Operator');
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
                'max:255',
            ],
            // 'email' => [
            //     'email',
            //     'max:255',
            //     Rule::unique('users')->ignore($this->student?->user),
            // ],
            'password' => Rule::when($this->routeIs('operators.students.store'), [
                'required',
                'min:6',
                'max:255',
            ]),
            Rule::when($this->routeIs('operators.students.update'), [
                'nullable',
                'min:6',
                'max:255',
            ]),
            'student_number' => [
                'required',
                'string',
                'max:255',
            ],
            'batch' => [
                'required',
                'integer',
            ],
            'avatar' => [
                'nullable',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
            'fee_group_id' => [
                'nullable',
                'exists:fee_groups,id',
            ],
            'classroom_id' => [
                'required',
                'exists:classrooms,id',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'student_number' => 'Nomor Induk Mahasiswa',
            'semester' => 'Semester',
            'batch' => 'Tahun Ajaran',
            'avatar' => 'Avatar',
            'fee_group_id' => 'Golongan UKT',
            'classroom_id' => 'Kelas',
        ];
    }
}
