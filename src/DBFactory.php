<?php declare(strict_types=1);

namespace CEmerson\DBSafe;

interface DBFactory
{
    public function getDB($dsn, $username = null, $passwd = null, $options = null);
}
