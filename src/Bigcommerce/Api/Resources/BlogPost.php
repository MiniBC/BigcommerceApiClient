<?php
namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

class BlogPost extends Resource
{
	protected array $ignoreOnCreate = [ 'id', 'preview_url' ];

	protected array $ignoreOnUpdate = [ 'id', 'preview_url' ];

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function create() : mixed
	{
		return Client::createBlogPost($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function update() : mixed
	{
		return Client::updateBlogPost($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteBlogPost($this->id);
	}
}