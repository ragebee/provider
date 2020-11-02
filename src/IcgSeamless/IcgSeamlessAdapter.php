<?php

namespace Ragebee\Provider\IcgSeamless;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Ragebee\FishpondRecord\BetRecordMethodTrait;
use Ragebee\FishpondRecord\CanNormalizeBetRecord;
use Ragebee\Fishpond\Adapter\CanFetchRecords;
use Ragebee\Fishpond\Config;
use Ragebee\Fishpond\Game;
use Ragebee\Fishpond\GameInterface;
use Ragebee\Fishpond\PlayerInterface;
use Ragebee\Fishpond\Type;
use Ragebee\Fishpond\TypeInterface;
use Ragebee\Provider\IcgSeamless\IcgSeamlessClient;

class IcgSeamlessAdapter implements CanFetchRecords, CanNormalizeBetRecord
{
    use BetRecordMethodTrait;

    /** @var IcgSeamlessClient */
    protected $client;

    /** @var string */
    protected $channel;

    /** @var string */
    protected $agent;

    const SUPPORTED_GAME_TYPES = [
        'slot',
        // 'fish',
    ];

    const GAME_TYPE_MAP = [
        'slot' => Type::GAME_SLOT,
        'fish' => Type::GAME_FISHING,
    ];

    const SUCCESS_CODES = [200];

    const LANGUAGES = [
        'zh_CN' => 'zh',
        'en_US' => 'en',
    ];

    public function __construct(IcgSeamlessClient $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function getGameList(TypeInterface $type = null)
    {
        $response = $this->client->gameList();

        if (!is_array($response)) {
            return false;
        }

        $gameList = [];
        foreach ($response['data'] as $game) {
            if (!in_array($game['type'], self::SUPPORTED_GAME_TYPES)) {
                continue;
            }

            $normalizeGame = new Game(
                $game['name'],
                $game['productId'],
                self::GAME_TYPE_MAP[$game['type']],
                [[
                    'code' => 'zh_CN',
                    'value' => $game['name'],
                ], [
                    'code' => 'en_US',
                    'value' => $game['name'],
                ]]
            );

            $normalizeGame->meta = ['href' => $game['href']];

            $gameList[] = $normalizeGame;
        }

        return $gameList;
    }

    /**
     * @inheritdoc
     */
    public function prepareCreatePlayer(PlayerInterface $player, Config $config)
    {
        $player->name = Str::random(32);

        return compact('player');
    }

    /**
     * @inheritdoc
     */
    public function createPlayer(PlayerInterface $player, Config $config)
    {
        $response = $this->client->createPlayer(
            $player
        );

        // TODO
        // if (!in_array($response->getStatusCode(), self::SUCCESS_CODES)) {
        //     return false;
        // }

        return compact('player');
    }

    /**
     * @inheritdoc
     */
    public function getLoginUrl(PlayerInterface $player, GameInterface $game, Config $config)
    {
        if (!$config->get('token')) {
            throw new InvalidArgumentException('Config Token is requited.');
        }

        if (!$config->get('device')) {
            throw new InvalidArgumentException('Config Device is requited.');
        }

        $response = $this->client->loginGame(
            $config->get('token'),
            $config->get('backUrl'),
            self::LANGUAGES[$config->get('lang', 'zh')],
            $game->meta['href'],
        );

        $loginUrl = data_get($response, 'Url');

        return compact('loginUrl');
    }

    /**
     * @inheritdoc
     */
    public function logout(PlayerInterface $player, GameInterface $game, Config $config)
    {
        // TODO
        return false;
    }

    /**
     * 透過時間抓取紀錄。
     *
     * Conifg
     * - page:
     *   (int) requited.
     * - limit:
     *   (int) 500 ~ 20000, default 500.
     *
     * @param \Ragebee\Fishpond\TypeInterface $type
     * @param \DateTime $start
     * @param \DateTime $end
     * @param \Ragebee\Fishpond\Config $config
     *
     * @throws \InvalidArgumentException
     *
     * @return array|false false on failure, meta data on success
     */
    public function fetchRecords(TypeInterface $type, DateTime $start, DateTime $end, Config $config)
    {
        if ($type->getType() === TypeInterface::RECORD_BET) {
            $records = $this->fetchBetRecords($start, $end, $config);
        }

        return is_array($records) ? $records : false;
    }

    protected function fetchBetRecords(DateTime $start, DateTime $end, Config $config)
    {
        if (!$config->get('page')) {
            throw new InvalidArgumentException('Config Page is requited.');
        }

        $response = $this->client->getBetRecord(
            $this->formatDateTime($start),
            $this->formatDateTime($end),
            $config->get('page'),
            $config->get('limit') ?? 500
        );

        return data_get($response, 'Data.Result', []);
    }

    protected function formatDateTime(DateTime $dt)
    {
        return Carbon::instance($dt)->setTimezone("+00:00")->getTimestamp() * 1000;
    }
}
