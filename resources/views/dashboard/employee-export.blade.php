<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Export</title>
</head>
<body>
    @php
        $isServanda = ($selectedCompany ?? 'servanda') === 'servanda';
        $contractStatusLabel = function ($endDate) {
            if (blank($endDate)) {
                return '-';
            }

            try {
                $today = \Illuminate\Support\Carbon::today();
                $end = \Illuminate\Support\Carbon::parse($endDate)->startOfDay();
            } catch (\Throwable $e) {
                return '-';
            }

            if ($end->lt($today)) {
                return 'Expired';
            }

            $daysRemaining = $today->diffInDays($end);

            if ($daysRemaining < 30) {
                return 'Menjelang Berakhir (' . $daysRemaining . ' hari)';
            }

            $monthsRemaining = max(1, round($daysRemaining / 30, 1));
            $formattedMonthsRemaining = str_replace('.', ',', rtrim(rtrim(number_format($monthsRemaining, 1, '.', ''), '0'), '.'));

            if ($end->gt($today->copy()->addMonths(3))) {
                return 'Aman (' . $formattedMonthsRemaining . ' bulan)';
            }

            return 'Perhatian (' . $formattedMonthsRemaining . ' bulan)';
        };
    @endphp

    <h3>Daftar Pegawai - {{ $companyName ?? 'Servanda' }}</h3>

    <table border="1">
        <thead>
            @if ($isServanda)
                <tr>
                    <th>Nama</th>
                    <th>Employee No.</th>
                    <th>Position</th>
                    <th>Pay Freq</th>
                    <th>Jenis Kelamin</th>
                    <th>Area Penempatan</th>
                    <th>Tanggal Lahir</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status Kontrak</th>
                    <th>Status</th>
                </tr>
            @else
                <tr>
                    <th>Nama</th>
                    <th>Employee No.</th>
                    <th>Position</th>
                    <th>Pay Freq</th>
                    <th>Jenis Kelamin</th>
                    <th>Area Penempatan</th>
                    <th>Tanggal Lahir</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status Kontrak</th>
                    <th>Termination</th>
                    <th>Status</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                @if ($isServanda)
                    <tr>
                        <td>{{ $employee->nama }}</td>
                        <td>{{ $employee->employee_no }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->pay_freq }}</td>
                        <td>{{ $employee->jenis_kelamin }}</td>
                        <td>{{ $employee->area }}</td>
                        <td>{{ $employee->tanggal_lahir }}</td>
                        <td>{{ $employee->start_date }}</td>
                        <td>{{ $employee->end_date }}</td>
                        <td>{{ $contractStatusLabel($employee->end_date) }}</td>
                        <td>{{ $employee->status }}</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $employee->nama }}</td>
                        <td>{{ $employee->employee_no }}</td>
                        <td>{{ $employee->position }}</td>
                        <td>{{ $employee->pay_freq }}</td>
                        <td>{{ $employee->jenis_kelamin }}</td>
                        <td>{{ $employee->area }}</td>
                        <td>{{ $employee->tanggal_lahir }}</td>
                        <td>{{ $employee->start_date }}</td>
                        <td>{{ $employee->end_date }}</td>
                        <td>{{ $contractStatusLabel($employee->end_date) }}</td>
                        <td>{{ $employee->termination_date }}</td>
                        <td>{{ $employee->status }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>
