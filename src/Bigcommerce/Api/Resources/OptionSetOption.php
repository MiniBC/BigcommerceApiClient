<?php
namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class OptionSetOption extends Resource
{
    protected array $ignoreOnCreate = [ 'id', 'option_set_id' ];

    protected array $ignoreOnUpdate = [ 'id', 'option_set_id' ];

    /**
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function option() : array|string
	{
		return Client::getCollection($this->fields->option->resource);
	}

    /**
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function create() : mixed
	{
		return Client::createResource('/optionsets/options', $this->getCreateFields());
	}

    /**
     * @return void
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public function update()
	{
		Client::updateResource('/optionsets/options/' . $this->id, $this->getUpdateFields());
	}

}
