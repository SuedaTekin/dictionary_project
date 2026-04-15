<?php

namespace App\Repository;

use App\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DictionaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dictionary::class);
    }

    public function findPaginated(int $page, int $limit, ?string $searchTerm = null): array
    {
        $qb = $this->createQueryBuilder('d');

        // Arama terimi varsa filtreyi uygula
        if ($searchTerm !== null && $searchTerm !== '') {
            $qb->andWhere('d.name LIKE :searchTerm OR d.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Toplam sayıyı hesapla (Filtre dahil)
        $totalQuery = clone $qb;
        $total = $totalQuery->select('count(d.id)')
            ->setFirstResult(0)
            ->getQuery()
            ->getSingleScalarResult();

        // Verileri getir
        $data = $qb->orderBy('d.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'total' => (int)$total,
            'data' => $data
        ];
    }
}