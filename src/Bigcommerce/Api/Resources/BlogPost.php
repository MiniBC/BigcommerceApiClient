<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class BlogPost extends Resource
{
	protected array $ignoreOnCreate = [ 'id', 'preview_url' ];

	protected array $ignoreOnUpdate = [ 'id', 'preview_url' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createBlogPost($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateBlogPost($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteBlogPost($this->id);
	}
}