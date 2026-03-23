<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkdayTarget extends Model
{
    protected $table = 'workday_targets';

    protected $fillable = [
        'company',
        'division',
        'branch',
        'year',
        'month',
        'monthly_target',
        'daily_target',
    ];
}
