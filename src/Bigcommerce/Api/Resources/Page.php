<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Page extends Resource
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
		return Client::createPage($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updatePage($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deletePage($this->id);
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function getAll() : array|string
	{
		return Client::getPages();
	}

    /**
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function get() : Resource|string
	{
		return Client::getPage($this->id);
	}
}
