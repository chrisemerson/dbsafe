<?php declare(strict_types=1);

namespace CEmerson\DBSafe\DBFactories;

use CEmerson\DBSafe\DBFactory;
use PDO;

final class PDOFactory implements DBFactory
{
    public function getDB($dsn, $username = null, $passwd = null, $options = null)
    {
        return new PDO($dsn, $username, $passwd, $options);
    }
}
