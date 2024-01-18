<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Shipment extends Resource
{
	protected array $ignoreOnCreate = [
		'id',
		'order_id',
		'date_created',
		'customer_id',
		'shipping_method'
    ];

	protected array $ignoreOnUpdate = [
		'id',
		'order_id',
		'date_created',
		'customer_id',
		'shipping_method',
		'items'
	];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/orders/' . $this->order_id . '/shipments', $this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
        return Client::updateResource('/orders/' . $this->fields->order_id . '/shipments/' . $this->id, $this->getUpdateFields());
	}
}
