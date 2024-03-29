<?php

namespace App\Dao\Models;

use App\Dao\Builder\DataBuilder;
use App\Dao\Entities\RegisterEntity;
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

class Register extends Model
{
    use ActiveTrait, ApiTrait, DataTableTrait, FilterQueryString, OptionTrait, PowerJoins, RegisterEntity, Sanitizable, Sortable;

    protected $table = 'register';

    protected $primaryKey = 'register_id';

    protected $fillable = [
        'register_id',
        'register_rfid',
        'register_id_rs',
        'register_id_ruangan',
        'register_id_jenis',
        'register_status_cuci',
        'register_status_register',
        'register_status_transaksi',
        'register_status_proses',
        'register_created_by',
        'register_updated_by',
        'register_deleted_by',
        'register_deskripsi',
        'register_created_at',
        'register_updated_at',
    ];

    public $sortable = [
        'outstanding_rfid',
        'outstanding_key',
    ];

    protected $casts = [
        'outstanding_rs_ori' => 'integer',
        'outstanding_rs_scan' => 'integer',
    ];

    protected $filters = [
        'filter',
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
            DataBuilder::build($this->field_code())->name('Kode RS')->show()->sort(),
            DataBuilder::build($this->field_name())->name('Nama Rumah Sakit')->show()->sort(),
            DataBuilder::build($this->field_alamat())->name('Alamat')->show()->sort(),
            DataBuilder::build($this->field_description())->name('Deskripsi')->show()->sort(),
        ];

        if (level(UserLevel::Finance)) {
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
        return $this->hasMany(JenisLinen::class, JenisLinen::field_rs_id(), $this->field_primary());
    }
}
