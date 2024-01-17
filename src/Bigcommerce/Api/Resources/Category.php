<?php
namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class Category extends Resource
{

	protected array $ignoreOnCreate = [ 'id', 'parent_category_list' ];

	protected array $ignoreOnUpdate = [ 'id', 'parent_category_list' ];

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function create() : mixed
	{
		return Client::createCategory($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function update() : mixed
	{
		return Client::updateCategory($this->id, $this->getUpdateFields());
	}

}