<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Platform;

use Doctrine\DBAL\Platforms\DateIntervalUnit;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\TransactionIsolationLevel;

trait DecoratorPlatformTrait
{
    public function getBooleanTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getBooleanTypeDeclarationSQL($column);
    }

    public function getIntegerTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getIntegerTypeDeclarationSQL($column);
    }

    public function getBigIntTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getBigIntTypeDeclarationSQL($column);
    }

    public function getSmallIntTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getSmallIntTypeDeclarationSQL($column);
    }

    protected function _getCommonIntegerTypeDeclarationSQL(array $column): string
    {
        return $this->platform->_getCommonIntegerTypeDeclarationSQL($column);
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        $this->platform->initializeDoctrineTypeMappings();
    }

    public function getClobTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getClobTypeDeclarationSQL($column);
    }

    public function getBlobTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getBlobTypeDeclarationSQL($column);
    }

    public function getLocateExpression(string $string, string $substring, ?string $start = null): string
    {
        return $this->platform->getLocateExpression($string, $substring, $start);
    }

    public function getDateDiffExpression(string $date1, string $date2): string
    {
        return $this->platform->getDateDiffExpression($date1, $date2);
    }

    protected function getDateArithmeticIntervalExpression(string $date, string $operator, string $interval, DateIntervalUnit $unit): string
    {
        return $this->platform->getDateArithmeticIntervalExpression($date, $operator, $interval, $unit);
    }

    public function getCurrentDatabaseExpression(): string
    {
        return $this->platform->getCurrentDatabaseExpression();
    }

    public function getListViewsSQL(string $database): string
    {
        return $this->platform->getListViewsSQL($database);
    }

    public function getSetTransactionIsolationSQL(TransactionIsolationLevel $level): string
    {
        return $this->platform->getSetTransactionIsolationSQL($level);
    }

    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getDateTimeTypeDeclarationSQL($column);
    }

    public function getDateTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getDateTypeDeclarationSQL($column);
    }

    public function getTimeTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getTimeTypeDeclarationSQL($column);
    }

    protected function createReservedKeywordsList(): KeywordList
    {
        return $this->platform->createReservedKeywordsList();
    }

    public function getGuidTypeDeclarationSQL(array $column): string
    {
        return $this->platform->getGuidTypeDeclarationSQL($column);
    }

    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->platform, $method)) {
            return $this->platform->{$method}(...$arguments);
        }

        throw new \RuntimeException('Not implemented');
    }
}
