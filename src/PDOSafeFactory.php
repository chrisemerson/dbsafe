<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use PDO;
use PDOException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PDOSafeFactory
{
    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CacheItemPoolInterface $cache = null,
        LoggerInterface $logger = null
    ) {
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getPDO(CredentialsProvider $credentialsProvider, array $options): PDO
    {
        try {
            return new PDO(
                $credentialsProvider->getDSN(),
                $credentialsProvider->getUsername(),
                $credentialsProvider->getPassword(),
                $options
            );
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
