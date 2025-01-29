<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveAcademicYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!activeAcademicYear()) {
            if (auth()->user()->hasRole('Admin')) {
                flashMessage('Tidak ada tahun ajaran yang aktif. Silahkan tambahkan terlebih dahulu', 'error');
                return to_route('admin.academic-years.index');
            } else if (auth()->user()->hasRole('Operator')) {
                flashMessage('Tidak ada tahun ajaran yang aktif. Harap hubungi admin', 'error');
                return to_route('operators.dashboard');
            } else if (auth()->user()->hasRole('Student')) {
                flashMessage('Tidak ada tahun ajaran yang aktif. Mohon tunggu sampai tahun ajaran baru aktif', 'error');
                return to_route('students.dashboard');
            }
        }
        return $next($request);
    }
}
