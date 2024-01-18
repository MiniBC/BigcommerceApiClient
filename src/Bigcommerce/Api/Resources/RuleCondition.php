<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * Conditions that will be applied to a product based on the rule.
 */
class RuleCondition extends Resource
{
    protected array $ignoreOnCreate = [ 'id' ];

    protected array $ignoreOnUpdate = [ 'id', 'rule_id' ];

	public $product_id;

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/products/' . $this->product_id . '/rules/' . $this->rule_id . '/conditions' , $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/products/' . $this->product_id . '/rules/' . $this->rule_id . '/conditions/' .$this->id , $this->getUpdateFields());
	}
}
