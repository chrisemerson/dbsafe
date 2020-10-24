<?php declare(strict_types=1);

namespace CEmerson\DBSafe\CredentialsProviders;

use CEmerson\DBSafe\CredentialsProvider;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class AbstractCredentialsProvider implements CredentialsProvider
{
    use LoggerAwareTrait;

    private const MYSQL_DEFAULT_PORT = 3306;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

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
