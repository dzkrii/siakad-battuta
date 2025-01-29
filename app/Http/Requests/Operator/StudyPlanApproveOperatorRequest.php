<?php

namespace App\Http\Requests\Operator;

use App\Enums\StudyPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StudyPlanApproveOperatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('Operator') || auth()->user()->hasRole('Admin'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                new Enum(StudyPlanStatus::class),
            ],
            'notes' => [
                'required_if:status,Reject',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'Status',
            'notes' => 'Catatan',
        ];
    }
}
