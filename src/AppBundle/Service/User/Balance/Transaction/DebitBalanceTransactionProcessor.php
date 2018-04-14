<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Enum\UserBalanceTransactionStateEnum;
use RangeException;

class DebitBalanceTransactionProcessor extends AbstractBalanceTransactionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function processTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): void
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->em->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->substract($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        if ($newBalance < 0) {
            throw new RangeException('New user balance is below zero');
        }
        $userEntity->setBalance($newBalance);

        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
    }
}