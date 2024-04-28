<?php

namespace App\Dao\Models;

use Illuminate\Database\Eloquent\Model;

class ViewBersih extends Bersih
{
    protected $table = 'view_bersih';

    protected $primaryKey = 'bersih_rfid';

    protected $casts = [
        'bersih_rs_id' => 'integer',
        'bersih_created_at' => 'datetime',
        'bersih_updated_at' => 'datetime',
    ];
}
