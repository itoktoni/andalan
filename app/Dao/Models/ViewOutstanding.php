<?php

namespace App\Dao\Models;

use Illuminate\Database\Eloquent\Model;

class ViewOutstanding extends Outstanding
{
    protected $table = 'view_outstanding';

    protected $primaryKey = 'outstanding_rfid';
}
