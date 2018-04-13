<?php
declare(strict_types=1);

namespace AppBundle\Consumer;

use AppBundle\Service\Tools\MoneyCalculatorService;
use Doctrine\DBAL\LockMode;
use AppBundle\Entity\Repository\UserBalanceTransactionRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBalanceTransaction;
use AppBundle\Enum\UserBalanceTransactionTypeEnum;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use AppBundle\Enum\UserBalanceTransactionStateEnum;
use RangeException;
use AppBundle\Dto\UserBalanceTransactionDto;
use Doctrine\DBAL\Connection;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

class UserBalanceTransactionConsumer implements ConsumerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var UserBalanceTransactionRepository
     */
    private $userBalanceTransactionRepository;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MoneyCalculatorService
     */
    private $moneyCalculator;

    public function __construct(
        LoggerInterface $logger,
        Serializer $serializer,
        Connection $connection,
        UserBalanceTransactionRepository $userBalanceTransactionRepository,
        EntityManager $em,
        MoneyCalculatorService $moneyCalculator
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->userBalanceTransactionRepository = $userBalanceTransactionRepository;
        $this->em = $em;
        $this->moneyCalculator = $moneyCalculator;
    }

    /**
     * @param AMQPMessage $msg
     *
     * @return int
     * @throws Throwable
     */
    public function execute(AMQPMessage $msg): int
    {
        //return ConsumerInterface::MSG_REJECT;
        try {
            $this->em->clear();
            /** @var UserBalanceTransactionDto $userBalanceTransactionDto */
            $userBalanceTransactionDto = $this->serializer->deserialize(
                $msg->getBody(),
                UserBalanceTransactionDto::class,
                'json'
            );

            $userBalanceTransactionEntity = $this->userBalanceTransactionRepository->find($userBalanceTransactionDto->getTransactionId());
            if ($userBalanceTransactionEntity instanceof UserBalanceTransaction) {
                throw new InvalidArgumentException('Transaction already exists');
            }

            $userBalanceTransactionEntity = new UserBalanceTransaction();
            $userBalanceTransactionEntity->setUserBalanceTransactionId($userBalanceTransactionDto->getTransactionId());
            $userBalanceTransactionEntity->setUser($this->em->getReference(User::class, $userBalanceTransactionDto->getUserId()));
            $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::DIRTY);
            $userBalanceTransactionEntity->setSum($userBalanceTransactionDto->getSum());
            $userBalanceTransactionEntity->setTypeId($userBalanceTransactionDto->getTypeId());
            $this->em->persist($userBalanceTransactionEntity);
            $this->em->flush($userBalanceTransactionEntity);

            $this->connection->beginTransaction();

            /** @var User $userEntity */
            $userEntity = $this->em->getRepository(User::class)->find(
                $userBalanceTransactionDto->getUserId(),
                LockMode::PESSIMISTIC_WRITE
            );
            if (!$userEntity instanceof User) {
                throw new InvalidArgumentException('Could not find user');
            }

            $transactionSum = $userBalanceTransactionDto->getSum();
            if ($userBalanceTransactionDto->getTypeId() === UserBalanceTransactionTypeEnum::DEBIT) {
                $newBalance = $this->moneyCalculator->substract($userEntity->getBalance(), $transactionSum);
                if ($newBalance < 0) {
                    throw new RangeException('New user balance is below zero');
                }
            } else {
                $newBalance = $this->moneyCalculator->sum($userEntity->getBalance(), $transactionSum);
            }

            $userEntity->setBalance($newBalance);
            $this->em->persist($userEntity);
            $this->em->flush($userEntity);

            $this->connection->commit();

            return ConsumerInterface::MSG_ACK;
        } catch (Throwable $t) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            if (isset($userBalanceTransactionEntity)) {
                $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::FAILED);
                $this->em->persist($userBalanceTransactionEntity);
                $this->em->flush($userBalanceTransactionEntity);
            }

            return ConsumerInterface::MSG_REJECT;
        }
    }
}