<?php

namespace Ragebee\Provider\Cq9Seamless;

use Carbon\Carbon;
use Ragebee\FishpondRecord\AbstractBetRecordMethod;
use Ragebee\FishpondRecord\BetRecordInterface;
use Ragebee\FishpondRecord\DisplayData;
use Ragebee\Fishpond\Config;

class Cq9SeamlessBetRecordMethod extends AbstractBetRecordMethod
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getCreatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'bettime'));
    }

    public function getUpdatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'endroundtime'));
    }

    public function getBetId($betRecord): string
    {
        return data_get($betRecord, 'round');
    }

    public function getRoundId($betRecord): string
    {
        return data_get($betRecord, 'round');
    }

    public function getPlayerName($record): string
    {
        return data_get($record, 'account');
    }

    public function getGameCode($record): string
    {
        return data_get($record, 'gamecode');
    }

    public function getStatus($record): int
    {
        return BetRecordInterface::STATUS_COMPLETED;
    }

    public function getBetAmount($betRecord): string
    {
        return data_get($betRecord, 'bet');
    }

    public function getValidBetAmount($betRecord): string
    {
        return data_get($betRecord, 'bet');
    }

    public function getPayment($betRecord): ?string
    {
        return data_get($betRecord, 'win');
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
