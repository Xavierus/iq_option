<?php
declare(strict_types=1);

use AppBundle\Enum\UserBalanceTransactionStateEnum;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBalanceTransaction;
use Doctrine\ORM\EntityManager;
use AppBundle\Enum\UserBalanceTransactionTypeEnum;
use AppBundle\Dto\UserBalanceTransactionDto;
use Symfony\Component\Serializer\Serializer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class UserBalanceTransactionCest
{
    private const TRANSACTION_ID  = 1;
    private const USER_ID  = 1;
    private const USER_ID_DESTINATION  = 2;
    private const BALANCE_USER_1 = '100.00';
    private const BALANCE_USER_2 = '30.00';

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->producer = $I->grabService('old_sound_rabbit_mq.user_balance_transaction_producer');
        $this->serializer = $I->grabService('serializer');
        $this->em = $I->grabService('doctrine.orm.entity_manager');

        $user = $this->getUser(self::USER_ID);
        $user->setBalance(self::BALANCE_USER_1);
        $userDestination = $this->getUser(self::USER_ID_DESTINATION);
        $userDestination->setBalance(self::BALANCE_USER_2);
        $this->em->flush();

        $this->deleteTransaction();
        $this->em->clear();
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionDebit(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::DEBIT,
                self::USER_ID,
                '45.67'
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('54.33', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::COMMITED, $transaction->getStateId());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionCredit(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::CREDIT,
                self::USER_ID,
                '7.89'
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('107.89', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::COMMITED, $transaction->getStateId());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionLockWithCommit(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::LOCK,
                self::USER_ID,
                '13.00'
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('87.00', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::LOCKED, $transaction->getStateId());

        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::COMMIT
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('87.00', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::COMMITED, $transaction->getStateId());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionLockWithRollback(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::LOCK,
                self::USER_ID,
                '13.00'
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('87.00', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::LOCKED, $transaction->getStateId());

        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::ROLLBACK
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('100.00', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::ROLLEDBACK, $transaction->getStateId());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionTransfer(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::TRANSFER,
                self::USER_ID,
                '10.00',
                self::USER_ID_DESTINATION
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('90.00', $this->getUser(self::USER_ID)->getBalance());
        $I->assertSame('40.00', $this->getUser(self::USER_ID_DESTINATION)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::COMMITED, $transaction->getStateId());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionFailedWithLowBalance(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(
                self::TRANSACTION_ID,
                UserBalanceTransactionTypeEnum::DEBIT,
                self::USER_ID,
                '425.67'
            ),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('100.00', $this->getUser(self::USER_ID)->getBalance());
        $transaction = $this->getTransaction();
        $I->assertSame(UserBalanceTransactionStateEnum::FAILED, $transaction->getStateId());
    }

    private function deleteTransaction(): void
    {
        $this->em->remove($this->em->getReference(UserBalanceTransaction::class, self::TRANSACTION_ID));
        $this->em->flush();
    }

    /**
     * @return UserBalanceTransaction
     */
    private function getTransaction(): UserBalanceTransaction
    {
        $transaction = $this->em->getRepository(UserBalanceTransaction::class)->find(self::TRANSACTION_ID);
        $this->em->refresh($transaction);
        return $transaction;
    }

    /**
     * @param int $userId
     * @return User
     */
    private function getUser(int $userId): User
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        $this->em->refresh($user);
        return $user;
    }
}