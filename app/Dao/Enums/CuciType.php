<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class CuciType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const Unknown = 0;

    const Cuci = 1;

    const Sewa = 2;

    public static function getDescription($value): string
    {
        if ($value === self::Sewa) {
            return 'Rental';
        }

        return parent::getDescription($value);
    }
}
