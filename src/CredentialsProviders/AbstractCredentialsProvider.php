<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use CEmerson\PDOSafe\CredentialsProvider;

abstract class AbstractCredentialsProvider implements CredentialsProvider
{
    private const MYSQL_DEFAULT_PORT = 3306;

    protected function getDSNString(
        string $engine,
        string $host,
        string $dbName,
        string $charset = 'utf8',
        int $port = self::MYSQL_DEFAULT_PORT
    ): string {
        return $engine . ':host=' . $host . ';port=' . $port . ';dbname=' . $dbName . ';charset=' . $charset;
    }
}
