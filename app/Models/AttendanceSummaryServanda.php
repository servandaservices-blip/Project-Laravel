<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSummaryServanda extends Model
{
    protected $table = 'attendance_summary_servanda';

    protected $fillable = [
        'company_id',
        'employee_id',
        'employee_no',
        'employee_name',
        'smartpresence_employee_id',
        'period_label',
        'period_month',
        'period_year',
        'period_start',
        'period_end',
        'workday_count',
        'presence_count',
        'absent_count',
        'attendance_rate',
        'last_sync_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'last_sync_at' => 'datetime',
        'attendance_rate' => 'decimal:2',
    ];
}
