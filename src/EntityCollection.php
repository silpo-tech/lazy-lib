<?php

declare(strict_types=1);

namespace LazyLib;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use PaginatorBundle\Paginator\PaginatableInterface;

class EntityCollection extends AbstractLazyCollection implements PaginatableInterface
{
    private Paginator|null $paginator = null;
    private Paginator|null $countPaginator = null;

    private bool $readOnly = false;
    private bool|null $forcePartialLoad = null;
    private bool $useDistinct = true;
    private bool $useOutputWalkers = true;
    private bool $fetchJoinCollection = true;
    private QueryBuilder $paginatorQueryBuilder;

    public function __construct(private readonly QueryBuilder $queryBuilder)
    {
    }

    public function setPaginatorQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->paginatorQueryBuilder = $queryBuilder;
        $this->initialized = false;

        return $this;
    }

    public function setReadOnly(bool $readOnly): self
    {
        $this->readOnly = $readOnly;
        $this->initialized = false;

        return $this;
    }

    public function setUseDistinct(bool $useDistinct): self
    {
        $this->useDistinct = $useDistinct;
        $this->initialized = false;

        return $this;
    }

    public function setFetchJoinCollection(bool $fetchJoinCollection): self
    {
        $this->fetchJoinCollection = $fetchJoinCollection;
        $this->initialized = false;

        return $this;
    }

    public function clearSortForCountRequest(): self
    {
        $qb = clone $this->queryBuilder;
        $qb->resetDQLPart('orderBy');
        $this->setPaginatorQueryBuilder($qb);

        $this->initialized = false;

        return $this;
    }

    public function setForcePartialLoad(bool $forcePartialLoad): self
    {
        $this->forcePartialLoad = $forcePartialLoad;
        $this->initialized = false;

        return $this;
    }

    public function setUseOutputWalkers(bool $useOutputWalkers): self
    {
        $this->useOutputWalkers = $useOutputWalkers;
        $this->initialized = false;

        return $this;
    }

    public function setLimit(int $limit): void
    {
        $this->queryBuilder->setMaxResults($limit);
        $this->initialized = false;
    }

    public function setOffset(int $offset): void
    {
        $this->queryBuilder->setFirstResult($offset);
        $this->initialized = false;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function getCountPaginator(): Paginator
    {
        return $this->countPaginator;
    }

    public function getPaginatorQueryBuilder(): QueryBuilder
    {
        return $this->paginatorQueryBuilder ?? $this->queryBuilder;
    }

    /**
     * @throws Exception
     */
    protected function doInitialize(): void
    {
        $query = $this->queryBuilder->getQuery();
        $query->setHint(Query::HINT_READ_ONLY, $this->readOnly);
        $query->setHint(CountWalker::HINT_DISTINCT, $this->useDistinct);

        if (null !== $this->forcePartialLoad) {
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, $this->forcePartialLoad);
        }

        $this->paginator = $this->createPaginator($query, $this->fetchJoinCollection);

        $this->collection = new ArrayCollection(iterator_to_array($this->paginator->getIterator()));
    }

    public function count(): int
    {
        $this->initialize();

        $queryBuilder = $this->paginatorQueryBuilder ?? $this->queryBuilder;

        $query = $queryBuilder->getQuery();
        $query->setHint(CountWalker::HINT_DISTINCT, $this->useDistinct);

        $this->countPaginator = $this->createPaginator($query, false);

        return $this->countPaginator->count();
    }

    protected function createPaginator(Query $query, bool $fetchJoinCollection): Paginator
    {
        $paginator = new Paginator($query, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($this->useOutputWalkers);

        return $paginator;
    }
}
