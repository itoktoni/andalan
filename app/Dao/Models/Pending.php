<?php

namespace App\Dao\Models;

use App\Dao\Builder\DataBuilder;
use App\Dao\Entities\OutstandingEntity;
use App\Dao\Entities\PendingEntity;
use App\Dao\Enums\UserLevel;
use App\Dao\Traits\ActiveTrait;
use App\Dao\Traits\ApiTrait;
use App\Dao\Traits\DataTableTrait;
use App\Dao\Traits\OptionTrait;
use App\Http\Resources\GeneralResource;
use Illuminate\Database\Eloquent\Model;
use Kirschbaum\PowerJoins\PowerJoins;
use Kyslik\ColumnSortable\Sortable;
use Mehradsadeghi\FilterQueryString\FilterQueryString as FilterQueryString;
use Touhidurabir\ModelSanitize\Sanitizable as Sanitizable;

class Pending extends Model
{
    use ActiveTrait, ApiTrait, DataTableTrait, FilterQueryString, OptionTrait, PendingEntity, PowerJoins, Sanitizable, Sortable;

    protected $table = 'pending';

    protected $primaryKey = 'pending_rfid';

    protected $fillable = [
        'pending_id',
        'pending_rfid',
        'pending_key',
        'pending_id_ruangan',
        'pending_id_jenis',
        'pending_id_rs',
        'pending_kotor',
        'pending_tanggal',
        'pending_status_transaksi',
        'pending_status_proses',
    ];

    public $sortable = [
        'pending_rfid',
        'pending_key',
    ];

    protected $casts = [
        'pending_rfid' => 'string',
        'pending_id_rs' => 'integer',
        'pending_id_jenis' => 'integer',
        'pending_id_ruangan' => 'integer',
        'pending_kotor' => 'date',
        'pending_tanggal' => 'date',
    ];

    protected $filters = [
        'filter',
        'view_rs_id',
        'view_ruangan_id',
        'view_linen_id',
    ];

    public $timestamps = false;

    public $incrementing = true;

    public function fieldSearching()
    {
        return $this->field_name();
    }

    public function fieldDatatable(): array
    {
        $data = [
            DataBuilder::build($this->field_primary())->name('ID')->width(20)->sort(),
        ];

        return $data;
    }

    public function apiTransform()
    {
        return GeneralResource::class;
    }

    public function has_jenis()
    {
        return $this->hasOne(JenisLinen::class, JenisLinen::field_primary(), self::field_jenis_id());
    }

    public function has_ruangan()
    {
        return $this->hasOne(Ruangan::class, Ruangan::field_primary(), self::field_ruangan_id());
    }

    public function has_rs()
    {
        return $this->hasOne(Rs::class, Rs::field_primary(), self::field_rs_id());
    }

    public function has_detail()
    {
        return $this->hasOne(Detail::class, Detail::field_primary(), self::field_rfid());
    }

    public function has_view()
    {
        return $this->hasOne(ViewDetailLinen::class, ViewDetailLinen::field_primary(), self::field_rfid());
    }

}
