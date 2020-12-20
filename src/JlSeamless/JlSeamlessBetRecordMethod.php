<?php

namespace Ragebee\Provider\JlSeamless;

use Carbon\Carbon;
use Ragebee\FishpondRecord\AbstractBetRecordMethod;
use Ragebee\FishpondRecord\BetRecordInterface;
use Ragebee\FishpondRecord\DisplayData;
use Ragebee\Fishpond\Config;

class JlSeamlessBetRecordMethod extends AbstractBetRecordMethod
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getCreatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'WagersTime'), '+07:00');
    }

    public function getUpdatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'PayoffTime'), '+07:00');
    }

    public function getBetId($betRecord): string
    {
        return data_get($betRecord, 'WagersId');
    }

    public function getRoundId($betRecord): string
    {
        return data_get($betRecord, 'WagersId');
    }

    public function getPlayerName($record): string
    {
        return data_get($record, 'Account');
    }

    public function getGameCode($record): string
    {
        return data_get($record, 'GameId');
    }

    public function getStatus($record): string
    {
        return BetRecordInterface::STATUS_SETTLED;
    }

    public function getBetAmount($betRecord): string
    {
        return data_get($betRecord, 'BetAmount');
    }

    public function getValidBetAmount($betRecord): string
    {
        return data_get($betRecord, 'BetAmount');
    }

    public function getPayment($betRecord): ?string
    {
        return data_get($betRecord, 'PayoffAmount');
    }

    public function getWinloss($betRecord): ?string
    {
        return null;
    }

    public function getDisplayData($betRecord): array
    {
        $displayData = new DisplayData($betRecord);

        return $displayData->toArray();
    }
}
