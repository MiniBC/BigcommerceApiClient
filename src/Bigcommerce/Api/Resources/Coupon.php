<?php
namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class Coupon extends Resource
{
	protected array $ignoreOnCreate = [ 'id', 'num_uses' ];

	protected array $ignoreOnUpdate = [ 'id', 'num_uses' ];

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function create() : mixed
	{
		return Client::createCoupon($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function update() : mixed
	{
		return Client::updateCoupon($this->id, $this->getUpdateFields());
	}
}
