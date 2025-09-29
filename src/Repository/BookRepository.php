<?php
namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function searchByTitle(?string $q): array
    {
        $qb = $this->createQueryBuilder('b');
        if ($q) {
            $qb->andWhere('b.title LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        return $qb->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByGenre(?string $genre): array
    {
        $qb = $this->createQueryBuilder('b');
        if ($genre) {
            $qb->andWhere('b.genre = :genre')
                ->setParameter('genre', $genre);
        }
        return $qb->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
