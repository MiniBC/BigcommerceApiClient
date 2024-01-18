<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * A product option rule.
 */
class Rule extends Resource
{
    protected array $ignoreOnCreate = [ 'id', 'product_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'product_id' ];

    /**
     * @return array
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function conditions() : array
	{
		$conditions = Client::getCollection($this->fields->conditions->resource, 'RuleCondition');

		foreach ($conditions as $condition) {
			$condition->product_id = $this->product_id;
		}

		return $conditions;
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/products/' . $this->product_id . '/rules', $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/products/' . $this->product_id . '/rules/' . $this->id, $this->getUpdateFields());
	}
}
