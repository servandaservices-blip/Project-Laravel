<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';

    protected $fillable = [
        'nama',
        'employee_no',
        'position',
        'cost_center',
        'employment_status',
        'pay_freq',
    ];
}
