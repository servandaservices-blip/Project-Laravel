<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingPositionController extends Controller
{
    private const TABLE = 'faizal.user_positions';

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $positions = DB::table(self::TABLE)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return view('dashboard.setting-user-positions', [
            'positions' => $positions,
            'search' => $search,
            'totalPositions' => $positions->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::table(self::TABLE)->insert([
            'name' => trim((string) $validated['name']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('settings.positions.index')
            ->with('success', 'Position User berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        DB::table(self::TABLE)
            ->where('id', $id)
            ->update([
                'name' => trim((string) $validated['name']),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('settings.positions.index')
            ->with('success', 'Position User berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        DB::table(self::TABLE)->where('id', $id)->delete();

        return redirect()
            ->route('settings.positions.index')
            ->with('success', 'Position User berhasil dihapus.');
    }
}
