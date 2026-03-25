<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'username',
        'division',
        'company_access',
        'position',
        'status',
        'rules',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'company_access' => 'array',
        'position' => 'array',
        'rules' => 'array',
    ];

    public function getAccessRoleAttribute(): ?string
    {
        $rules = $this->rules;

        if (is_array($rules)) {
            $role = collect($rules)
                ->map(fn ($value) => trim((string) $value))
                ->first(fn ($value) => $value !== '');

            return $role ?: null;
        }

        $role = trim((string) $rules);

        return $role !== '' ? $role : null;
    }

    public function isAdministrator(): bool
    {
        return $this->access_role === 'Administrator';
    }

    public function isTargetAccessibleRole(): bool
    {
        return in_array($this->access_role, [
            'Administrator',
            'Cleaning - Costcontrol',
            'Security - Costcontrol',
        ], true);
    }

    public function canAccessTargetMenu(): bool
    {
        return $this->isTargetAccessibleRole();
    }

    public function forcedDivision(): ?string
    {
        return match ($this->access_role) {
            'Cleaning' => 'Cleaning',
            'Security' => 'Security',
            'Cleaning - Costcontrol' => 'Cleaning',
            'Security - Costcontrol' => 'Security',
            default => null,
        };
    }

    public function allowedDivisions(): array
    {
        return match ($this->access_role) {
            'Cleaning' => ['Cleaning'],
            'Security' => ['Security'],
            'Cleaning - Costcontrol' => ['Cleaning'],
            'Security - Costcontrol' => ['Security'],
            'Cleaning & Security' => ['Cleaning', 'Security'],
            default => ['Security', 'Cleaning'],
        };
    }

    public function allowedCompanies(): array
    {
        $companies = collect($this->company_access ?? [])
            ->map(fn ($company) => strtolower(trim((string) $company)))
            ->filter(fn ($company) => $company !== '')
            ->unique()
            ->values()
            ->all();

        return $companies;
    }

    public function canAccessCompany(?string $company): bool
    {
        $company = strtolower(trim((string) $company));

        if ($company === '' || $this->isAdministrator()) {
            return true;
        }

        $allowedCompanies = $this->allowedCompanies();

        if ($allowedCompanies === []) {
            return true;
        }

        return in_array($company, $allowedCompanies, true);
    }

    public function areaManagerScopeName(): ?string
    {
        if ($this->isAdministrator() || ! $this->isAreaManagerRole()) {
            return null;
        }

        $positions = collect(is_array($this->position) ? $this->position : [$this->position])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        $hasManagerRole = $positions->contains(function (string $value) {
            $normalized = Str::lower($value);

            return Str::contains($normalized, ['area manager', 'area commander', '(am)', '(ac)']);
        });

        if (! $hasManagerRole) {
            return null;
        }

        $name = trim((string) $this->name);

        return $name !== '' ? $name : null;
    }

    public function operationManagerScopeName(): ?string
    {
        if ($this->isAdministrator() || ! $this->isOperationManagerRole()) {
            return null;
        }

        $name = trim((string) $this->name);

        return $name !== '' ? $name : null;
    }

    public function isAreaManagerRole(): bool
    {
        return $this->matchesPositionKeywords(['area manager', 'area commander', '(am)', '(ac)']);
    }

    public function isOperationManagerRole(): bool
    {
        return $this->matchesPositionKeywords([
            'operation manager',
            'operation commander',
            'senior operation manager',
            'senior operation commander',
            '(om)',
            '(oc)',
            '(som)',
            '(soc)',
        ]);
    }

    private function matchesPositionKeywords(array $keywords): bool
    {
        $positions = collect(is_array($this->position) ? $this->position : [$this->position])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        return $positions->contains(function (string $value) use ($keywords) {
            $normalized = Str::lower($value);

            foreach ($keywords as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    return true;
                }
            }

            return false;
        });
    }
}
