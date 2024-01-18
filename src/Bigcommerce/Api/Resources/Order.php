<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Order extends Resource
{
    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function shipments() : array|string
	{
		return Client::getCollection('/orders/'. $this->id . '/shipments', 'Shipment');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function products() : array|string
	{
		return Client::getCollection($this->fields->products->resource, 'OrderProduct');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function shipping_addresses() : array|string
	{
		return Client::getCollection($this->fields->shipping_addresses->resource, 'Address');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function coupons() : array|string
	{
		return Client::getCollection($this->fields->coupons->resource, 'Coupon');
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		$order = new \stdClass; // to use stdClass in global namespace use this...
		$order->status_id = $this->status_id;
		$order->is_deleted = $this->is_deleted;

		Client::updateResource('/orders/' . $this->id, $order);
	}

}
