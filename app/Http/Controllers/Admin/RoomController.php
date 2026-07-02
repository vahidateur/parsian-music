<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::orderBy('name')->paginate(20);
        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:rooms'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        Room::create($validated + ['is_active' => true]);

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_created_successfully'));
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:rooms,name,' . $room->id],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $room->update($validated);

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_updated_successfully'));
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_deleted_successfully'));
    }

    public function toggle(Room $room): RedirectResponse
    {
        $room->update(['is_active' => !$room->is_active]);

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_updated_successfully'));
    }
}
