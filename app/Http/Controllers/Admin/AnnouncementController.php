<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the announcements.
     */
    public function index(): Response
    {
        $announcements = Announcement::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn($announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'for_student' => $announcement->for_student,
                'for_teacher' => $announcement->for_teacher,
                'published_at' => $announcement->published_at,
                'expired_at' => $announcement->expired_at,
                'is_active' => $announcement->is_active,
                'created_at' => $announcement->created_at,
                'creator' => $announcement->creator ? [
                    'id' => $announcement->creator->id,
                    'name' => $announcement->creator->name,
                ] : null,
            ]);

        return inertia('Admin/Announcements/Index', [
            'page_settings' => [
                'title' => 'Pengumuman',
                'subtitle' => 'Kelola pengumuman untuk mahasiswa dan dosen',
            ],
            'announcements' => [
                'data' => $announcements->items(),
                'links' => [
                    'prev' => $announcements->previousPageUrl(),
                    'next' => $announcements->nextPageUrl(),
                ],
                'meta' => [
                    'links' => $announcements->linkCollection()->toArray(),
                    'current_page' => $announcements->currentPage(),
                    'from' => $announcements->firstItem(),
                    'last_page' => $announcements->lastPage(),
                    'path' => $announcements->path(),
                    'per_page' => $announcements->perPage(),
                    'to' => $announcements->lastItem(),
                    'total' => $announcements->total(),
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(): Response
    {
        return inertia('Admin/Announcements/Create', [
            'page_settings' => [
                'title' => 'Buat Pengumuman',
                'subtitle' => 'Tambahkan pengumuman baru',
            ],
        ]);
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'for_student' => 'required|boolean',
            'for_teacher' => 'required|boolean',
            'published_at' => 'nullable|date',
            'expired_at' => 'nullable|date|after_or_equal:published_at',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();

        Announcement::create($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dibuat');
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Announcement $announcement): Response
    {
        return inertia('Admin/Announcements/Edit', [
            'page_settings' => [
                'title' => 'Edit Pengumuman',
                'subtitle' => 'Perbarui pengumuman yang ada',
            ],
            'announcement' => $announcement,
        ]);
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'for_student' => 'required|boolean',
            'for_teacher' => 'required|boolean',
            'published_at' => 'nullable|date',
            'expired_at' => 'nullable|date|after_or_equal:published_at',
            'is_active' => 'boolean',
        ]);

        $announcement->update($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui');
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus');
    }
}
