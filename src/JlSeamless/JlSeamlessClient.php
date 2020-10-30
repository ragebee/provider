<?php

namespace Ragebee\Provider\JlSeamless;

use Carbon\Carbon;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JlSeamlessClient
{
    const API_URL = 'https://uat-wb-api.jlfafafa2.com/api1/';

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
     *   (array) an array of "agent_id", "agent_key" key.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->apiUrl = $args['api_url'] ?? self::API_URL;
        $this->client = $args['client'] ?? new GuzzleClient(['handler' => GuzzleFactory::handler()]);
        $this->credentials = $args['credentials'];
    }

    public function getKey($request = [])
    {
        return Str::random(6) . md5($this->keySortToString($request) . $this->getKeyG()) . Str::random(6);
    }

    private function keySortToString($parameter): string
    {
        $count = count($parameter);
        $i = 1;
        $parameterString = '';
        foreach ($parameter as $key => $value) {
            $parameterString .= $key . '=' . $value;
            $parameterString .= $i < $count ? '&' : '';
            $i++;
        }

        return $parameterString;
    }

    public function getKeyG()
    {
        return md5(Carbon::now("-04:00")->format('ymd') . $this->credentials['agent_id'] . $this->credentials['agent_key']);
    }

    /**
     * 取得遊戲列表
     */
    public function gameList()
    {
        $parameters = [
            'AgentId' => $this->credentials['agent_id'],
        ];

        $parameters['Key'] = $this->getKey($parameters);

        $response = $this->client->post($this->getEndpointUrl($this->apiUrl, 'GetGameList'), [
            RequestOptions::FORM_PARAMS => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    /**
     * 使用者登入遊戲
     */
    public function loginGame(
        string $token,
        string $gameId,
        string $lang = 'zh-CN'
    ) {
        $parameters = [
            'Token' => $token,
            'GameId' => $gameId,
            'Lang' => $lang,
            'AgentId' => $this->credentials['agent_id'],
        ];
        $parameters['Key'] = $this->getKey($parameters);

        return [
            'Url' => $this->apiUrl . '/singleWallet/Login?' . http_build_query($parameters),
        ];
    }

    /**
     * 使用者出遊戲
     */
    public function logout(
        string $account
    ) {
        $parameters = [
            'Account' => $account,
            'AgentId' => $this->credentials['agent_id'],
        ];

        $parameters['Key'] = $this->getKey($parameters);

        $response = $this->client->post($this->getEndpointUrl($this->apiUrl, 'api1/KickMember'), [
            RequestOptions::FORM_PARAMS => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    /**
     * 取得玩家下注紀錄
     *
     * Time format: 2018-10-18T05:47:51
     * Time zone: +07:00
     *
     * @return array|null
     */
    public function getBetRecord(string $startTime, string $endTime, int $page = 1, int $pagesize = 500)
    {
        if ($pagesize < 500 || $pagesize > 10000) {
            throw new InvalidArgumentException('Pagesize need > 500 and <= 10000. pagesize:' . $pagesize);
        }

        $parameters = [
            'StartTime' => $startTime,
            'EndTime' => $endTime,
            'Page' => (int) $page,
            'PageLimit' => (int) $pagesize,
            'AgentId' => $this->credentials['agent_id'],
        ];

        $parameters['Key'] = $this->getKey($parameters);

        $response = $this->client->get($this->getEndpointUrl($this->apiUrl, 'GetBetRecordByTime'), [
            RequestOptions::QUERY => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    protected function getEndpointUrl(string $url, string $endpoint): string
    {
        return "{$url}/api1/{$endpoint}";
    }
}
