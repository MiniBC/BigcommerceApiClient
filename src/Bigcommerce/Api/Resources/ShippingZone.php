<?php

namespace Bigcommerce\Api\Resources;

use Bigcommerce\Api\{Resource, Client, ClientError, NetworkError, ServerError};

class ShippingZone extends Resource
{
    protected array $ignoreOnCreate = [ 'id' ];

    protected array $ignoreOnUpdate = [ 'id' ];

    public function create() : mixed
    {
        return Client::createResource('/shipping/zones/', $this->getCreateFields());
    }

    public function update() : mixed
    {
        return Client::updateResource('/shipping/zones/'. $this->id, $this->getUpdateFields());
    }
}