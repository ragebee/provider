<?php

namespace Ragebee\Provider\Cq9Seamless;

use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;

class Cq9SeamlessClient
{
    const API_URL = 'https://api.cqgame.games';

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
     *   (array) an array of "api_key" key.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->apiUrl = $args['api_url'] ?? self::API_URL;
        $this->client = $args['client'] ?? new GuzzleClient(['handler' => GuzzleFactory::handler()]);
        $this->credentials = $args['credentials'];
    }

    /**
     * 取得遊戲列表
     */
    public function gameList()
    {
        $response = $this->client->get($this->getEndpointUrl($this->apiUrl, 'gameboy/game/list/cq9'), [
            RequestOptions::HEADERS => [
                'Authorization' => data_get($this->credentials, 'api_key'),
            ],
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    /**
     * 使用者登入遊戲
     */
    public function loginGame(
        string $account,
        string $gamehall = 'CQ9',
        string $gamecode,
        string $gameplat = 'web',
        string $lang = 'zh-cn',
        string $session,
        string $app = 'N',
        string $detect = 'N'
    ) {
        $parameters = [
            'account' => $account,
            'gamehall' => $gamehall,
            'gamecode' => $gamecode,
            'gameplat' => $gameplat,
            'lang' => $lang,
            'session' => $session,
            'app' => $app,
            'detect' => $detect,
        ];

        $response = $this->client->post($this->getEndpointUrl($this->apiUrl, 'gameboy/player/sw/gamelink'), [
            RequestOptions::HEADERS => [
                'Authorization' => data_get($this->credentials, 'api_key'),
            ],
            RequestOptions::FORM_PARAMS => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    /**
     * 使用者出遊戲
     */
    public function logout(
        string $account
    ) {
        $parameters = [
            'account' => $account,
        ];

        $response = $this->client->post($this->getEndpointUrl($this->apiUrl, 'gameboy/player/logout'), [
            RequestOptions::HEADERS => [
                'Authorization' => data_get($this->credentials, 'api_key'),
            ],
            RequestOptions::FORM_PARAMS => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    /**
     * 取得玩家下注紀錄
     *
     * Time format: RFC3339 2018-10-18T05:47:51+05:00
     *
     * @return array|null
     */
    public function getBetRecord(string $startTime, string $endTime, int $page = 1, int $pagesize = 500)
    {
        if ($pagesize < 500 || $pagesize > 20000) {
            throw new InvalidArgumentException('Pagesize need > 500 and <= 20000. pagesize:' . $pagesize);
        }

        $parameters = [
            'starttime' => $startTime,
            'endtime' => $endTime,
            'page' => $page,
            'pagesize' => $pagesize,
        ];

        $response = $this->client->get($this->getEndpointUrl($this->apiUrl, 'gameboy/order/view'), [
            RequestOptions::HEADERS => [
                'Authorization' => data_get($this->credentials, 'api_key'),
            ],
            RequestOptions::QUERY => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    protected function getEndpointUrl(string $url, string $endpoint): string
    {
        return "{$url}/{$endpoint}";
    }
}
