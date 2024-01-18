<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * A custom field on a product.
 */
class ProductCustomField extends Resource
{
    protected array $ignoreOnCreate = [ 'id', 'product_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'product_id' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
	    return Client::createResource('/products/' . $this->product_id . '/customfields', $this->getCreateFields());
	}

    /**
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
	    Client::updateResource('/products/' . $this->product_id . '/customfields/' . $this->id, $this->getUpdateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete()
	{
	    Client::deleteResource('/products/' . $this->product_id . '/customfields/' . $this->id);
	}
}
