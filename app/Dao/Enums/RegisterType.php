<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class RegisterType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const UNKNOWN = null;

    const REGISTER = 'REGISTER';

    const GANTI_CHIP = 'GANTI_CHIP';
}
