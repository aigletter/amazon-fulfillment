<?php

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;

interface MapperInterface
{
    public function mapToRequest(AbstractOrder $object, BuyerInterface $buyer): array;
}