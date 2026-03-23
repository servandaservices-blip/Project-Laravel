<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

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

    public function forcedDivision(): ?string
    {
        return match ($this->access_role) {
            'Cleaning' => 'Cleaning',
            'Security' => 'Security',
            default => null,
        };
    }

    public function allowedDivisions(): array
    {
        return match ($this->access_role) {
            'Cleaning' => ['Cleaning'],
            'Security' => ['Security'],
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
}
