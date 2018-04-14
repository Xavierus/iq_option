<?php
declare(strict_types=1);

namespace AppBundle\Dto;

class UserBalanceTransactionFinishedDto implements \JsonSerializable
{
    /**
     * @var int
     */
    private $transactionId;

    /**
     * @var int
     */
    private $stateId;

    /**
     * @param int $transactionId
     * @param int $stateId
     */
    public function __construct(int $transactionId, int $stateId)
    {
        $this->transactionId = $transactionId;
        $this->stateId = $stateId;
    }

    public function jsonSerialize()
    {
        return [
            'transaction_id' => $this->transactionId,
            'state_id' => $this->stateId,
        ];
    }
}