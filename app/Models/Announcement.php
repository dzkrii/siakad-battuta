<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'for_student',
        'for_teacher',
        'published_at',
        'expired_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'for_student' => 'boolean',
        'for_teacher' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Get the user who created this announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active announcements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });
    }

    /**
     * Scope a query to only include announcements for students.
     */
    public function scopeForStudents($query)
    {
        return $query->where('for_student', true);
    }

    /**
     * Scope a query to only include announcements for teachers.
     */
    public function scopeForTeachers($query)
    {
        return $query->where('for_teacher', true);
    }
}
