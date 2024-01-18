<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Customer extends Resource
{

	protected array $ignoreOnCreate = [ 'id' ];

	protected array $ignoreOnUpdate = [ 'id' ];

    /**
     * @return string|array
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function addresses() : string|array
	{
		return Client::getCollection($this->fields->addresses->resource, 'Address');
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createCustomer($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateCustomer($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteCustomer($this->id);
	}

}