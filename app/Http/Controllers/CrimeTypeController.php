<?php

namespace App\Http\Controllers;

use App\Models\CrimeType;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrimeTypeController extends Controller
{
    public function index(): View
    {
        $crimeTypes = CrimeType::withCount(['crimes', 'offenseTypes'])->orderBy('name')->paginate(15);

        return view('crime-types.index', compact('crimeTypes'));
    }

    public function create(): View
    {
        return view('crime-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:crime_types,name'],
            'color' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $crimeType = CrimeType::create($validated);
        Audit::log('crime_type.created', $crimeType, [], $crimeType->only([
            'id', 'name', 'color', 'is_active',
        ]));

        return redirect()->route('crime-types.index')->with('success', 'Crime type added successfully.');
    }

    public function edit(CrimeType $crimeType): View
    {
        return view('crime-types.edit', compact('crimeType'));
    }

    public function update(Request $request, CrimeType $crimeType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('crime_types', 'name')->ignore($crimeType)],
            'color' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $crimeType->only(array_keys($validated));
        $crimeType->update($validated);
        $changes = $crimeType->getChanges();

        if ($changes) {
            Audit::log('crime_type.updated', $crimeType, array_intersect_key($oldValues, $changes), $changes);
        }

        return redirect()->route('crime-types.index')->with('success', 'Crime type updated successfully.');
    }

    public function destroy(CrimeType $crimeType): RedirectResponse
    {
        $oldValues = $crimeType->only(['id', 'name', 'color', 'is_active']);
        $crimeType->update(['is_active' => false]);
        Audit::log('crime_type.archived', $crimeType, $oldValues, $crimeType->only([
            'id', 'name', 'color', 'is_active',
        ]));

        return redirect()->route('crime-types.index')->with('success', 'Crime type archived successfully.');
    }
}
