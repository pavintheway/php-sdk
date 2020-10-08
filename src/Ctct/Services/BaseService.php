<?php
namespace Ctct\Services;

use Ctct\Exceptions\CtctException;
use Ctct\Guzzle7Shim\JsonResponse;
use Ctct\Util\Config;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

/**
 * Super class for all services
 *
 * @package Services
 * @author Constant Contact
 */
abstract class BaseService
{
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

        // This avoids having to rewrite the calls to Response::getBody()->json() in the Services
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            return new JsonResponse(
                $response->getStatusCode(),
                $response->getHeaders(),
                $response->getBody(),
                $response->getProtocolVersion(),
                $response->getReasonPhrase()
            );
        }));

        $this->client = new Client(['handler' => $stack]);
    }

    /**
     * Get the rest client being used by the service
     * @return Client - GuzzleHTTP Client implementation being used
     */
    protected function getClient()
    {
        return $this->client;
    }

    protected function createBaseRequest($accessToken, $method, $baseUrl) {
        return new Request(
            $method,
            Uri::withQueryValues(new Uri($baseUrl), ['api_key' => $this->apiKey]),
            $this->getHeaders($accessToken)
        );
    }

    /**
     * Turns a BadResponseException into a CtctException - like magic.
     * @param BadResponseException $exception
     * @return CtctException
     */
    protected function convertException($exception)
    {
        $ctctException = new CtctException($exception->getResponse()->getReasonPhrase(), $exception->getCode());
        $ctctException->setUrl((string) $exception->getRequest()->getUri());
        $ctctException->setErrors(json_decode($exception->getResponse()->getBody()->getContents()));
        return $ctctException;
    }
}
