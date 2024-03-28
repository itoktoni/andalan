<?php

namespace App\Dao\Models;

use App\Dao\Builder\DataBuilder;
use App\Dao\Entities\OutstandingEntity;
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

class Outstanding extends Model
{
    use Sortable, FilterQueryString, Sanitizable, DataTableTrait, OutstandingEntity, ActiveTrait, OptionTrait, PowerJoins, ApiTrait;

    protected $table = 'outstanding';
    protected $primaryKey = 'outstanding_rfid';

    protected $fillable = [
        'outstanding_rfid',
        'outstanding_key',
        'outstanding_rs_ori',
        'outstanding_rs_scan',
        'outstanding_id_ruangan',
        'outstanding_id_jenis',
        'outstanding_status_transaksi',
        'outstanding_status_proses',
        'outstanding_created_at',
        'outstanding_updated_at',
        'outstanding_created_by',
        'outstanding_updated_by',
    ];

    public $sortable = [
        'outstanding_rfid',
        'outstanding_key',
    ];

    protected $casts = [
        'outstanding_rs_ori' => 'integer',
        'outstanding_rs_scan' => 'integer'
    ];

    protected $filters = [
        'filter',
    ];

    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    public function fieldSearching(){
        return $this->field_name();
    }

    public function fieldDatatable(): array
    {
        $data = [
            DataBuilder::build($this->field_primary())->name('ID')->width(20)->sort(),
            DataBuilder::build($this->field_code())->name('Kode RS')->show()->sort(),
            DataBuilder::build($this->field_name())->name('Nama Rumah Sakit')->show()->sort(),
            DataBuilder::build($this->field_alamat())->name('Alamat')->show()->sort(),
            DataBuilder::build($this->field_description())->name('Deskripsi')->show()->sort(),
        ];

        if(level(UserLevel::Finance)){
            $data = array_merge($data, [
                DataBuilder::build($this->field_harga_cuci())->name('Harga Cuci')->show()->sort(),
                DataBuilder::build($this->field_harga_sewa())->name('Harga Rental')->show()->sort(),
            ]);
        }

        return $data;
    }

    public function apiTransform()
    {
        return GeneralResource::class;
    }

    public function has_ruangan()
    {
        return $this->belongsToMany(Ruangan::class, 'rs_dan_ruangan', Outstanding::field_primary(), Ruangan::field_primary());
    }

    public function has_rfid()
    {
        return $this->hasMany(Detail::class, Detail::field_rs_id(), $this->field_primary());
    }

    public function has_jenis()
    {
        return $this->hasMany(Jenis::class, Jenis::field_rs_id(), $this->field_primary());
    }
}
