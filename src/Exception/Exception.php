<?php

namespace HeyMoon\DoctrinePostgresEnum\Exception;

use Doctrine\DBAL\Exception as DBALException;
use LogicException;

class Exception extends LogicException implements DBALException {}
