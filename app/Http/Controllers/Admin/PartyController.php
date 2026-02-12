<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Party::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('admin/parties/index', [
            'q' => $q,
            'rows' => $rows,
        ]);
    }

    public function create()
    {
        return view('admin/parties/create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:parties,code'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9a-fA-F]{6}$/'],
        ]);

        $color = trim((string) ($validated['color'] ?? ''));
        if ($color === '') {
            $color = null;
        } elseif (!str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        Party::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'color' => $color,
        ]);

        return redirect()
            ->route('admin.parties.index')
            ->with('status', 'Partai berhasil ditambahkan.');
    }

    public function edit(Party $party)
    {
        return view('admin/parties/edit', [
            'party' => $party,
        ]);
    }

    public function update(Request $request, Party $party)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:parties,code,'.$party->id],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9a-fA-F]{6}$/'],
        ]);

        $color = trim((string) ($validated['color'] ?? ''));
        if ($color === '') {
            $color = null;
        } elseif (!str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        $party->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'color' => $color,
        ]);

        return redirect()
            ->route('admin.parties.index')
            ->with('status', 'Partai berhasil diperbarui.');
    }

    public function destroy(Party $party)
    {
        $party->delete();

        return redirect()
            ->route('admin.parties.index')
            ->with('status', 'Partai dihapus.');
    }
}
