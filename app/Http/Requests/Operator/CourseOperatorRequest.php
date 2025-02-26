<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseOperatorRequest extends FormRequest
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
            // 'code' => [
            //     'required',
            //     'string',
            //     'max:255',
            //     Rule::unique('courses')->ignore($this->course?->code),
            // ],
            'teacher_id' => [
                'required',
                'exists:teachers,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'credit' => [
                'required',
                'integer',
            ],
            'semester' => [
                'required',
                'integer',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'kode_matkul' => 'Kode',
            'teacher_id' => 'Dosen',
            'name' => 'Nama',
            'credit' => 'SKS',
            'semester' => 'Semester',
        ];
    }
}
