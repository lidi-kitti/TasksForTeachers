<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function createAllTasksQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.author', 'a')
            ->addSelect('a')
            ->orderBy('t.createdAt', 'DESC');
    }
}
