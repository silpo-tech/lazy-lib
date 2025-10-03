<?php

declare(strict_types=1);

namespace LazyLib\Tests\Stubs;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

#[ORM\Table(name: 'products_stubs')]
#[Entity]
class Product
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER, unique: true)]
        public int $id,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        public string $name,
    ) {
    }
}
