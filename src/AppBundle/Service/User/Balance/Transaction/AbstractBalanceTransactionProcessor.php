<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBalanceTransaction;
use AppBundle\Enum\UserBalanceTransactionStateEnum;
use AppBundle\Service\Tools\MoneyCalculatorService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

abstract class AbstractBalanceTransactionProcessor
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MoneyCalculatorService
     */
    protected $moneyCalculator;

    /**
     * @param EntityManager $em
     * @param MoneyCalculatorService $moneyCalculator
     */
    public function __construct(EntityManager $em, MoneyCalculatorService $moneyCalculator)
    {
        $this->em = $em;
        $this->moneyCalculator = $moneyCalculator;
    }

    /**
     * @param UserBalanceTransactionDto $userBalanceTransactionDto
     */
    abstract public function processTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): void;

    /**
     * @param UserBalanceTransactionDto $userBalanceTransactionDto
     * @return UserBalanceTransaction
     */
    protected function createUserBalanceTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): UserBalanceTransaction
    {
        $userBalanceTransactionEntity = $this->em->getRepository(UserBalanceTransaction::class)
            ->find($userBalanceTransactionDto->getTransactionId());
        if ($userBalanceTransactionEntity instanceof UserBalanceTransaction) {
            throw new InvalidArgumentException(sprintf('Transaction with id = %d already exists', $userBalanceTransactionDto->getTransactionId()));
        }

        $userBalanceTransactionEntity = new UserBalanceTransaction();
        $userBalanceTransactionEntity->setUserBalanceTransactionId($userBalanceTransactionDto->getTransactionId());
        $userBalanceTransactionEntity->setUser($this->em->getReference(User::class, $userBalanceTransactionDto->getUserId()));
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::DIRTY);
        $userBalanceTransactionEntity->setSum($userBalanceTransactionDto->getSum());
        $userBalanceTransactionEntity->setTypeId($userBalanceTransactionDto->getTypeId());
        $userIdDestination = $userBalanceTransactionDto->getUserIdDestination();
        if (!is_null($userIdDestination)) {
            $userBalanceTransactionEntity->setUserDestination($this->em->getReference(User::class, $userIdDestination));
        }

        $this->em->persist($userBalanceTransactionEntity);
        $this->em->flush($userBalanceTransactionEntity);

        return $userBalanceTransactionEntity;
    }

    /**
     * @param int $userBalanceTransactionId
     * @return UserBalanceTransaction
     */
    protected function getLockedUserBalanceTransaction(int $userBalanceTransactionId): UserBalanceTransaction
    {
        $userBalanceTransactionEntity = $this->em->getRepository(UserBalanceTransaction::class)
            ->find($userBalanceTransactionId, LockMode::PESSIMISTIC_WRITE);
        if (!$userBalanceTransactionEntity instanceof UserBalanceTransaction) {
            throw new InvalidArgumentException(sprintf('Transaction with id = %d could not be found', $userBalanceTransactionId));
        }

        if ($userBalanceTransactionEntity->getStateId() !== UserBalanceTransactionStateEnum::LOCKED) {
            throw new InvalidArgumentException(sprintf(
                'Transaction with id = %d could not be commited or rolledback because it is not locked',
                $userBalanceTransactionId
            ));
        }

        return $userBalanceTransactionEntity;
    }

    /**
     * @param int $userId
     * @return User
     */
    protected function getUser(int $userId): User
    {
        /** @var User $userEntity */
        $userEntity = $this->em->getRepository(User::class)->find(
            $userId,
            LockMode::PESSIMISTIC_WRITE
        );
        if (!$userEntity instanceof User) {
            throw new InvalidArgumentException('Could not find user');
        }
        return $userEntity;
    }
}