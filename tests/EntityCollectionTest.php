<?php

declare(strict_types=1);

namespace LazyLib\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use LazyLib\EntityCollection;
use LazyLib\Tests\Mocks\EntityManagerMock;
use LazyLib\Tests\Stubs\Product;
use PHPUnit\Framework\TestCase;

class EntityCollectionTest extends TestCase
{
    protected EntityManagerMock|null $entityManagerMock = null;

    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('supportsIdentityColumns')
            ->willReturn(true)
        ;
        $platform->method('supportsLimitOffset')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $connection->method('getEventManager')
            ->willReturn(new EventManager())
        ;

        $connection->method('executeQuery')
            ->willReturn($this->createMock(Result::class))
        ;

        $connection->method('getParams')
            ->willReturn([])
        ;

        $this->entityManagerMock = new EntityManagerMock($connection);
    }

    public function testReadOnlyAndDistinct(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);

        $ec->setReadOnly(true);
        $this->assertFalse($ec->isInitialized());

        $this->assertEquals(0, $ec->count());
        $this->assertTrue($ec->isInitialized());
        $this->assertFalse($ec->getPaginatorQueryBuilder()->getQuery()->getHint(Query::HINT_READ_ONLY));
    }

    public function testDistinct(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);

        $ec->setUseDistinct(false);
        $this->assertFalse($ec->isInitialized());

        $this->assertEquals(0, $ec->count());
        $this->assertFalse($ec->getPaginatorQueryBuilder()->getQuery()->getHint(CountWalker::HINT_DISTINCT));
    }

    public function testUseOutputWalkers(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);
        $ec->setUseOutputWalkers(false);

        $this->assertEquals(0, $ec->count());
        $this->assertFalse($ec->getPaginator()->getUseOutputWalkers());
    }

    public function testClearSortForCountRequest(): void
    {
        $qb = $this->createQueryBuilder();
        $qb->addOrderBy('p.id', 'ASC');
        $this->assertNotEmpty($qb->getDQLPart('orderBy'));
        $ec = new EntityCollection($qb);
        $ec->clearSortForCountRequest();
        $this->assertEmpty($ec->getPaginatorQueryBuilder()->getDQLPart('orderBy'));
    }

    public function testLimitAndOffset(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);
        $ec->setLimit(2);
        $ec->setOffset(4);
        $this->assertFalse($ec->isInitialized());
        $ec->count();
        $this->assertEquals(2, $ec->getPaginator()->getQuery()->getMaxResults());
        $this->assertEquals(4, $ec->getPaginator()->getQuery()->getFirstResult());
    }

    public function testForcePartialLoadDefaultFalseAfterInitializationTrue(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);
        $ec->setForcePartialLoad(true);
        $this->assertEmpty($ec->toArray());
        $this->assertTrue($ec->getPaginator()->getQuery()->getHint(Query::HINT_FORCE_PARTIAL_LOAD));
    }

    public function testFetchJoinCollectionDefaultTrueAfterCountFalse(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);
        $this->assertFalse($ec->isInitialized());
        $this->assertEquals([], $ec->toArray());
        $this->assertTrue($ec->getPaginator()->getFetchJoinCollection());
        $this->assertEmpty($ec->count());
        $this->assertTrue($ec->isInitialized());
        $this->assertFalse($ec->getCountPaginator()->getFetchJoinCollection());
    }

    public function testFetchJoinCollectionSetFalse(): void
    {
        $qb = $this->createQueryBuilder();
        $ec = new EntityCollection($qb);
        $ec->setFetchJoinCollection(false);
        $this->assertEquals([], $ec->toArray());
        $this->assertFalse($ec->getPaginator()->getFetchJoinCollection());
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $qb = new QueryBuilder($this->entityManagerMock);
        $qb->add('select', 'p')
            ->add('from', Product::class . ' p');

        return $qb;
    }
}
