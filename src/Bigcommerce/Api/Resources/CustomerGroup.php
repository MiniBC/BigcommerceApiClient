<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class CustomerGroup extends Resource
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
		return Client::createCustomerGroup($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteCustomerGroup($this->id);
	}
}
