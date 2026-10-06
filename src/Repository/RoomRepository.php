<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function findRecentlyUpdated(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByOwnerWithFilter(User $user, ?string $search, ?string $status, ?string $sortOption, ?string $sortDirection, int $page = 1, int $limit = 15): array
    {
        $sortDirections = [
            'desc' => 'DESC',
            'asc' => 'ASC',
        ];

        $sortOptions = [
            'updated' => 'r.updatedAt',
            'created' => 'r.createdAt',
            'name' => 'r.name',
        ];

        $sortOption = $sortOptions[$sortOption] ?? $sortOptions['updated'];
        $sortDirection = $sortDirections[$sortDirection] ?? $sortDirections['desc'];

        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.owner = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        $qb->orderBy($sortOption, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countByOwnerWithFilter(User $user, ?string $search, ?string $status): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.owner = :owner')
            ->setParameter('owner', $user);

        if ($search) {
            $qb->andWhere('r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function hasRooms(User $user): bool
    {
        return $this->createQueryBuilder('r')
                ->select('r.id')
                ->where('r.owner = :owner')
                ->setParameter('owner', $user)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult() !== null;
    }
}
