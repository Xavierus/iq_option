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
     * @var int|null
     */
    private $userId;

    /**
     * @var int|null
     */
    private $userIdDestination;

    /**
     * @var string|null
     */
    private $sum;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @param int $transactionId
     * @param int $typeId
     * @param int|null $userId
     * @param string|null $sum
     * @param int|null $userIdDestination
     */
    public function __construct(int $transactionId, int $typeId, int $userId = null, string $sum = null, int $userIdDestination = null)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->sum = $sum;
        $this->typeId = $typeId;
        $this->userIdDestination = $userIdDestination;
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

    /**
     * @return int|null
     */
    public function getUserIdDestination(): ?int
    {
        return $this->userIdDestination;
    }

    public function jsonSerialize()
    {
        return [
            'transaction_id' => $this->transactionId,
            'user_id' => $this->userId,
            'sum' => $this->sum,
            'type_id' => $this->typeId,
            'user_id_destination' => $this->userIdDestination,
        ];
    }
}