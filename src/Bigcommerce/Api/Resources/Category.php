<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class Category extends Resource
{

	protected array $ignoreOnCreate = [ 'id', 'parent_category_list' ];

	protected array $ignoreOnUpdate = [ 'id', 'parent_category_list' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createCategory($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateCategory($this->id, $this->getUpdateFields());
	}
}
