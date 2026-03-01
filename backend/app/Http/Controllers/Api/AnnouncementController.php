<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        return response()->json(Announcement::orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean'
        ]);

        $announcement = Announcement::create($validated);
        return response()->json($announcement, 201);
    }

    public function show(string $id)
    {
        return response()->json(Announcement::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'type' => 'sometimes|in:info,warning,success',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean'
        ]);

        $announcement->update($validated);
        return response()->json($announcement);
    }

    public function destroy(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}
