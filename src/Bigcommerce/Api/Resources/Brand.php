<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Brand extends Resource
{
	protected array $ignoreOnCreate = [ 'id' ];

	protected array $ignoreOnUpdate = [ 'id' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createBrand($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateBrand($this->id, $this->getUpdateFields());
	}

}