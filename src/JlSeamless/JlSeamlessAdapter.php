<?php

namespace Ragebee\Provider\JlSeamless;

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
use Ragebee\Fishpond\PlayerInterface;
use Ragebee\Fishpond\Type;
use Ragebee\Fishpond\TypeInterface;
use Ragebee\Provider\JlSeamless\JlSeamlessClient;

class JlSeamlessAdapter implements CanFetchRecords, CanNormalizeBetRecord, AutoCreatePlayer
{
    use BetRecordMethodTrait;
    use AutoCreatePlayerTrait;

    /** @var JlSeamlessClient */
    protected $client;

    /** @var string */
    protected $channel;

    /** @var string */
    protected $agent;

    const SUPPORTED_GAME_TYPES = [
        1,
    ];

    const GAME_TYPE_MAP = [
        1 => Type::GAME_SLOT,
        5 => Type::GAME_FISHING,
    ];

    const SUCCESS_CODES = [0, 101];

    const LANGUAGES = [
        'zh_CN' => 'zh-CN',
        'en_US' => 'en-US',
    ];

    public function __construct(JlSeamlessClient $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function getGameList(TypeInterface $type = null)
    {
        $response = $this->client->gameList();

        if (!in_array(data_get($response, 'ErrorCode'), self::SUCCESS_CODES)) {
            return false;
        }

        $gameList = [];
        foreach ($response['Data'] as $game) {
            if (!in_array($game['GameCategoryId'], self::SUPPORTED_GAME_TYPES)) {
                continue;
            }

            $gameList[] = new Game(
                $game['name']['en-US'],
                $game['GameId'],
                self::GAME_TYPE_MAP[$game['GameCategoryId']],
                [[
                    'code' => 'zh_CN',
                    'value' => $game['name']['zh-CN'],
                ], [
                    'code' => 'en_US',
                    'value' => $game['name']['en-US'],
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
            $config->get('token'),
            $game->getCode(),
            self::LANGUAGES[$config->get('lang', 'zh-CN')],
        );

        $loginUrl = data_get($response, 'Url');

        return compact('loginUrl');
    }

    /**
     * @inheritdoc
     */
    public function logout(PlayerInterface $player, GameInterface $game, Config $config)
    {
        $response = $this->client->logout($player->getName());

        if (!in_array(data_get($response, 'ErrorCode'), self::SUCCESS_CODES)) {
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

        return data_get($response, 'Data.Result');
    }

    protected function formatDateTime(DateTime $dt)
    {
        return Carbon::instance($dt)->setTimezone("-07:00")->toDateTimeLocalString();
    }
}
