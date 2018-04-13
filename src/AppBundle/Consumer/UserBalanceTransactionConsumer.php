<?php
declare(strict_types=1);

namespace AppBundle\Consumer;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Entity\Repository\UserBalanceTransactionRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBalanceTransaction;
use AppBundle\Enum\UserBalanceTransactionStateEnum;
use AppBundle\Enum\UserBalanceTransactionTypeEnum;
use AppBundle\Service\Tools\MoneyCalculatorService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use RangeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

class UserBalanceTransactionConsumer implements ConsumerInterface
{
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
        Serializer $serializer,
        Connection $connection,
        UserBalanceTransactionRepository $userBalanceTransactionRepository,
        EntityManager $em,
        MoneyCalculatorService $moneyCalculator
    ) {
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

            switch ($userBalanceTransactionDto->getTypeId()) {
                case UserBalanceTransactionTypeEnum::DEBIT:
                    $this->processDebitTransaction($userBalanceTransactionDto);
                    break;

                case UserBalanceTransactionTypeEnum::CREDIT:
                    $this->processCreditTransaction($userBalanceTransactionDto);
                    break;

                case UserBalanceTransactionTypeEnum::LOCK:
                    $this->processLockTransaction($userBalanceTransactionDto);
                    break;

                case UserBalanceTransactionTypeEnum::COMMIT:
                    $this->processCommitTransaction($userBalanceTransactionDto);
                    break;

                case UserBalanceTransactionTypeEnum::ROLLBACK:
                    $this->processRollbackTransaction($userBalanceTransactionDto);
                    break;

                case UserBalanceTransactionTypeEnum::TRANSFER:
                    $this->processTransferTransaction($userBalanceTransactionDto);
                    break;

                default:
                    throw new InvalidArgumentException('Unknown transaction type');
            }

            return ConsumerInterface::MSG_ACK;
        } catch (Throwable $t) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $userBalanceTransactionEntity = $this->userBalanceTransactionRepository->find($userBalanceTransactionDto->getTransactionId());
            if ($userBalanceTransactionEntity instanceof UserBalanceTransaction) {
                $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::FAILED);
                $this->em->persist($userBalanceTransactionEntity);
                $this->em->flush($userBalanceTransactionEntity);
            }

            return ConsumerInterface::MSG_REJECT;
        }
    }

    private function processDebitTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->connection->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->substract($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        if ($newBalance < 0) {
            throw new RangeException('New user balance is below zero');
        }
        $userEntity->setBalance($newBalance);

        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
        $this->connection->commit();
    }

    private function processCreditTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->connection->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->sum($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        $userEntity->setBalance($newBalance);
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
        $this->connection->commit();
    }

    private function processLockTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->connection->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->substract($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        if ($newBalance < 0) {
            throw new RangeException('New user balance is below zero');
        }
        $userEntity->setBalance($newBalance);

        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::LOCKED);

        $this->em->flush();
        $this->connection->commit();
    }

    private function processCommitTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->getLockedUserBalanceTransaction($userBalanceTransactionDto->getTransactionId());

        $this->connection->beginTransaction();

        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
        $this->connection->commit();
    }

    private function processRollbackTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->getLockedUserBalanceTransaction($userBalanceTransactionDto->getTransactionId());

        $this->connection->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionEntity->getUser()->getUserId());
        $newBalance = $this->moneyCalculator->sum($userEntity->getBalance(), $userBalanceTransactionEntity->getSum());
        $userEntity->setBalance($newBalance);
        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::ROLLEDBACK);

        $this->em->flush();
        $this->connection->commit();
    }

    private function processTransferTransaction(UserBalanceTransactionDto $userBalanceTransactionDto)
    {
        $userBalanceTransactionEntity = $this->createUserBalanceTransaction($userBalanceTransactionDto);

        $this->connection->beginTransaction();

        $userEntity = $this->getUser($userBalanceTransactionDto->getUserId());
        $newBalance = $this->moneyCalculator->substract($userEntity->getBalance(), $userBalanceTransactionDto->getSum());
        if ($newBalance < 0) {
            throw new RangeException('New user balance is below zero');
        }
        $userEntity->setBalance($newBalance);

        $userDestinationEntity = $this->getUser($userBalanceTransactionDto->getUserIdDestination());
        $newBalance = $this->moneyCalculator->sum($userDestinationEntity->getBalance(), $userBalanceTransactionDto->getSum());
        $userDestinationEntity->setBalance($newBalance);

        $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::COMMITED);

        $this->em->flush();
        $this->connection->commit();
    }

    /**
     * @param UserBalanceTransactionDto $userBalanceTransactionDto
     * @return UserBalanceTransaction
     */
    private function createUserBalanceTransaction(UserBalanceTransactionDto $userBalanceTransactionDto): UserBalanceTransaction
    {
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
        $userIdDestination = $userBalanceTransactionDto->getUserIdDestination();
        if (!is_null($userIdDestination)) {
            $userBalanceTransactionEntity->setUserBalanceTransactionId($this->em->getReference(User::class, $userIdDestination));
        }

        $this->em->persist($userBalanceTransactionEntity);
        $this->em->flush($userBalanceTransactionEntity);

        return $userBalanceTransactionEntity;
    }

    private function getLockedUserBalanceTransaction(int $userBalanceTransactionId): UserBalanceTransaction
    {
        $userBalanceTransactionEntity = $this->userBalanceTransactionRepository->find($userBalanceTransactionId);
        if (!$userBalanceTransactionEntity instanceof UserBalanceTransaction) {
            throw new InvalidArgumentException('Transaction could not be found exists');
        }

        if ($userBalanceTransactionEntity->getStateId() !== UserBalanceTransactionStateEnum::LOCKED) {
            throw new InvalidArgumentException('Transaction could not be commited or rolledback because it is not locked');
        }

        return $userBalanceTransactionEntity;
    }

    /**
     * @param int $userId
     * @return User
     */
    private function getUser(int $userId): User
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