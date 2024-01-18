<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * An image which is displayed on the storefront for a product.
 */
class ProductImage extends Resource
{
    protected array $ignoreOnCreate = [ 'id', 'date_created', 'product_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'date_created', 'product_id' ];

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/products/' . $this->product_id . '/images' , $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/products/' . $this->product_id . '/images/' . $this->id , $this->getUpdateFields());
	}

}