<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Enum\UserBalanceTransactionStateEnum;

class RollbackBalanceTransactionProcessor extends AbstractBalanceTransactionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function processTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): void
    {
        $this->em->beginTransaction();

        $userBalanceTransactionEntity = $this->getLockedUserBalanceTransaction($userBalanceTransactionDto->getTransactionId());

        $userEntity = $this->getUser($userBalanceTransactionEntity->getUser()->getUserId());
        $newBalance = $this->moneyCalculator->sum($userEntity->getBalance(), $userBalanceTransactionEntity->getSum());
        $userEntity->setBalance($newBalance);
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::ROLLEDBACK);

        $this->em->flush();
    }
}