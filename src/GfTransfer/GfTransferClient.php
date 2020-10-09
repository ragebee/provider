<?php

namespace Ragebee\Provider\GfTransfer;

use GuzzleHttp\Client as GuzzleClient;

class GfTransferClient
{
    use GuzzleHttpClientTrait;

    const API_URL = 'https://foo.boo';

    /** @var string */
    protected $apiUrl;

    /** @var \GuzzleHttp\ClientInterface */
    protected $client;

    /** @var array */
    protected $credentials;

    /**
     * - api_url:
     *   (string)
     * - credentials:
     *   (array) an array of "foo", "boo" key.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->client = $args['client'] ?? new GuzzleClient(['handler' => $this->httpHandler()]);

        $this->apiUrl = $args['api_url'] ?? self::API_URL;
        $this->credentials = $args['credentials'];
    }

    public function playerCreate()
    {

    }

    public function launch()
    {

    }

    public function demo()
    {

    }

    public function getPlayerBalance()
    {

    }

    public function transferIn()
    {

    }

    public function transferOut()
    {

    }

    public function transactionRecordPlayerGet()
    {

    }

    public function betRecordGet()
    {

    }
}
