<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * Represents a single currency.
 */
class Currency extends Resource
{
	protected array $ignoreOnCreate = [ 'date_created', 'date_modified' ];

	protected array $ignoreOnUpdate = [ 'id', 'date_created', 'date_modified' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createCurrency($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateCurrency($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteCurrency($this->id);
	}
}
