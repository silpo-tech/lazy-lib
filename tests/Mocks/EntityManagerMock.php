<?php

declare(strict_types=1);

namespace LazyLib\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\UnitOfWork;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManagerDecorator
{
    private UnitOfWork|null $_uowMock = null;
    private ProxyFactory|null $_proxyFactoryMock = null;

    /**
     * @throws MissingMappingDriverImplementation
     */
    public function __construct(
        Connection $conn,
        Configuration|null $config = null,
    ) {
        if ($config === null) {
            $config = new Configuration();
            $config->setProxyDir(__DIR__ . '/Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl(new AttributeDriver([]));
        }

        parent::__construct(new EntityManager($conn, $config));
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->_uowMock ?? parent::getUnitOfWork();
    }

    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     */
    public function setUnitOfWork(UnitOfWork $uow): void
    {
        $this->_uowMock = $uow;
    }

    public function setProxyFactory(ProxyFactory $proxyFactory): void
    {
        $this->_proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->_proxyFactoryMock ?? parent::getProxyFactory();
    }
}
