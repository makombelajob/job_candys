<?php

namespace App\Repository;

use App\Entity\Visitors;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visitors>
 */
class VisitorsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visitors::class);
    }

    /**
     * Récupère tous les visiteurs,
     * du plus récemment actif au moins récemment actif.
     */
    public function findAllVisitors(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.lastVisitAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasReachedVisitLimit(
        string $ip,
        int $limit = 100
    ): bool
    {

        $result = $this->createQueryBuilder('v')
            ->select('v.id')
            ->andWhere('v.ipAddress = :ip')  
            ->andWhere('v.visitCount >= :limit')
            ->setParameter('ip', $ip)
            ->setParameter('limit', $limit)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}