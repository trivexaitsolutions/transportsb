<?php

namespace App\Http\Controllers;

use App\Models\SoNumberSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SoNumberSeriesController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $items = SoNumberSeries::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('prefix', 'like', '%'.$search.'%')
                        ->orWhere('suffix', 'like', '%'.$search.'%')
                        ->orWhere('series_type', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw("CASE WHEN series_type = 'gst' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('masters.so-number-series', compact('items', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        SoNumberSeries::create($data);

        return back()->with('success', 'SO Number Series added successfully.');
    }

    public function update(Request $request, SoNumberSeries $series): RedirectResponse
    {
        $data = $this->validated($request, $series->id);
        $series->update($data);

        return back()->with('success', 'SO Number Series updated successfully.');
    }

    public function destroy(SoNumberSeries $series): RedirectResponse
    {
        $series->delete();

        return back()->with('success', 'SO Number Series deleted successfully.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'series_type' => [
                'required',
                Rule::in(['gst', 'non_gst']),
                Rule::unique('so_number_series', 'series_type')->ignore($ignoreId),
            ],
            'prefix' => ['nullable', 'string', 'max:100'],
            'start_number' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'next_number' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'number_digits' => ['required', 'integer', 'min:1', 'max:12'],
            'suffix' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $start = (int) $validated['start_number'];
        $next = (int) $validated['next_number'];

        if ($next < $start) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'next_number' => 'Next Number cannot be smaller than Start Number.',
            ]);
        }

        return [
            'series_type' => $validated['series_type'],
            'prefix' => trim((string) ($validated['prefix'] ?? '')),
            'start_number' => $start,
            'next_number' => $next,
            'number_digits' => (int) $validated['number_digits'],
            'suffix' => filled($validated['suffix'] ?? null) ? trim((string) $validated['suffix']) : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
