<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\InstrumentAction;
use App\Exceptions\RecordInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InstrumentRequest;
use App\Models\Instrument;
use App\Services\Lists\InstrumentListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every action resolves its named InstrumentPolicy ability through the
 * Authorization_Layer before any input is read or any record is written, so a
 * hidden UI control is never the only protection.
 */
class InstrumentController extends Controller
{
    public function index(Request $request, InstrumentListQuery $listQuery): View
    {
        $this->authorize('viewAny', Instrument::class);

        return view('admin.instruments.index', [
            'list' => $listQuery->forInput($request->query(), $request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Instrument::class);

        return view('admin.instruments.create');
    }

    public function store(InstrumentRequest $request, InstrumentAction $action): RedirectResponse
    {
        $this->authorize('create', Instrument::class);

        $action->create($request->validated());

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_created_successfully'));
    }

    public function edit(Instrument $instrument): View
    {
        $this->authorize('update', $instrument);

        return view('admin.instruments.edit', compact('instrument'));
    }

    public function update(InstrumentRequest $request, Instrument $instrument, InstrumentAction $action): RedirectResponse
    {
        $this->authorize('update', $instrument);

        $action->update($instrument, $request->validated());

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_updated_successfully'));
    }

    public function destroy(Instrument $instrument, InstrumentAction $action): RedirectResponse
    {
        $this->authorize('delete', $instrument);

        try {
            $action->delete($instrument);
        } catch (RecordInUseException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_deleted_successfully'));
    }

    public function toggle(Instrument $instrument, InstrumentAction $action): RedirectResponse
    {
        $this->authorize('toggle', $instrument);

        $action->toggle($instrument);

        return back()->with('success', __('admin.instrument_updated_successfully'));
    }
}
