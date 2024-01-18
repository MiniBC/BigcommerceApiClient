<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * Represents a single product.
 */
class Product extends Resource
{
	protected array $ignoreOnCreate = [ 'date_created', 'date_modified' ];

	/**
	 * @see https://developer.bigcommerce.com/display/API/Products#Products-ReadOnlyFields
	 * @var array
	 */
	protected array $ignoreOnUpdate = [
		'id',
		'rating_total',
		'rating_count',
		'date_created',
		'date_modified',
		'date_last_imported',
		'number_sold',
		'brand',
		'images',
		'discount_rules',
		'configurable_fields',
		'custom_fields',
		'videos',
		'skus',
		'rules',
		'option_set',
		'options',
		'tax_class'
    ];

	protected array $ignoreIfZero = [ 'tax_class_id' ];

    /**
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function brand() : Resource|string
	{
		return Client::getResource($this->fields->brand->resource, 'Brand');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function images() : array|string
	{
		return Client::getCollection($this->fields->images->resource, 'ProductImage');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function skus() : array|string
	{
		return Client::getCollection($this->fields->skus->resource, 'Sku');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function rules() : array|string
	{
		return Client::getCollection($this->fields->rules->resource, 'Rule');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function videos() : array|string
	{
		return Client::getCollection($this->fields->videos->resource, 'ProductVideo');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function custom_fields() : array|string
	{
		return Client::getCollection($this->fields->custom_fields->resource, 'ProductCustomField');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function configurable_fields() : array|string
	{
		return Client::getCollection($this->fields->configurable_fields->resource, 'ProductConfigurableField');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function discount_rules() : array|string
	{
		return Client::getCollection($this->fields->discount_rules->resource, 'DiscountRule');
	}

    /**
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function option_set() : Resource|string
	{
		return Client::getResource($this->fields->option_set->resource, 'OptionSet');
	}

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function options() : array|string
	{
		return Client::getCollection('/products/' . $this->id . '/options', 'ProductOption');
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createProduct($this->getCreateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update() : mixed
	{
		return Client::updateProduct($this->id, $this->getUpdateFields());
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function delete() : mixed
	{
		return Client::deleteProduct($this->id);
	}

    /**
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function tax_class() : Resource|string
	{
		return Client::getResource($this->fields->tax_class->resource, 'TaxClass');
	}

}
