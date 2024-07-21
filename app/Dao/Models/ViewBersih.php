<?php

namespace App\Dao\Models;

use App\Dao\Entities\BersihEntity;
use Illuminate\Database\Eloquent\Model;

class ViewBersih extends Bersih
{
    protected $table = 'view_bersih';

    protected $primaryKey = 'bersih_id';

    protected $casts = [
        'bersih_rfid' => 'string',
        'bersih_rs_id' => 'integer',
        'bersih_created_at' => 'datetime',
        'bersih_updated_at' => 'datetime',
    ];
}
