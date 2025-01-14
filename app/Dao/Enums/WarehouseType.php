<?php

namespace App\Dao\Enums;

use App\Dao\Traits\StatusTrait;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as Enum;

class WarehouseType extends Enum implements LocalizedEnum
{
    use StatusTrait;

    const WORKSHOP = 'WORKSHOP';

    const WAREHOUSE = 'WAREHOUSE';

    const HUB = 'HUB';
}
