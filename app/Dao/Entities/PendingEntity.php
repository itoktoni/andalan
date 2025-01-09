<?php

namespace App\Dao\Entities;

use App\Dao\Enums\CuciType;
use App\Dao\Enums\HilangType;
use App\Dao\Enums\ProcessType;
use App\Dao\Enums\RegisterType;
use App\Dao\Enums\TransactionType;
use App\Dao\Models\JenisLinen;
use App\Dao\Models\Rs;
use App\Dao\Models\Ruangan;

trait PendingEntity
{
    public static function field_primary()
    {
        return 'pending_id';
    }

    public function getFieldPrimaryAttribute()
    {
        return $this->{$this->field_primary()};
    }


    public static function field_rfid()
    {
        return 'pending_rfid';
    }

    public function getFieldRfidAttribute()
    {
        return $this->{$this->field_rfid()};
    }

    public static function field_jenis_id()
    {
        return 'pending_id_jenis';
    }

    public static function field_key()
    {
        return 'pending_key';
    }

    public function getFieldKeyAttribute()
    {
        return $this->{$this->field_key()};
    }

    public static function field_name()
    {
        return self::field_primary();
    }

    public function getFieldNameAttribute()
    {
        return $this->{$this->field_name()};
    }

    public static function field_ruangan_id()
    {
        return 'pending_id_ruangan';
    }

    public function getFieldRuanganIdAttribute()
    {
        return $this->{$this->field_ruangan_id()};
    }

    public function getFieldRuanganNameAttribute()
    {
        return $this->{Ruangan::field_name()};
    }

    public static function field_rs_id()
    {
        return 'pending_id_rs';
    }

    public function getFieldRsIdAttribute()
    {
        return $this->{$this->field_rs_id()};
    }

    public static function field_status_transaction()
    {
        return 'pending_status_transaksi';
    }

    public function getFieldStatusTransactionAttribute()
    {
        return $this->{$this->field_status_transaction()};
    }

    public function getFieldStatusTransactionNameAttribute()
    {
        return TransactionType::getDescription($this->getFieldStatusTransactionAttribute());
    }

    public static function field_status_process()
    {
        return 'pending_status_proses';
    }

    public function getFieldStatusProcessAttribute()
    {
        return $this->{$this->field_status_process()};
    }

    public function getFieldStatusProcessNameAttribute()
    {
        return ProcessType::getDescription($this->getFieldStatusProcessAttribute());
    }

    public static function field_tanggal()
    {
        return 'pending_tanggal';
    }

    public function getFieldTanggalAttribute()
    {
        return $this->{self::field_tanggal()};
    }

    public static function field_kotor()
    {
        return 'pending_kotor';
    }

    public function getFieldKotorAttribute()
    {
        return $this->{self::field_kotor()};
    }

    public static function field_linen_id()
    {
        return JenisLinen::field_primary();
    }

    public function getFieldLinenIdAttribute()
    {
        return $this->{$this->field_linen_name()};
    }

    public static function field_linen_name()
    {
        return JenisLinen::field_name();
    }

    public function getFieldLinenNameAttribute()
    {
        return $this->{$this->field_linen_name()};
    }

    public static function field_location_name()
    {
        return Ruangan::field_name();
    }

    public function getFieldLocationNameAttribute()
    {
        return $this->{$this->field_location_name()};
    }

    public function getFieldRsNameAttribute()
    {
        return $this->{Rs::field_name()};
    }
}
