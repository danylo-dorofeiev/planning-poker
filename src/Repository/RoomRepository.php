<?php

namespace App\Repository;

use App\Entity\Deck;
use App\Entity\Room;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function findRoomsByUserWithFilter(
        User $user,
        ?string $search,
        ?string $status,
        ?string $sortOption,
        ?string $sortDirection,
        ?bool $onlyOwned,
        int $page = 1,
        int $limit = 15
    ): array {
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
            ->leftJoin(
                'r.members',
                'rm',
                Join::WITH,
                'rm.user = :user'
            )
            ->setParameter('user', $user)
            ->distinct();

        if ($search) {
            $qb->andWhere('r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        if ($onlyOwned) {
            $qb->andWhere('r.owner = :user');
        } else {
            $qb->andWhere('r.owner = :user OR rm.id IS NOT NULL');
        }

        return $qb
            ->orderBy($sortOption, $sortDirection)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countRoomsByUserWithFilter(
        User $user,
        ?string $search = null,
        ?string $status = null,
        ?bool $onlyOwned = false,
    ): int {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)')
            ->leftJoin('r.members', 'rm', Join::WITH, 'rm.user = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        if ($onlyOwned) {
            $qb->andWhere('r.owner = :user');
        } else {
            $qb->andWhere('r.owner = :user OR rm.id IS NOT NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findRecentlyUpdated(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.members', 'rm', Join::WITH, 'rm.user = :user')
            ->andWhere('r.owner = :user OR rm.id IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function hasRooms(User $user): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('r.id')
            ->leftJoin('r.members', 'rm', Join::WITH, 'rm.user = :user')
            ->where('r.owner = :user OR rm.id IS NOT NULL')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
