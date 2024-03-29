<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class BedaRsType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const Sama = 0;

    const Beda = 1;

    const BelumRegister = 2;

    public static function getDescription($value): string
    {
        if ($value === self::Sama) {
            return 'OK';
        } elseif ($value == self::Beda) {
            return 'Beda RS';
        } else {
            return 'Belum Register';
        }

        return parent::getDescription($value);
    }
}
