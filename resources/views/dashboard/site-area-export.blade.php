<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Area Export</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="8">Site Area {{ $companyName }}</th>
            </tr>
            <tr>
                <th>No</th>
                <th>Nama Area</th>
                <th>Division</th>
                <th>Cabang</th>
                <th>Area Manager</th>
                <th>Operation Manager</th>
                <th>Status</th>
                <th>Company</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($siteAreas as $index => $siteArea)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $siteArea->area_name }}</td>
                    <td>{{ $siteArea->division ?: '-' }}</td>
                    <td>{{ $siteArea->branch ?: '-' }}</td>
                    <td>{{ $siteArea->area_manager ?: '-' }}</td>
                    <td>{{ $siteArea->operation_manager ?: '-' }}</td>
                    <td>{{ $siteArea->status ?: '-' }}</td>
                    <td>{{ strtoupper((string) $selectedCompany) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data site area.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
