<?php declare(strict_types=1);

namespace CEmerson\DBSafe;

use Psr\Log\LoggerAwareInterface;

interface DBFactory extends LoggerAwareInterface
{
    public function getDB($dsn, $username = null, $passwd = null);
}
