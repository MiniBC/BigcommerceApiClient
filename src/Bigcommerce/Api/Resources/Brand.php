<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class Brand extends Resource
{
	protected array $ignoreOnCreate = [ 'id' ];

	protected array $ignoreOnUpdate = [ 'id' ];

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function create() : mixed
	{
		return Client::createBrand($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function update() : mixed
	{
		return Client::updateBrand($this->id, $this->getUpdateFields());
	}

}