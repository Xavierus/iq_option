<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance\Transaction;

use AppBundle\Enum\UserBalanceTransactionTypeEnum;
use AppBundle\Service\Tools\MoneyCalculatorService;
use Doctrine\ORM\EntityManager;

class BalanceTransactionFactory
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MoneyCalculatorService
     */
    private $moneyCalculator;

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
     * @param int $transactionTypeId
     * @return AbstractBalanceTransactionProcessor
     */
    public function createBalanceTransactionProcessor(int $transactionTypeId): AbstractBalanceTransactionProcessor
    {
        switch ($transactionTypeId) {
            case UserBalanceTransactionTypeEnum::DEBIT:
                return new DebitBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            case UserBalanceTransactionTypeEnum::CREDIT:
                return new CreditBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            case UserBalanceTransactionTypeEnum::LOCK:
                return new LockBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            case UserBalanceTransactionTypeEnum::COMMIT:
                return new CommitBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            case UserBalanceTransactionTypeEnum::ROLLBACK:
                return new RollbackBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            case UserBalanceTransactionTypeEnum::TRANSFER:
                return new TransferBalanceTransactionProcessor($this->em, $this->moneyCalculator);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unknown transaction type = %d', $transactionTypeId));
        }
    }
}