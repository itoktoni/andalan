<?php

namespace App\Dao\Entities;

use App\Dao\Models\JenisBahan;
use App\Dao\Models\Kategori;
use App\Dao\Models\Rs;
use App\Dao\Models\Supplier;
use App\Dao\Models\ViewTotalJenis;

trait JenisLinenEntity
{
    public static function field_primary()
    {
        return 'jenis_id';
    }

    public function getFieldPrimaryAttribute()
    {
        return $this->{$this->field_primary()};
    }

    public static function field_name()
    {
        return 'jenis_nama';
    }

    public function getFieldNameAttribute()
    {
        return $this->{$this->field_name()};
    }

    public static function field_description()
    {
        return 'jenis_deskripsi';
    }

    public function getFieldDescriptionAttribute()
    {
        return $this->{$this->field_description()};
    }

    public static function field_rs()
    {
        return 'jenis_id_rs';
    }

    public function getFieldRsAttribute()
    {
        return $this->{$this->field_rs()};
    }

    public static function field_rs_id()
    {
        return 'jenis_id_rs';
    }

    public function getFieldRsIdAttribute()
    {
        return $this->{$this->field_rs_id()};
    }

    public function getFieldRsNameAttribute()
    {
        return $this->{Rs::field_name()};
    }

    /*
    public static function field_bahan_id()
    {
        return 'jenis_id_jenis_bahan';
    }

    public function getFieldBahanIdAttribute()
    {
        return $this->{$this->field_bahan_id()};
    }

    public function getFieldBahanNameAttribute()
    {
        return $this->{JenisBahan::field_name()};
    }


    public static function field_supplier_id()
    {
        return 'jenis_id_supplier';
    }

    public function getFieldSupplierIdAttribute()
    {
        return $this->{$this->field_supplier_id()};
    }

    public function getFieldSupplierNameAttribute()
    {
        return $this->{Supplier::field_name()};
    }

    */

    public static function field_category_id()
    {
        return 'jenis_id_kategori';
    }

    public function getFieldCategoryIdAttribute()
    {
        return $this->{$this->field_kategori()};
    }

    public function getFieldCategoryNameAttribute()
    {
        return $this->{Kategori::field_name()};
    }

    public static function field_parstock()
    {
        return 'jenis_parstok';
    }

    public function getFieldParstockAttribute()
    {
        return $this->{$this->field_parstock()};
    }

    public static function field_weight()
    {
        return 'jenis_berat';
    }

    public function getFieldWeightAttribute()
    {
        return $this->{$this->field_weight()} ?? 0;
    }

    public static function field_image()
    {
        return 'jenis_gambar';
    }

    public function getFieldImageAttribute()
    {
        return $this->{$this->field_image()};
    }

    public function getFieldImageUrlAttribute()
    {
        return imageUrl($this->{$this->field_image()});
    }

    public function getFieldTotalAttribute()
    {
        return $this->{ViewTotalJenis::field_total()};
    }
}
