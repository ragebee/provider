<?php

namespace Ragebee\Provider\IcgSeamless;

use Carbon\Carbon;
use Ragebee\FishpondRecord\AbstractBetRecordMethod;
use Ragebee\FishpondRecord\BetRecordInterface;
use Ragebee\FishpondRecord\DisplayData;
use Ragebee\Fishpond\Config;

class IcgSeamlessBetRecordMethod extends AbstractBetRecordMethod
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getCreatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'createdAt'));
    }

    public function getUpdatedAt($betRecord): ?Carbon
    {
        return Carbon::parse(data_get($betRecord, 'updatedAt'));
    }

    public function getBetId($betRecord): string
    {
        return data_get($betRecord, 'id');
    }

    public function getRoundId($betRecord): string
    {
        return data_get($betRecord, 'setId');
    }

    public function getPlayerName($record): string
    {
        return data_get($record, 'player');
    }

    public function getGameCode($record): string
    {
        return data_get($record, 'productId');
    }

    public function getStatus($record): string
    {
        $status = data_get($record, 'status');

        switch ($status) {
            case 'playing':
                return BetRecordInterface::STATUS_OPEN;
            case 'cancel':
                return BetRecordInterface::STATUS_CANCELED;
            case 'finish':
                return BetRecordInterface::STATUS_SETTLED;
        }
    }

    public function getBetAmount($betRecord): string
    {
        return (string) (data_get($betRecord, 'bet') / 100);
    }

    public function getValidBetAmount($betRecord): string
    {
        return (string) (data_get($betRecord, 'validBet') / 100);
    }

    public function getPayment($betRecord): ?string
    {
        return (string) (data_get($betRecord, 'validBet') / 100);
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
