<?php

namespace Ctct\Services;

use Ctct\Exceptions\CtctException;
use Ctct\Util\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;

/**
 * Super class for all services
 *
 * @package Services
 * @author Constant Contact
 */
abstract class BaseService
{
    /**
     * GuzzleHTTP Client Implementation to use for HTTP requests
     * @var Client
     */
    private $client;
    /**
     * ApiKey for the application
     * @var string
     */
    private $apiKey;

    /**
     * Constructor with the option to to supply an alternative rest client to be used
     * @param string $apiKey - Constant Contact API Key
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client();
    }

    /**
     * Get the rest client being used by the service
     * @return Client - GuzzleHTTP Client implementation being used
     */
    protected function getClient()
    {
        return $this->client;
    }

    protected function createBaseRequest($accessToken, $method, $baseUrl)
    {
        return new Request(
            $method,
            $baseUrl . '?api_key=' . $this->apiKey,
            $this->getHeaders($accessToken)
        );
    }

    /**
     * Helper function to return required headers for making an http request with constant contact
     * @param $accessToken - OAuth2 access token to be placed into the Authorization header
     * @return array - authorization headers
     */
    private static function getHeaders($accessToken)
    {
        return array(
            'User-Agent' => 'ConstantContact AppConnect PHP Library v' . Config::get('settings.version'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'x-ctct-request-source' => 'sdk.php' . Config::get('settings.version')
        );
    }

    /**
     * Turns a ClientException into a CtctException - like magic.
     * @param ClientException $exception - Guzzle ClientException
     * @return CtctException
     */
    protected function convertException($exception)
    {
        $ctctException = new CtctException($exception->getResponse()->getReasonPhrase(), $exception->getCode());
        $ctctException->setUrl('');
        $ctctException->setErrors(json_decode($exception->getResponse()->getBody()->getContents()));
        return $ctctException;
    }

    public function createBaseQuery() {
        return ['api_key' => $this->apiKey];
    }
}
