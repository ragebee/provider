<?php

namespace Ragebee\Provider\Cq9Seamless;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Ragebee\FishpondRecord\BetRecordMethodTrait;
use Ragebee\FishpondRecord\CanNormalizeBetRecord;
use Ragebee\Fishpond\Adapter\AutoCreatePlayer;
use Ragebee\Fishpond\Adapter\CanFetchRecords;
use Ragebee\Fishpond\Adapter\Polyfill\AutoCreatePlayerTrait;
use Ragebee\Fishpond\Config;
use Ragebee\Fishpond\Game;
use Ragebee\Fishpond\GameInterface;
use Ragebee\Fishpond\OperatorConstant;
use Ragebee\Fishpond\PlayerInterface;
use Ragebee\Fishpond\Type;
use Ragebee\Fishpond\TypeInterface;

class Cq9SeamlessAdapter implements CanFetchRecords, CanNormalizeBetRecord, AutoCreatePlayer
{
    use BetRecordMethodTrait;
    use AutoCreatePlayerTrait;

    /** @var Cq9SeamlessClient */
    protected $client;

    /** @var string */
    protected $channel;

    /** @var string */
    protected $agent;

    const SUPPORTED_GAME_TYPES = [
        'slot',
        // 'fish',
        'table',
        'arcade',
    ];

    const GAME_TYPE_MAP = [
        'slot' => Type::GAME_SLOT,
        'fish' => Type::GAME_FISHING,
        'table' => Type::GAME_SLOT,
        'arcade' => Type::GAME_SLOT,
    ];

    const SUCCESS_CODES = ["0"];

    const LANGUAGES = [
        'zh_CN' => 'zh-cn',
        'en_US' => 'en_us',
        'vi_VN' => 'vn',
        'th_TH' => 'th',
    ];

    public function __construct(Cq9SeamlessClient $client)
    {
        $this->client = $client;
    }

    public static function getRequiredCredentialsKeys()
    {
        return [
            OperatorConstant::CONFIG_KEY_API_URL,
            OperatorConstant::CONFIG_KEY_OPERATOR_TOKEN,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getGameList(TypeInterface $type = null)
    {
        $response = $this->client->gameList();

        if (!in_array(data_get($response, 'status.code'), self::SUCCESS_CODES)) {
            return false;
        }

        $gameList = [];
        foreach ($response['data'] as $game) {
            // if ($game['status'] !== true) {
            //     continue;
            // }

            if (!in_array($game['gametype'], self::SUPPORTED_GAME_TYPES)) {
                continue;
            }

            $nameset = [];
            foreach ($game['nameset'] as $nameArray) {
                $nameset[$nameArray['lang']] = $nameArray['name'];
            }

            $gameList[] = new Game(
                $game['gamename'],
                $game['gamecode'],
                self::GAME_TYPE_MAP[$game['gametype']],
                [[
                    'code' => 'zh_CN',
                    'value' => $nameset['zh-cn'],
                ], [
                    'code' => 'en_US',
                    'value' => $nameset['en'] ?? '',
                ]]
            );
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
    public function getLoginUrl(PlayerInterface $player, GameInterface $game, Config $config)
    {
        if (!$config->get('token')) {
            throw new InvalidArgumentException('Config Token is requited.');
        }

        if (!$config->get('device')) {
            throw new InvalidArgumentException('Config Device is requited.');
        }

        $response = $this->client->loginGame(
            $player->getName(),
            'CQ9',
            $game->getCode(),
            $config->get('device') === 'pc' ? 'web' : 'mobile',
            self::LANGUAGES[$config->get('lang', 'zh_CN')],
            $config->get('token'),

        );

        if (!in_array(data_get($response, 'status.code'), self::SUCCESS_CODES)) {
            return false;
        }

        $loginUrl = data_get($response, 'data.url');

        if ($config->get('backUrl')) {
            $loginUrl = $loginUrl . '&leaveurl=' . $config->get('backUrl');
        }

        return compact('loginUrl');
    }

    /**
     * @inheritdoc
     */
    public function logout(PlayerInterface $player, GameInterface $game, Config $config)
    {
        $response = $this->client->logout($player->getName());

        if (!in_array(data_get($response, 'status.code'), self::SUCCESS_CODES)) {
            return false;
        }

        return true;
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

        if (data_get($response, 'status.code') === '8') {
            return [];
        }

        return data_get($response, 'data.Data');
    }

    protected function formatDateTime(DateTime $dt)
    {
        return Carbon::instance($dt)->setTimezone("-04:00")->toIso8601String();
    }
}
