<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\View;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use Doctrine\ORM\Mapping\Column as MappingColumn;

/**
 * @extends AbstractSchemaManager<AbstractPlatform>
 */
final class DoctrineEnumColumnSchemaManager extends PostgreSQLSchemaManager
{
    public function __construct(protected Connection $connection, protected AbstractPlatform $platform, private readonly AbstractSchemaManager $schemaManager, private readonly MetaDataProviderInterface $metaDataProvider)
    {
        parent::__construct($connection, $platform);
    }

    protected function selectTableNames(string $databaseName): Result
    {
        return $this->schemaManager->selectTableNames($databaseName);
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        return $this->schemaManager->selectTableColumns($databaseName, $tableName);
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        return $this->schemaManager->selectIndexColumns($databaseName, $tableName);
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        return $this->schemaManager->selectForeignKeyColumns($databaseName, $tableName);
    }

    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        return $this->schemaManager->fetchTableOptionsByTable($databaseName, $tableName);
    }

    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        $column = $tableColumn['field'];

        if (!isset($tableColumn['table_name'])) {
            return $this->schemaManager->_getPortableTableColumnDefinition($tableColumn);
        }

        // This can be optimized
        $metaData = $this->metaDataProvider->getTable($tableColumn['table_name']);
        if (!$metaData) {
            return $this->schemaManager->_getPortableTableColumnDefinition($tableColumn);
        }

        $field = $metaData->getFieldForColumn($tableColumn['field']);

        $property = $metaData->getReflectionProperty($field);
        $arguments = [];
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() !== MappingColumn::class) {
                continue;
            }
            $arguments = array_merge($arguments, $attribute->getArguments() ?? []);
        }
        $enumType = $arguments['enumType'] ?? null;
        if (!$enumType) {
            return $this->schemaManager->_getPortableTableColumnDefinition($tableColumn);
        }

        if (($default = ($arguments['options'] ?? [])['default'] ?? null) instanceof UnitEnum) {
            $arguments['options']['default'] = property_exists($default, 'value') ? $default->value : $default->name;
        }
        // $values = $this->metaDataProvider->getRange($type);
        //
        // if ($values) {
        //     $this->exist[$type] = $values;
        // }

        return new Column(
            $column,
            EnumType::getType($metaData->getTypeOfField($field)),
            array_merge($arguments['options'] ?? [], [
                // 'comment' => EnumType::comment($enumType),
                'notnull' => $arguments['isnotnull'] ?? false,
                'platformOptions' => [
                    'enumType' => $enumType
                ]
            ])
        );
    }

    protected function _getPortableTableDefinition(array $table): string
    {
        return $this->schemaManager->_getPortableTableDefinition($table);
    }

    protected function _getPortableViewDefinition(array $view): View
    {
        return $this->schemaManager->_getPortableViewDefinition($view);
    }

    protected function _getPortableTableForeignKeyDefinition(array $tableForeignKey): ForeignKeyConstraint
    {
        return $this->schemaManager->_getPortableTableForeignKeyDefinition($tableForeignKey);
    }

    public function createComparator(): Comparator
    {
        return new DoctrineEnumColumnComparator($this->platform, $this->metaDataProvider);
    }
}
