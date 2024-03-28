<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Enum as Enum;
use BenSampo\Enum\Contracts\LocalizedEnum;

class LinenType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const UNKNOWN            =  null;
    const BEBAS              =  'BEBAS';
    const DEDICATED          =  'DEDICATED';

}
