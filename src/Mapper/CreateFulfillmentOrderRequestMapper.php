<?php

namespace App\Mapper;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use App\MapperInterface;

class CreateFulfillmentOrderRequestMapper implements MapperInterface
{
    /**
     * @param AbstractOrder $order
     * @param BuyerInterface $buyer
     * @return array
     */
    public function mapToRequest(AbstractOrder $order, BuyerInterface $buyer): array
    {
        return [
            // The marketplace the fulfillment order is placed against.
            // string
            //"marketplaceId" => '',

            // A fulfillment order identifier that the seller creates to track their fulfillment order. The SellerFulfillmentOrderId must be unique for each fulfillment order that a seller creates. If the seller's system already creates unique order identifiers, then these might be good values for them to use.
            // string
            // required
            "sellerFulfillmentOrderId" => $order->getOrderId(),

            // A fulfillment order identifier that the seller creates. This value displays as the order identifier in recipient-facing materials such as the outbound shipment packing slip. The value of DisplayableOrderId should match the order identifier that the seller provides to the recipient. The seller can use the SellerFulfillmentOrderId for this value or they can specify an alternate value if they want the recipient to reference an alternate order identifier.\n\nThe value must be an alpha-numeric or ISO 8859-1 compliant string from one to 40 characters in length. Cannot contain two spaces in a row. Leading and trailing white space is removed.
            // string
            // required
            //"displayableOrderId" => '',

            // The date and time of the fulfillment order. Displays as the order date in recipient-facing materials such as the outbound shipment packing slip.
            // #/definitions/Timestamp
            // required
            // TODO I'm not sure about sellerFulfillmentOrderId and displayableOrderDate
            "displayableOrderDate" => $order->data['order_unique'],

            // Order-specific text that appears in recipient-facing materials such as the outbound shipment packing slip.
            // string
            // required
            "displayableOrderComment" => $order->data['comments'],

            // The shipping method for the fulfillment order. When this value is ScheduledDelivery, choose Ship for the fulfillmentAction. Hold is not a valid fulfillmentAction value when the shippingSpeedCategory value is ScheduledDelivery.
            // #/definitions/ShippingSpeedCategory
            // required
            "shippingSpeedCategory" => $this->mapShippingSpeedCategory($order->data['shipping_type_id']),

            // #/definitions/DeliveryWindow
            "deliveryWindow" => [
                // TODO Fix php format - add letter "Z" at the end instead "+00:00"
                "startDate" => (new \DateTime())->format(DATE_RFC3339),
                "endDate" => (new \DateTime($order->data['due_date']))->format(DATE_RFC3339)
            ],

            // The destination address for the fulfillment order.
            // #/definitions/Address
            // required
            "destinationAddress" => [
                // The name of the person, business or institution at the address.
                // string
                "name" => $this->findShippingName($order->data['shipping_adress']),

                // The first line of the address.
                // required
                "addressLine1" => $order->data['shipping_street'],

                // Additional address information, if required.
                //"addressLine2" => '',
                // Additional address information, if required.
                //"addressLine3" => '',

                // The city where the person, business, or institution is located. This property is required in all countries except Japan. It should not be used in Japan.
                "city" => $order->data['shipping_city'],

                // The district or county where the person, business, or institution is located.
                //"districtOrCounty" => '',

                // The state or region where the person, business or institution is located.
                "stateOrRegion" => $order->data['shipping_state'],

                // The postal code of the address.
                "postalCode" => $order->data['shipping_zip'],

                // The two digit country code. In ISO 3166-1 alpha-2 format.
                // required
                "countryCode" => $order->data['shipping_country'],

                // The phone number of the person, business, or institution located at the address.
                "phone" => $buyer->phone,
            ],

            // #/definitions/FulfillmentAction
            // TODO
            "fulfillmentAction" => 'Ship',

            // #/definitions/FulfillmentPolicy
            //"fulfillmentPolicy" => '',

            // #/definitions/CODSettings
            //"codSettings" => '',

            // The two-character country code for the country from which the fulfillment order ships. Must be in ISO 3166-1 alpha-2 format.
            // string
            //"shipFromCountryCode" => '',

            // #/definitions/NotificationEmailList
            //"notificationEmails" => '',

            // A list of features and their fulfillment policies to apply to the order.
            // array
            // #/definitions/FeatureSettings
            //"featureConstraints" => [],

            // A list of items to include in the fulfillment order preview, including quantity.
            // #/definitions/CreateFulfillmentOrderItemList
            // required
            "items" => array_map(function ($item) use ($order) {
                return $this->mapItem($item, $order->data['currency']);
            }, $order->data['products'])
        ];
    }

    protected function findShippingName(string $address)
    {
        $parts = $this->parseAddress($address);

        return $parts['name'];
    }

    protected function parseAddress(string $address)
    {
        $keys = [
            "name",
            "addressLine1",
            "city",
            "stateOrRegion",
            "country"
        ];
        $segments = explode("\n", trim($address));
        return array_combine($keys, $segments);
    }

    protected function mapItem(array $item, $currency)
    {
        return [
            // The seller SKU of the item.
            // required
            "sellerSku" => $item['sku'],
            // A fulfillment order item identifier that the seller creates to track fulfillment order items. Used to disambiguate multiple fulfillment items that have the same SellerSKU. For example, the seller might assign different SellerFulfillmentOrderItemId values to two items in a fulfillment order that share the same SellerSKU but have different GiftMessage values.
            // required
            // TODO I am not sure
            "sellerFulfillmentOrderItemId" => $item['order_product_id'],
            // #/definitions/Quantity
            // required
            "quantity" => $item['ammount'],
            // A message to the gift recipient, if applicable.
            //"giftMessage" => '',
            // Item-specific text that displays in recipient-facing materials such as the outbound shipment packing slip.
            "displayableComment" => $item['comment'],
            // Amazon's fulfillment network SKU of the item.
            //"fulfillmentNetworkSku" => '',
            // The monetary value assigned by the seller to this item.
            // #/definitions/Money
            "perUnitDeclaredValue" => [
                "currencyCode" => $currency,
                "value" => $item['original_price']
            ],
            // The amount to be collected from the recipient for this item in a COD (Cash On Delivery) order.
            // #/definitions/Money
            "perUnitPrice" => [
                "currencyCode" => $currency,
                "value" => $item['buying_price']
            ],
            // The tax on the amount to be collected from the recipient for this item in a COD (Cash On Delivery) order.
            // #/definitions/Money
            //"perUnitTax" => '',
        ];
    }

    protected function mapShippingSpeedCategory(int $type, string $name = '')
    {
        // TODO I don't know how can I define which shipping type or shipping name maps to amazon shipping categories
        // I guess "type" is the same ?
        // Options:
        $amazonCategories = [
            1 => "Standard",
            2 => "Expedited",
            3 => "Priority",
            4 => "ScheduledDelivery",
        ];

        if (array_key_exists($type, $amazonCategories)) {
            return $amazonCategories[$type];
        }

        throw new \Exception('Can not define shippingSpeedCategory');
    }
}