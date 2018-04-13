<?php
declare(strict_types=1);

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
    private const BALANCE = '100.00';

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
     * @var User
     */
    private $user;

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->producer = $I->grabService('old_sound_rabbit_mq.user_balance_transaction_producer');
        $this->serializer = $I->grabService('serializer');
        $this->em = $I->grabService('doctrine.orm.entity_manager');
        $user = $this->getUser();
        $user->setBalance(self::BALANCE);
        $this->em->flush($user);
        $this->deleteTransaction();
        $this->em->clear();
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionDebit(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(1, 1, '45.67', UserBalanceTransactionTypeEnum::DEBIT),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('54.33', $this->getUser()->getBalance());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transactionCredit(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(1, 1, '7.89', UserBalanceTransactionTypeEnum::CREDIT),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('107.89', $this->getUser()->getBalance());
    }

    /**
     * @param FunctionalTester $I
     */
    public function transaction(FunctionalTester $I): void
    {
        $request = $this->serializer->serialize(
            new UserBalanceTransactionDto(1, 1, '7.89', UserBalanceTransactionTypeEnum::CREDIT),
            'json'
        );
        $this->producer->publish($request);
        sleep(5);
        $I->assertSame('107.89', $this->getUser()->getBalance());
    }

    private function deleteTransaction(): void
    {
        $this->em->remove($this->em->getReference(UserBalanceTransaction::class, self::TRANSACTION_ID));
        $this->em->flush();
    }

    /**
     * @return User
     */
    private function getUser(): User
    {
        $this->em->clear();
        return $this->em->getRepository(User::class)->find(self::USER_ID);
    }
}