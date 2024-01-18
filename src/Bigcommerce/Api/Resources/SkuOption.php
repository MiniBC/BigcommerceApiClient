<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\Resource;
use Bigcommerce\Api\Client;

/**
 * A relationship between a product SKU and an option.
 */
class SkuOption extends Resource
{
    protected array $ignoreOnCreate = [ 'id' ];

    protected array $ignoreOnUpdate = [ 'id', 'sku_id' ];

	public int $product_id;

    /**
     * @return mixed
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/products/' . $this->product_id . '/skus/' . $this->sku_id . '/options' , $this->getCreateFields());
	}

    /**
     * @return void
     * @throws \Bigcommerce\Api\ClientError
     * @throws \Bigcommerce\Api\NetworkError
     * @throws \Bigcommerce\Api\ServerError
     */
	public function update()
	{
		Client::updateResource('/products/' . $this->product_id . '/skus/' . $this->sku_id . '/options/' .$this->id , $this->getUpdateFields());
	}
}
