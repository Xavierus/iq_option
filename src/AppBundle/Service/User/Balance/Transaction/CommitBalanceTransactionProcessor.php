<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Enum\UserBalanceTransactionStateEnum;

class CommitBalanceTransactionProcessor extends AbstractBalanceTransactionProcessor
{
    /**
     * {@inheritdoc}
     */
    public function processTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): void
    {
        $this->em->beginTransaction();

        $userBalanceTransactionEntity = $this->getLockedUserBalanceTransaction($userBalanceTransactionDto->getTransactionId());
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
    }
}