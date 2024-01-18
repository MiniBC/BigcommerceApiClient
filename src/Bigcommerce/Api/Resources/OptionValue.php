<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

/**
 * Selectable value of an option.
 */
class OptionValue extends Resource
{
    protected array $ignoreOnCreate = [ 'id', 'option_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'option_id' ];

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

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/options/' . $this->option_id . '/values', $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/options/' . $this->option_id . '/values/' . $this->id, $this->getUpdateFields());
	}
}
