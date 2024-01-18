<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * Relationship between a product and an option applied from an option set.
 */
class ProductOption extends Resource
{
    /**
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function option() : Resource|string
	{
		return Client::getResource('/options/' . $this->option_id, 'Option');
	}
}
