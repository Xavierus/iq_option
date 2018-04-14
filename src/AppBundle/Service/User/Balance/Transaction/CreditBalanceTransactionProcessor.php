<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Enum\UserBalanceTransactionStateEnum;

class CreditBalanceTransactionProcessor extends AbstractBalanceTransactionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function processTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): void
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->em->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->sum($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        $userEntity->setBalance($newBalance);
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
    }
}