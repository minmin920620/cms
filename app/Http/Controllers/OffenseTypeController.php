<?php

namespace App\Http\Controllers;

use App\Models\CrimeType;
use App\Models\OffenseType;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OffenseTypeController extends Controller
{
    public function index(): View
    {
        $offenseTypes = OffenseType::with('crimeType')->orderBy('name')->paginate(15);

        return view('offense-types.index', compact('offenseTypes'));
    }

    public function create(): View
    {
        $crimeTypes = CrimeType::crimeTypes();

        return view('offense-types.create', compact('crimeTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:500', 'unique:offense_types,name'],
            'crime_type_id' => ['nullable', Rule::exists('crime_types', 'id')->where('is_active', true)],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $offenseType = OffenseType::create($validated);
        Audit::log('offense_type.created', $offenseType, [], $offenseType->only([
            'id', 'name', 'crime_type_id', 'is_active',
        ]));

        return redirect()->route('offense-types.index')->with('success', 'Offense type added successfully.');
    }

    public function edit(OffenseType $offenseType): View
    {
        $crimeTypes = CrimeType::crimeTypes($offenseType->crime_type_id);

        return view('offense-types.edit', compact('offenseType', 'crimeTypes'));
    }

    public function update(Request $request, OffenseType $offenseType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:500', Rule::unique('offense_types', 'name')->ignore($offenseType)],
            'crime_type_id' => ['nullable', Rule::exists('crime_types', 'id')],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $offenseType->only(array_keys($validated));
        $offenseType->update($validated);
        $changes = $offenseType->getChanges();

        if ($changes) {
            Audit::log('offense_type.updated', $offenseType, array_intersect_key($oldValues, $changes), $changes);
        }

        return redirect()->route('offense-types.index')->with('success', 'Offense type updated successfully.');
    }

    public function destroy(OffenseType $offenseType): RedirectResponse
    {
        $oldValues = $offenseType->only(['id', 'name', 'crime_type_id', 'is_active']);
        $offenseType->update(['is_active' => false]);
        Audit::log('offense_type.archived', $offenseType, $oldValues, $offenseType->only([
            'id', 'name', 'crime_type_id', 'is_active',
        ]));

        return redirect()->route('offense-types.index')->with('success', 'Offense type archived successfully.');
    }
}
