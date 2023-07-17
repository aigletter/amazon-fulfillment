<?php

namespace Service;

use App\Data\TestBuyer;
use App\Data\TestOrder;
use App\Mapper\CreateFulfillmentOrderRequestMapper;
use App\ShippingService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ShippingServiceTest extends TestCase
{
    public function testShip()
    {
        $order = new TestOrder(16400);
        $order->load();

        $buyer = new TestBuyer(
            json_decode(
                file_get_contents(dirname(__DIR__, 2) . '/mock/buyer.29664.json'),
                true
            )
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->atLeast(4))->method('sendRequest')->willReturnCallback(function ($request) {
            if ($request->getMethod() === 'GET') {
                $payload = $this->getTestPayload();
                $body = (new HttpFactory())->createStream(
                    json_encode($payload)
                );
                return new Response(200, [], $body);
            }
            return new Response(200);
        });

        $service = new ShippingService(
            $client,
            new HttpFactory(),
            new HttpFactory(),
            new CreateFulfillmentOrderRequestMapper(),
        );

        $result = $service->ship($order, $buyer);

        $this->assertSame('123456789', $result);
    }

    private function getTestPayload()
    {
        static $counter = 0;
        $statuses = [
            "New",
            "Planning",
            "Processing",
        ];
        $status = $statuses[$counter];
        $counter++;
        return [
            'payload' => [
                'fulfillmentOrder' => [
                    'fulfillmentOrderStatus' => $status
                ],
                'fulfillmentShipments' => [
                    [
                        'fulfillmentShipmentPackage' => [
                            [
                                'trackingNumber' => '123456789',
                            ]
                        ],
                    ]
                ],
            ]
        ];
    }
}