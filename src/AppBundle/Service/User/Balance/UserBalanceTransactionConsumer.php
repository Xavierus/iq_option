<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance;

use AppBundle\Dto\UserBalanceTransactionDto;
use AppBundle\Entity\UserBalanceTransaction;
use AppBundle\Enum\UserBalanceTransactionStateEnum;
use AppBundle\Service\User\Balance\Transaction\BalanceTransactionFactory;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Serializer\Serializer;
use Throwable;

class UserBalanceTransactionConsumer implements ConsumerInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var BalanceTransactionFactory
     */
    private $balanceTransactionFactory;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserBalanceTransactionFinishedProducer
     */
    private $userBalanceTransactionFinishedProducer;

    /**
     * @param Serializer $serializer
     * @param BalanceTransactionFactory $balanceTransactionFactory
     * @param EntityManager $em
     * @param UserBalanceTransactionFinishedProducer $userBalanceTransactionFinishedProducer
     */
    public function __construct(
        Serializer $serializer,
        BalanceTransactionFactory $balanceTransactionFactory,
        EntityManager $em,
        UserBalanceTransactionFinishedProducer $userBalanceTransactionFinishedProducer
    ) {
        $this->serializer = $serializer;
        $this->balanceTransactionFactory = $balanceTransactionFactory;
        $this->em = $em;
        $this->userBalanceTransactionFinishedProducer = $userBalanceTransactionFinishedProducer;
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

            $balanceTransactionProcessor = $this->balanceTransactionFactory->createBalanceTransactionProcessor($userBalanceTransactionDto->getTypeId());
            $balanceTransactionProcessor->processTransaction($userBalanceTransactionDto);

            $userBalanceTransactionEntity = $this->em->getRepository(UserBalanceTransaction::class)
                ->find($userBalanceTransactionDto->getTransactionId());

            $this->userBalanceTransactionFinishedProducer->execute(
                $userBalanceTransactionDto->getTransactionId(),
                $userBalanceTransactionEntity->getStateId()
            );

            $this->em->commit();

            return ConsumerInterface::MSG_ACK;
        } catch (Throwable $t) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollBack();
            }

            $userBalanceTransactionEntity = $this->em->getRepository(UserBalanceTransaction::class)
                ->find($userBalanceTransactionDto->getTransactionId());
            if ($userBalanceTransactionEntity instanceof UserBalanceTransaction) {
                $userBalanceTransactionEntity->setStateId(UserBalanceTransactionStateEnum::FAILED);
                $this->em->persist($userBalanceTransactionEntity);
                $this->em->flush($userBalanceTransactionEntity);
            }

            $this->userBalanceTransactionFinishedProducer->execute(
                $userBalanceTransactionDto->getTransactionId(),
                UserBalanceTransactionStateEnum::FAILED
            );

            return ConsumerInterface::MSG_REJECT;
        }
    }
}