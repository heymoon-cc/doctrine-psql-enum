<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Platform;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\TableDiff;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Schema\DoctrineEnumColumnSchemaManager;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use StringBackedEnum;
use UnitEnum;

final class DoctrineEnumColumnPlatform extends PostgreSQLPlatform
{
    private array $exists = [];

    public function __construct(private readonly AbstractPlatform $platform, private readonly MetaDataProviderInterface $metaDataProvider)
    {
    }

    public function registerDoctrineTypeMapping(string $dbType, string $doctrineType): void
    {
        parent::registerDoctrineTypeMapping($dbType, $doctrineType);
        $this->platform->registerDoctrineTypeMapping($dbType, $doctrineType);
    }

    protected function _getCreateTableSQL(string $name, array $columns, array $options = []): array
    {
        $createTableSQL = $this->platform->_getCreateTableSQL($name, $columns, $options);
        $sql = [];

        foreach ($columns as $column) {
            if (!$column['type'] instanceof EnumType) {
                continue;
            }

            foreach (
                $this->processColumn(
                    $name,
                    $column,
                    false
                ) as $s
            ) {
                $sql[] = $s;
            }
        }

        return [...$sql, ...$createTableSQL];
    }

    public function getAlterTableSQL(TableDiff $diff): array
    {
        $sql = [];
        $tableName = $diff->getOldTable()->getName();
        foreach ($diff->getAddedColumns() as $column) {
            if (!$column->getType() instanceof EnumType) {
                continue;
            }

            foreach (
                $this->processColumn(
                    $tableName,
                    $column->toArray(),
                    true
                ) as $s
            ) {
                $sql[] = $s;
            }
        }

        foreach ($diff->getChangedColumns() as $column) {
            $newColumn = $column->getNewColumn();
            if (!$newColumn->getType() instanceof EnumType) {
                continue;
            }

            foreach (
                $this->processColumn(
                    $tableName,
                    $newColumn->toArray(),
                    true
                ) as $s
            ) {
                $sql[] = $s;
            }
        }

        return array_merge($sql, $this->platform->getAlterTableSQL($diff));
    }

    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        return new DoctrineEnumColumnSchemaManager($connection, $this, $this->platform->createSchemaManager($connection), $this->metaDataProvider);
    }

    /**
     * @param string $tableName
     * @param array $column
     * @param bool $alter
     * @return array
     * @throws Exception
     */
    protected function processColumn(string $tableName, array $column, bool $alter = false): array
    {
        $columnName = $column['name'];
        $targetClass = $this->metaDataProvider->getEnumClass($tableName, $columnName);
        if (!$targetClass) {
            return [];
        }

        $currentType = $this->metaDataProvider->getRawType($tableName, $columnName);
        $class = $column['enumType'] ?? $targetClass;
        $targetType = EnumType::nameFromClass($class);

        if (!$currentType) {
            $currentType = $targetType;
        }
        /** @var StringBackedEnum $class */
        $cases = array_map(fn (UnitEnum $enum) => $enum->value, $class::cases());

        if (isset($this->exists[$targetType])) {
            return [];
        }

        $sql = [];
        if ($values = $this->metaDataProvider->getRange($currentType)) {
            foreach (array_diff($cases, $values) as $add) {
                $sql[] = "ALTER TYPE $targetType ADD VALUE '$add'";
            }
            foreach (array_diff($values, $cases) as $drop) {
                $sql[] = "ALTER TYPE $targetType DROP VALUE '$drop'";
            }
        } else {
            $enumSql = implode(',', array_map(fn (string $case) => "'$case'", $cases));
            // $sql[] = "DROP TYPE IF EXISTS $targetType";
            $sql[] = "CREATE TYPE $targetType AS ENUM ($enumSql)";
        }

        $type = $column['type'];
        if ($targetType !== $currentType) {
            if ($alter) {
                $sql = array_merge($sql, [
                    array_key_exists('enumType', $column) ?
                        "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE {$type->getSQLDeclaration($column, $this)} USING $columnName::text::$targetType" :
                        "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE {$type->getSQLDeclaration(
                            array_merge($column, ['rawType' => $currentType]),
                            $this
                        )} USING $columnName::text::$currentType"
                ], $this->getNullableAlterSQL($tableName, $column));
            }
        }

        // store existing type to avoid multiple "CREATE TYPE" sql clauses
        $this->exists[$targetType] = true;

        return $sql;
    }

    protected function getNullableAlterSQL(string $table, array $column): array
    {
        $sql = [];
        $default = ($column['default'] ?? null) ?
            ($column['default'] instanceof UnitEnum ?
                (property_exists($column['default'], 'value') ?
                    $column['default']->value : $column['default']->name) : ((string)$column['default'] ?: null)) :
            null;
        if ($default) {
            $sql[] = "ALTER TABLE $table ALTER COLUMN {$column['name']} SET DEFAULT '$default'";
        } else {
            $sql[] = "ALTER TABLE $table ALTER COLUMN {$column['name']} DROP DEFAULT";
        }
        if ($column['notnull'] ?? false) {
            $sql[] = "ALTER TABLE $table ALTER COLUMN {$column['name']} SET NOT NULL";
        } else {
            $sql[] = "ALTER TABLE $table ALTER COLUMN {$column['name']} DROP NOT NULL";
        }
        return $sql;
    }
}
