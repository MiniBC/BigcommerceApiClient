<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * An option.
 */
class Option extends Resource
{
    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function values() : array|string
	{
		return Client::getCollection($this->fields->values->resource, 'OptionValue');
	}
}
