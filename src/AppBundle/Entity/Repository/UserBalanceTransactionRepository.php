<?php
declare(strict_types = 1);

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class UserBalanceTransactionRepository extends EntityRepository
{
    public function getFreshAccessTokenByPartyAndStatusAndType(int $partyId, int $statusId, int $typeId)
    {
        $currentTime = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('AccessToken');

        $qb->innerJoin('AccessToken.status', 'Status');
        $qb->innerJoin('AccessToken.type', 'Type');
        $qb->where('AccessToken.partyId = :partyId');
        $qb->andWhere('AccessToken.effDate <= :effDate');
        $qb->andWhere('AccessToken.expDate > :expDate');
        $qb->andWhere('Status.id = :statusId');
        $qb->andWhere('Type.id = :typeId');
        $qb->setParameters([
            'partyId' => $partyId,
            'effDate' => $currentTime,
            'expDate' => $currentTime,
            'statusId' => $statusId,
            'typeId' => $typeId,
        ]);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}