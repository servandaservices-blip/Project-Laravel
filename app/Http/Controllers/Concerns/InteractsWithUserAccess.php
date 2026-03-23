<?php

namespace App\Http\Controllers\Concerns;

trait InteractsWithUserAccess
{
    protected function accessibleCompanies(array $availableCompanies, string $default = 'servanda'): array
    {
        $user = auth()->user();

        if (! $user) {
            return $availableCompanies;
        }

        $allowed = array_values(array_filter(
            $availableCompanies,
            fn (string $company) => $user->canAccessCompany($company)
        ));

        if ($allowed === []) {
            return in_array($default, $availableCompanies, true) ? [$default] : [$availableCompanies[0]];
        }

        return $allowed;
    }

    protected function resolveAccessibleCompany(?string $company, array $availableCompanies, string $default = 'servanda'): string
    {
        $allowed = $this->accessibleCompanies($availableCompanies, $default);

        if (in_array($company, $allowed, true)) {
            return $company;
        }

        return in_array($default, $allowed, true) ? $default : $allowed[0];
    }

    protected function forcedDivisionByRole(): ?string
    {
        return auth()->user()?->forcedDivision();
    }

    protected function resolveRoleBasedDivision(?string $division, string $companyKey): ?string
    {
        if ($companyKey !== 'servanda') {
            return null;
        }

        $forcedDivision = $this->forcedDivisionByRole();

        if ($forcedDivision !== null) {
            return $forcedDivision;
        }

        $allowed = auth()->user()?->allowedDivisions() ?? ['Security', 'Cleaning'];

        return in_array($division, $allowed, true) ? $division : null;
    }
}
