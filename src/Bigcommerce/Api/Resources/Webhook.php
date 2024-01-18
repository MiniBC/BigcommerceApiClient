<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Webhook extends Resource
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
		return Client::createWebhook($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateWebhook($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteWebhook($this->id);
	}
}
