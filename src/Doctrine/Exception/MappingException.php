<?php

namespace HeyMoon\DoctrinePostgresEnum\Doctrine\Exception;

use Doctrine\DBAL\Exception as DBALException;
use RuntimeException;

class MappingException extends RuntimeException implements DBALException {}
