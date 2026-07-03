<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    public function index(): View
    {
        $instruments = Instrument::orderBy('name_fa')->orderBy('name')->get();

        return view('admin.instruments.index', compact('instruments'));
    }

    public function create(): View
    {
        return view('admin.instruments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_fa'   => ['required', 'string', 'max:100', 'unique:instruments,name_fa'],
            'name'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // English name defaults to Persian name if not provided
        $englishName = trim($validated['name'] ?? '') ?: $validated['name_fa'];
        $slug = Str::slug($englishName);

        // Ensure slug uniqueness
        $baseSlug = $slug;
        $counter  = 1;
        while (Instrument::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        Instrument::create([
            'name'      => $englishName,
            'name_fa'   => $validated['name_fa'],
            'slug'      => $slug,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_created_successfully'));
    }

    public function edit(Instrument $instrument): View
    {
        return view('admin.instruments.edit', compact('instrument'));
    }

    public function update(Request $request, Instrument $instrument): RedirectResponse
    {
        $validated = $request->validate([
            'name_fa'   => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('instruments', 'name_fa')->ignore($instrument->id)],
            'name'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $englishName = trim($validated['name'] ?? '') ?: $validated['name_fa'];

        $instrument->update([
            'name'      => $englishName,
            'name_fa'   => $validated['name_fa'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_updated_successfully'));
    }

    public function destroy(Instrument $instrument): RedirectResponse
    {
        // Prevent deletion if instrument is in use
        if ($instrument->enrollments()->exists() || $instrument->teachers()->exists()) {
            return back()->with('error', __('admin.instrument_in_use_error'));
        }

        $instrument->delete();

        return redirect()->route('admin.instruments.index')
            ->with('success', __('admin.instrument_deleted_successfully'));
    }

    public function toggle(Instrument $instrument): RedirectResponse
    {
        $instrument->update(['is_active' => ! $instrument->is_active]);

        return back()->with('success', __('admin.instrument_updated_successfully'));
    }
}
