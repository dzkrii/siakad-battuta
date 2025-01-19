<?php

use App\Models\AcademicYear;

if (!function_exists('flashMessage')) {
    function flashMessage($message, $type = 'success'): void
    {
        session()->flash('message', $message);
        session()->flash('type', $type);
    }
}

if (!function_exists('signatureMidtrans')) {
    function signatureMidtrans($order_id, $status_code, $gross_amount, $server_key): string
    {
        return hash('sha512', $order_id . $status_code . $gross_amount . $server_key);
    }
}

if (!function_exists('activeAcademicYear')) {
    function activeAcademicYear()
    {
        return AcademicYear::query()->where('is_active', true)->first();
    }
}

if (!function_exists('getLetterGrade')) {
    function getLetterGrade($grade): string
    {
        return match (true) {
            $grade >= 80 => 'A',
            $grade >= 75 => 'B+',
            $grade >= 71 => 'B',
            $grade >= 56 => 'C+',
            $grade >= 51 => 'C',
            $grade >= 40 => 'D',
            default => 'E',
        };
    }
}
