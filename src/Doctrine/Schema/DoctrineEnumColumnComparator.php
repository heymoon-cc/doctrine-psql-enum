<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;

class DoctrineEnumColumnComparator extends Comparator
{
    public function __construct(AbstractPlatform $platform, private readonly MetaDataProviderInterface $metaDataProvider)
    {
        parent::__construct($platform);
    }

    protected function columnsEqual(Column $column1, Column $column2): bool
    {
        if (!$column1->getType() instanceof EnumType) {
            return parent::columnsEqual($column1, $column2);
        }

        $column1->setNotnull(true);
        return parent::columnsEqual($column1, $column2);
    }
}
