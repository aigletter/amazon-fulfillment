<?php

namespace App\Data;

/**
 * @method getId()
 */
class TestOrder extends AbstractOrder
{
    protected function loadOrderData(int $id): array
    {
        return json_decode(
            file_get_contents(dirname(__DIR__) . '/../mock/order.16400.json'),
            true
        );
    }
}