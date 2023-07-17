<?php

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ShippingService implements ShippingServiceInterface
{
    protected const FULFILLMENT_ORDERS_PATH = '/fba/outbound/2020-07-01/fulfillmentOrders/';

    protected const STATUS_PROCESSING = 'Processing';

    protected const STATUS_CHECK_INTERVAL = 1;

    protected const MAX_EXECUTION_TIME = 5 * 60;

    protected $client;

    protected $requestFactory;

    protected $streamFactory;

    protected $mapper;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        MapperInterface $mapper
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->mapper = $mapper;
    }

    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        $this->createFulfillmentOrder($order, $buyer);

        $startTime = time();

        do {
            if (time() - $startTime >= self::MAX_EXECUTION_TIME) {
                break;
            }

            sleep(self::STATUS_CHECK_INTERVAL);

            $result = $this->getFulfilmentOrder($order->getOrderId());

            if ($result['fulfillmentOrder']['fulfillmentOrderStatus'] !== self::STATUS_PROCESSING) {
                continue;
            }

            return $result['fulfillmentShipments'][0]['fulfillmentShipmentPackage'][0]['trackingNumber'];

        } while ($result['fulfillmentOrder']['fulfillmentOrderStatus'] !== self::STATUS_PROCESSING);

        throw new Exception('Timeout');
    }

    private function getFulfilmentOrder(int $sellerFulfillmentOrderId)
    {
        $uri = self::FULFILLMENT_ORDERS_PATH . '/' . $sellerFulfillmentOrderId;
        $request = $this->requestFactory->createRequest('GET', $uri);

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getBody()->getContents(), $response->getStatusCode());
        }

        $content = $response->getBody()->getContents();

        return json_decode($content, true)['payload'];
    }

    private function createFulfillmentOrder(AbstractOrder $order, BuyerInterface $buyer)
    {
        $request = $this->requestFactory->createRequest('POST', self::FULFILLMENT_ORDERS_PATH);
        try {
            $content = json_encode([
                'body' => $this->mapper->mapToRequest($order, $buyer)
            ], JSON_PRETTY_PRINT);
        } catch (\Throwable $exception) {
            // TODO
            throw new Exception('Can not map data to request', 500, $exception);
        }
        $body = $this->streamFactory->createStream($content);
        $request = $request->withBody($body);

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getBody()->getContents(), $response->getStatusCode());
        }
    }
}