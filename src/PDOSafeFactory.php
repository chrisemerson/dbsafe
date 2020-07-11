<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PDOSafeFactory
{
    /** @var CredentialsProvider */
    private $credentialsProvider;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;


    public function __construct(
        CredentialsProvider $credentialsProvider,
        CacheItemPoolInterface $cache = null,
        LoggerInterface $logger = null
    ) {
        $this->credentialsProvider = $credentialsProvider;
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getPDO($dsn, $options): PDO
    {
        return new PDO(
            $dsn,
            $this->credentialsProvider->getUsername(),
            $this->credentialsProvider->getPassword(),
            $options
        );
    }
}
