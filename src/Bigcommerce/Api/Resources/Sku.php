<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * A stock keeping unit for a product.
 */
class Sku extends Resource
{
    protected array $ignoreOnCreate = [ 'product_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'product_id' ];

    /**
     * @return array
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function options() : array
	{
		$options = Client::getCollection($this->fields->options->resource, 'SkuOption');

		foreach($options as $option) {
			$option->product_id = $this->product_id;
		}

		return $options;
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/products/' . $this->product_id . '/skus' , $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/products/' . $this->product_id . '/skus/' . $this->id , $this->getUpdateFields());
	}
}
