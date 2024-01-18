<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Coupon extends Resource
{
	protected array $ignoreOnCreate = [ 'id', 'num_uses' ];

	protected array $ignoreOnUpdate = [ 'id', 'num_uses' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createCoupon($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateCoupon($this->id, $this->getUpdateFields());
	}
}
