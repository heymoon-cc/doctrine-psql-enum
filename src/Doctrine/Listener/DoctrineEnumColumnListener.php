<?php
/** @noinspection SqlNoDataSourceInspection */

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Listener;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\Mapping\Column as MappingColumn;
use Doctrine\ORM\Mapping\MappingException;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Exception\UnsupportedPlatformException;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Provider\MetaDataProviderInterface;
use HeyMoon\DoctrinePostgresEnum\Doctrine\Type\EnumType;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ColumnDiff;
use ReflectionException;
use StringBackedEnum;
use UnitEnum;

final class DoctrineEnumColumnListener implements EventSubscriber
{
    private bool $nestedCall = false;
    private array $exist = [];
    private array $comments = [];
    private array $dropComments = [];

    public function __construct(private readonly MetaDataProviderInterface $metaDataProvider) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postConnect,
            Events::onSchemaCreateTable,
            Events::onSchemaAlterTable,
            Events::onSchemaAlterTableAddColumn,
            Events::onSchemaAlterTableChangeColumn,
            Events::onSchemaColumnDefinition
        ];
    }

    /**
     * @throws UnsupportedPlatformException|Exception
     */
    public function postConnect(ConnectionEventArgs $event)
    {
        $this->checkPlatform($event->getConnection()->getDatabasePlatform());
    }

    /**
     * @throws Exception|ReflectionException
     * @noinspection PhpUnused
     */
    public function onSchemaCreateTable(SchemaCreateTableEventArgs $event): void
    {
        if ($this->nestedCall) {
            return;
        }
        $sql = [];
        foreach ($event->getColumns() as $column) {
            $sql = array_merge(
                $sql,
                $this->processColumn($event->getTable()->getName(), $column, $event->getPlatform())
            );
        }
        if ($sql) {
            $event->preventDefault();
            $this->nestedCall = true;
            $sql = array_merge(
                $sql,
                $event->getPlatform()->getCreateTableSQL($event->getTable()),
                $this->dumpComments($event->getPlatform())
            );
            $this->nestedCall = false;
            $event->addSql($sql);
        }
    }

    /**
     * @param SchemaColumnDefinitionEventArgs $event
     * @return void
     * @throws Exception
     * @throws MappingException
     * @throws SchemaException
     * @noinspection PhpUnused
     */
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $event): void
    {
        if (!$event->getTableColumn()) {
            return;
        }
        $type = $event->getTableColumn()['type'];
        $column = $event->getTableColumn()['field'];
        if ($this->metaDataProvider->typeExists($type)) {
            return;
        }
        try {
            $metaData = $this->metaDataProvider->getTable($event->getTable());
        } catch (MappingException) {
            return;
        }
        if (is_null($metaData)) {
            return;
        }
        $field = $metaData->getFieldForColumn($column);
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
            return;
        }
        if (($default = ($arguments['options'] ?? [])['default'] ?? null) instanceof UnitEnum) {
            $arguments['options']['default'] = property_exists($default, 'value') ? $default->value : $default->name;
        }
        $values = $this->metaDataProvider->getRange($type);
        if ($values) {
            $this->exist[$type] = $values;
        }
        $event->setColumn(new Column(
            $column, EnumType::getType($metaData->getTypeOfField($field)), array_merge($arguments['options'] ?? [], [
                'comment' => EnumType::comment($enumType),
                'notnull' => $arguments['isnotnull'] ?? false,
                'platformOptions' => [
                    'enumType' => $enumType
                ]
            ])
        ));
        $event->preventDefault();
    }

    /**
     * @throws ReflectionException|Exception
     * @noinspection PhpUnused
     */
    public function onSchemaAlterTable(SchemaAlterTableEventArgs $event): void
    {
        if ($this->nestedCall) {
            return;
        }
        $sql = [];
        $tableName = $event->getTableDiff()->getName($event->getPlatform())->getName();
        foreach ([$event->getTableDiff()->addedColumns, $event->getTableDiff()->changedColumns] as $columns) {
            foreach ($columns as $column) {
                if ($column instanceof Column) {
                    if (!$column->getType() instanceof EnumType) {
                        continue;
                    }
                    $sql = array_merge($sql, $this->processColumn(
                        $tableName, $column->toArray(), $event->getPlatform(), true)
                    );
                    continue;
                }
                /** @var ColumnDiff $column */
                if (!$column->column->getType() instanceof EnumType) {
                    continue;
                }
                if ($column->fromColumn->getType() instanceof EnumType && !$column->column->getComment()) {
                    $comment = $column->fromColumn->getComment() ?? $this->metaDataProvider->getComment($tableName, $column->fromColumn->getName());
                    $column->column->setComment($comment);
                }
                $sql = array_merge(
                    $sql,
                    $this->processColumn($tableName, $column->column->toArray(), $event->getPlatform(), true)
                );
            }
        }
        $event->preventDefault();
        $this->nestedCall = true;
        $event->addSql(array_merge(
            $sql,
            $event->getPlatform()->getAlterTableSQL($event->getTableDiff()),
            $this->dumpComments($event->getPlatform())
        ), $event->getPlatform());
        $this->nestedCall = false;
    }

    public function onSchemaAlterTableAddColumn(SchemaAlterTableAddColumnEventArgs $event)
    {
        if ($this->nestedCall) {
            return;
        }
        if(!$event->getColumn()->getType() instanceof EnumType) {
            return;
        }
        $event->preventDefault();
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $event)
    {
        if ($this->nestedCall) {
            $event->preventDefault();
        }
    }

    public function toggleNested(): void
    {
        $this->nestedCall = !$this->nestedCall;
    }

    protected function addComment(string $table, string $field, string $comment): self
    {
        if (!array_key_exists($table, $this->comments)) {
            $this->comments[$table] = [];
        }
        $this->comments[$table][$field] = $comment;
        return $this;
    }

    protected function dropComment(string $table, string $field): self
    {
        if (!array_key_exists($table, $this->dropComments)) {
            $this->dropComments[$table] = [];
        }
        $this->dropComments[$table][] = $field;
        return $this;
    }

    protected function dumpComments(AbstractPlatform $platform): array
    {
        $sql = [];
        foreach ($this->comments as $table => $columns) {
            foreach ($columns as $column => $comment) {
                $sql[] = $platform->getCommentOnColumnSQL($table, $column, $comment);
            }
        }
        $this->comments = [];
        foreach ($this->dropComments as $table => $columns) {
            foreach ($columns as $column) {
                $sql[] = $platform->getCommentOnColumnSQL($table, $column, null);
            }
        }
        $this->dropComments = [];
        return $sql;
    }

    /**
     * @throws ReflectionException|Exception
     */
    protected function processColumn(string $tableName, array $column, AbstractPlatform $platform, bool $alter = false): array
    {
        $sql = [];
        $columnName = $column['name'];
        $type = $column['type'];
        if (!$type instanceof EnumType) {
            return $sql;
        }
        $targetClass = $this->metaDataProvider->getEnumClass($tableName, $columnName);
        if (!$targetClass) {
            return $sql;
        }
        $currentType = $this->metaDataProvider->getRawType($tableName, $columnName);
        $class = $column['enumType'] ?? $targetClass;
        $targetType = EnumType::nameFromClass($class);
        if (!$currentType) {
            $currentType = $targetType;
        }
        /** @var StringBackedEnum $class */
        $cases = array_map(fn(UnitEnum $enum) => $enum->value, $class::cases());
        if (array_key_exists($targetType, $this->exist)) {
            foreach (array_diff($cases, $this->exist[$targetType]) as $add) {
                $sql[] = "ALTER TYPE $targetType ADD VALUE '$add'";
            }
            foreach (array_diff($this->exist[$targetType], $cases) as $drop) {
                $sql[] = "ALTER TYPE $targetType DROP VALUE '$drop'";
            }
        } else {
            $enumSql = implode(',', array_map(fn(string $case) => "'$case'", $cases));
            $sql[] = "DROP TYPE IF EXISTS $targetType";
            $sql[] = "CREATE TYPE $targetType AS ENUM ($enumSql)";
        }
        if ($targetType !== $currentType) {
            $comment = array_key_exists('enumType', $column) ? EnumType::comment($class) :
                ($column['comment'] ?? null);
            if (!$comment) {
                $this->dropComment($tableName, $columnName);
            } elseif ($this->metaDataProvider->getComment($tableName, $columnName) !== $comment) {
                $this->addComment($tableName, $columnName, $comment);
            }
            if ($alter) {
                $sql = array_merge($sql, [
                    array_key_exists('enumType', $column) ?
                        "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE {$type->getSQLDeclaration($column, $platform)} USING $columnName::text::$targetType" :
                        "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE {$type->getSQLDeclaration(
                        array_merge($column, ['rawType' => $currentType]), $platform)} USING $columnName::text::$currentType"
                ], $this->getNullableAlterSQL($tableName, $column));
            }
        }
        $this->exist[$targetType] = $cases;
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

    /**
     * @throws UnsupportedPlatformException
     */
    protected function checkPlatform(AbstractPlatform $platform): void
    {
        if (!$platform instanceof PostgreSQLPlatform) {
            throw UnsupportedPlatformException::create($platform);
        }
    }
}
