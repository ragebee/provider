<?php

namespace Ragebee\Provider\IcgSeamless;

use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Ragebee\Fishpond\PlayerInterface;

class IcgSeamlessClient
{
    const API_URL = 'https://admin-stage.iconic-gaming.com/service';

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

    public function gameList($lang = 'zh')
    {
        $parameters = [
            'type' => 'slot',
            'lang' => $lang,
        ];

        $response = $this->client->GET($this->getEndpointUrl($this->apiUrl, 'api/v1/games'), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->credentials['token'],
            ],
            RequestOptions::QUERY => $parameters,
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    public function createPlayer(PlayerInterface $player)
    {
        $response = $this->client->post($this->getEndpointUrl($this->apiUrl, 'api/v1/players'), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->credentials['token'],
            ],
            RequestOptions::JSON => [
                'username' => $player->name,
            ],
        ]);

        return json_decode($response->getBody(), true) ?: (string) $response->getBody();
    }

    public function loginGame(
        string $token,
        string $homeUrl,
        string $lang = 'zh',
        string $href
    ) {
        $parameters = [
            'token' => $token,
            'home_URL' => $homeUrl,
            'Lang' => $lang,
        ];

        return [
            'Url' => $href . '&' . http_build_query($parameters),
        ];
    }

    /**
     * 取得玩家下注紀錄
     *
     * Time format: 時區 +0, 需補到毫秒 Ex. 1566230400000
     *
     * @return array|null
     */
    public function getBetRecord(string $startTime, string $endTime, int $page = 1, int $pagesize = 500)
    {
        if ($pagesize < 10 || $pagesize > 10000) {
            throw new InvalidArgumentException('Pagesize need > 10 and <= 10000. pagesize:' . $pagesize);
        }

        $parameters = [
            'start' => $startTime,
            'end' => $endTime,
            'page' => $page,
            'pageSize' => $pagesize,
        ];

        $response = $this->client->get($this->getEndpointUrl($this->apiUrl, 'api/v1/profile/rounds'), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->credentials['token'],
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
