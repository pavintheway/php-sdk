<?php

namespace Ctct\Guzzle7Shim;

use GuzzleHttp\Psr7\Response;

class JsonResponse extends Response
{
    /**
     * @return array<mixed, mixed>
     */
    public function json()
    {
        return \json_decode(parent::getBody(), true);
    }
}
