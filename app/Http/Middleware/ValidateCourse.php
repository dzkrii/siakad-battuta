<?php

namespace App\Http\Middleware;

use App\Models\Course;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCourse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $course = Course::query()
            ->where('id', $request->course_id)
            ->where('faculty_id', $request->faculty_id)
            ->where('department_id', $request->department_id)
            ->exists();

        if (!$course) {
            flashMessage('Mata kuliah tersebut tidak ada di program studi atau fakultas yang anda pilih', 'error');
            return back();
        }

        return $next($request);
    }
}
