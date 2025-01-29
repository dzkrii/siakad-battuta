<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class CourseClassroomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Teacher');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendances.*.status' => [
                'nullable',
                'boolean',
            ],
            'grades.*.grade' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'attendances.*.status' => 'Kehadiran',
            'grades.*.grade' => 'Nilai',
        ];
    }
}
