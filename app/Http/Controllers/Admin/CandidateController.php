<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Party;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $partyId = $request->query('party_id');

        $rows = Candidate::query()
            ->with('party:id,code,name')
            ->when($partyId, fn ($query) => $query->where('party_id', $partyId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->orderBy('party_id')
            ->orderByRaw('COALESCE(number, 999999) asc')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin/candidates/index', [
            'q' => $q,
            'partyId' => $partyId,
            'parties' => Party::query()->orderBy('code')->get(['id', 'code', 'name']),
            'rows' => $rows,
        ]);
    }

    public function create()
    {
        return view('admin/candidates/create', [
            'parties' => Party::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_id' => ['required', 'exists:parties,id'],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);

        Candidate::create([
            'party_id' => $validated['party_id'],
            'name' => trim($validated['name']),
            'number' => $validated['number'] ?? null,
        ]);

        return redirect()
            ->route('admin.candidates.index', ['party_id' => $validated['party_id']])
            ->with('status', 'Calon berhasil ditambahkan.');
    }

    public function edit(Candidate $candidate)
    {
        return view('admin/candidates/edit', [
            'candidate' => $candidate,
            'parties' => Party::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'party_id' => ['required', 'exists:parties,id'],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['nullable', 'integer', 'min:1'],
        ]);

        $candidate->update([
            'party_id' => $validated['party_id'],
            'name' => trim($validated['name']),
            'number' => $validated['number'] ?? null,
        ]);

        return redirect()
            ->route('admin.candidates.index', ['party_id' => $validated['party_id']])
            ->with('status', 'Calon berhasil diperbarui.');
    }

    public function destroy(Candidate $candidate)
    {
        $partyId = $candidate->party_id;
        $candidate->delete();

        return redirect()
            ->route('admin.candidates.index', ['party_id' => $partyId])
            ->with('status', 'Calon dihapus.');
    }
}
