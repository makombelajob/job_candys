<?php

namespace App\Repository;

use App\Entity\Applications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Companies;
use App\Entity\Profils;

/**
 * @extends ServiceEntityRepository<Applications>
 */
class ApplicationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Applications::class);
    }

    //    /**
    //     * @return Applications[] Returns an array of Applications objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Applications
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function hasApplicationForProfileAndCompany(
        Profils $profil,
        Companies $company
    ): bool {
        return (bool) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.profils = :profil')
            ->andWhere('a.companies = :company')
            ->setParameter('profil', $profil)
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

}
