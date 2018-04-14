<?php
declare(strict_types=1);

namespace AppBundle\Service\User\Balance;

use AppBundle\Dto\UserBalanceTransactionFinishedDto;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Serializer\Serializer;

class UserBalanceTransactionFinishedProducer
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param ProducerInterface $processClientOrderProducer
     * @param Serializer $serializer
     */
    public function __construct(ProducerInterface $processClientOrderProducer, Serializer $serializer)
    {
        $this->producer = $processClientOrderProducer;
        $this->serializer = $serializer;
    }

    /**
     * @param int $transactionId
     * @param int $stateId
     */
    public function execute(int $transactionId, int $stateId): void
    {
        $this->producer->publish(
            $this->serializer->serialize(
                new UserBalanceTransactionFinishedDto($transactionId, $stateId),
                'json'
            )
        );
    }
}