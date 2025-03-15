<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\Admin\ScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPlanScheduleStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'student' => $this->whenLoaded('student', [
                'id' => $this->student?->id,
                'name' => $this->student?->user?->name,
                'student_number' => $this->student?->student_number,
                'semester' => $this->student?->semester,
            ]),
            'academicYear' => $this->whenLoaded('academicYear', [
                'id' => $this->academicYear?->id,
                'name' => $this->academicYear?->name,
                'semester' => $this->academicYear?->semester,
            ]),
            'schedules' => $this->whenLoaded('schedules', ScheduleResource::collection($this->schedules)),
        ];
    }
}
