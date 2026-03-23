<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const USER_POSITION_TABLE = 'faizal.user_positions';

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $division = trim((string) $request->query('division', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($division === 'Cleaning', fn ($query) => $query->where('division', 'Cleaning'))
            ->when($division === 'Security', fn ($query) => $query->where('division', 'Security'))
            ->when($division === '-', function ($query) {
                $query->where(function ($builder) {
                    $builder
                        ->whereNull('division')
                        ->orWhereRaw("TRIM(COALESCE(division, '')) = ''")
                        ->orWhere('division', '-');
                });
            })
            ->orderBy('username')
            ->get();

        $positionOptions = DB::table(self::USER_POSITION_TABLE)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return view('dashboard.users', [
            'users' => $users,
            'search' => $search,
            'selectedDivisionFilter' => $division,
            'totalUsers' => $users->count(),
            'companyOptions' => ['Servanda', 'Gabe', 'Salus'],
            'divisionOptions' => ['Cleaning', 'Security'],
            'statusOptions' => ['Aktif', 'Tidak Aktif'],
            'positionOptions' => $positionOptions,
            'ruleOptions' => ['Cleaning', 'Security', 'Cleaning & Security', 'Administrator'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'division' => ['nullable', Rule::in(['Cleaning', 'Security'])],
            'company_access' => ['nullable', 'array'],
            'company_access.*' => [Rule::in(['Servanda', 'Gabe', 'Salus'])],
            'position' => ['nullable', 'array'],
            'position.*' => ['string', 'max:150'],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
            'rules' => ['required', Rule::in(['Cleaning', 'Security', 'Cleaning & Security', 'Administrator'])],
        ]);

        $division = $validated['division'] ?? null;

        if (in_array($validated['rules'], ['Cleaning', 'Security'], true)) {
            $division = $validated['rules'];
        }

        User::query()->create([
            'name' => $this->nullableTrimmed($validated['name'] ?? null),
            'username' => trim((string) $validated['username']),
            'division' => $division,
            'company_access' => array_values($validated['company_access'] ?? []),
            'position' => array_values($validated['position'] ?? []),
            'status' => $validated['status'],
            'rules' => [$validated['rules']],
            'password' => Hash::make('Servanda123'),
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'division' => ['nullable', Rule::in(['Cleaning', 'Security'])],
            'company_access' => ['nullable', 'array'],
            'company_access.*' => [Rule::in(['Servanda', 'Gabe', 'Salus'])],
            'position' => ['nullable', 'array'],
            'position.*' => ['string', 'max:150'],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
            'rules' => ['required', Rule::in(['Cleaning', 'Security', 'Cleaning & Security', 'Administrator'])],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $division = $validated['division'] ?? null;

        if (in_array($validated['rules'], ['Cleaning', 'Security'], true)) {
            $division = $validated['rules'];
        }

        $payload = [
            'name' => $this->nullableTrimmed($validated['name'] ?? null),
            'username' => trim((string) $validated['username']),
            'division' => $division,
            'company_access' => array_values($validated['company_access'] ?? []),
            'position' => array_values($validated['position'] ?? []),
            'status' => $validated['status'],
            'rules' => [$validated['rules']],
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        User::query()->whereKey($id)->delete();

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(int $id)
    {
        $user = User::query()->findOrFail($id);

        $user->update([
            'password' => Hash::make('Servanda123'),
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Password user ' . $user->username . ' berhasil direset menjadi Servanda123');
    }

    public function updateOwnProfile(Request $request)
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $validated = $request->validateWithBag('profileUpdate', [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
        ]);

        $user->update([
            'username' => trim((string) $validated['username']),
        ]);

        return back()->with('profile_success', 'Profil berhasil diperbarui.');
    }

    private function nullableTrimmed(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function updateOwnPassword(Request $request)
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (! $user || ! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ], 'passwordUpdate');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('password_success', 'Password berhasil diperbarui.');
    }
}
