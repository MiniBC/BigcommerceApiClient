<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class OptionSet extends Resource
{
    protected array $ignoreOnCreate = [ 'id' ];

    protected array $ignoreOnUpdate = [ 'id' ];

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function options() : array|string
	{
		return Client::getCollection($this->fields->options->resource, 'OptionSetOption');
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/optionsets', $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/optionsets/' . $this->id, $this->getUpdateFields());
	}
}
