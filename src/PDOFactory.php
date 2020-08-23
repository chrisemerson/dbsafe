<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use PDO;

class PDOFactory
{
    public function getPDO($dsn, $username = null, $passwd = null, $options = null): PDO
    {
        return new PDO($dsn, $username, $passwd, $options);
    }
}
