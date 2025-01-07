<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class PendingType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const Proses = 'PROSES';

    const Selesai = 'SELESAI';
}
