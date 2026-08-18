<?php

namespace App\Repository;

use App\Entity\FreelancePropositions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Companies;
use App\Entity\Profils;

/**
 * @extends ServiceEntityRepository<FreelancePropositions>
 */
class FreelancePropositionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FreelancePropositions::class);
    }

    //    /**
    //     * @return FreelancePropositions[] Returns an array of FreelancePropositions objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?FreelancePropositions
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function hasProposalForProfileAndCompany(
        Profils $profil,
        Companies $company
    ): bool {
        return $this->createQueryBuilder('fp')
                ->select('1')
                ->andWhere('fp.profils = :profil')
                ->andWhere('fp.companies = :company')
                ->setParameter('profil', $profil)
                ->setParameter('company', $company)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult() !== null;
    }
}
