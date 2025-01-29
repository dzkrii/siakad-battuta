<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\FeeStudentResource;
use App\Models\Fee;
use Illuminate\Http\Request;
use Inertia\Response;

class FeeStudentController extends Controller
{
    public function __invoke(): Response
    {
        $fee = Fee::query()
            ->where('student_id', auth()->user()->student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', auth()->user()->student->semester)
            ->first();

        $fees = Fee::query()
            ->select(['fees.id', 'fees.fee_code', 'fees.student_id', 'fees.fee_group_id', 'fees.academic_year_id', 'fees.semester', 'fees.status', 'fees.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->where('student_id', auth()->user()->student->id)
            ->with(['feeGroup', 'academicYear'])
            ->paginate(request()->load ?? 10);

        return inertia('Students/Fees/Index', [
            'page_settings' => [
                'title' => 'Pembayaran',
                'subtitle' => 'Menampilkan semua data pembayaran ukt yang tersedia pada Universitas Battuta.',
            ],
            'fee' => $fee,
            'fees' => FeeStudentResource::collection($fees)->additional([
                'meta' => [
                    'has_pages' => $fees->hasPages(),
                ],
            ]),
            'state' => [
                'page' => request()->page ?? 1,
                'search' => request()->search ?? '',
                'load' => 10,
            ],
        ]);
    }
}
