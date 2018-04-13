<?php
declare(strict_types=1);

namespace AppBundle\Dto;

class UserBalanceTransactionDto implements \JsonSerializable
{
    /**
     * @var int
     */
    private $transactionId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $sum;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @param int $transactionId
     * @param int $userId
     * @param string $sum
     * @param int $typeId
     */
    public function __construct(int $transactionId, int $userId, string $sum, int $typeId)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->sum = $sum;
        $this->typeId = $typeId;
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getSum(): string
    {
        return $this->sum;
    }

    /**
     * @return int
     */
    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function jsonSerialize()
    {
        return [
            'transaction_id' => $this->transactionId,
            'user_id' => $this->userId,
            'sum' => $this->sum,
            'type_id' => $this->typeId,
        ];
    }
}