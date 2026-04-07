<?php

namespace App\Repository;

use App\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dictionary>
 */
class DictionaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dictionary::class);
    }

    public function searchByWord(string $word): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.word LIKE :val')
            ->setParameter('val', '%'.$word.'%')
            ->getQuery()
            ->getResult();
    }
}