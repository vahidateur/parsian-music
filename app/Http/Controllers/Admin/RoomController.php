<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\RoomAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use App\Services\Lists\RoomListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every action resolves its named RoomPolicy ability through the
 * Authorization_Layer before any input is read or any record is written, so a
 * hidden UI control is never the only protection.
 */
class RoomController extends Controller
{
    public function index(Request $request, RoomListQuery $listQuery): View
    {
        $this->authorize('viewAny', Room::class);

        return view('admin.rooms.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Room::class);

        return view('admin.rooms.create');
    }

    public function store(RoomRequest $request, RoomAction $action): RedirectResponse
    {
        $this->authorize('create', Room::class);

        $action->create($request->validated());

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_created_successfully'));
    }

    public function edit(Room $room): View
    {
        $this->authorize('update', $room);

        return view('admin.rooms.edit', compact('room'));
    }

    public function update(RoomRequest $request, Room $room, RoomAction $action): RedirectResponse
    {
        $this->authorize('update', $room);

        $action->update($room, $request->validated());

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_updated_successfully'));
    }

    public function destroy(Room $room, RoomAction $action): RedirectResponse
    {
        $this->authorize('delete', $room);

        $action->delete($room);

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_deleted_successfully'));
    }

    public function toggle(Room $room, RoomAction $action): RedirectResponse
    {
        $this->authorize('toggle', $room);

        $action->toggle($room);

        return redirect()->route('admin.rooms.index')
            ->with('success', __('admin.room_updated_successfully'));
    }
}
