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

    public function getPDO(CredentialsProvider $credentialsProvider, array $options, $forceFetch = false): PDO
    {
        while (true) {
            try {
                $pdo = new PDO(
                    $this->getDSN($credentialsProvider, $forceFetch),
                    $this->getUsername($credentialsProvider, $forceFetch),
                    $this->getPassword($credentialsProvider, $forceFetch),
                    $options
                );

                $this->cache->commit();

                return $pdo;
            } catch (PDOException $e) {
                if ($forceFetch) {
                    throw $e;
                }

                $forceFetch = true;
            }
        }
    }

    private function getDSN(CredentialsProvider $credentialsProvider, $force = false)
    {
        return $this->getCachedItem(
            $credentialsProvider,
            'getDSN',
            "cemerson.pdosafe." . $credentialsProvider->getDBIdentifier() . ".dsn",
            $force
        );
    }

    private function getUsername(CredentialsProvider $credentialsProvider, $force = false)
    {
        return $this->getCachedItem(
            $credentialsProvider,
            'getUsername',
            "cemerson.pdosafe." . $credentialsProvider->getDBIdentifier() . ".username",
            $force
        );
    }

    private function getPassword(CredentialsProvider $credentialsProvider, $force = false)
    {
        return $this->getCachedItem(
            $credentialsProvider,
            'getPassword',
            "cemerson.pdosafe." . $credentialsProvider->getDBIdentifier() . ".password",
            $force
        );
    }

    private function getCachedItem(CredentialsProvider $credentialsProvider, string $methodName, string $cacheKey, bool $forceFetch = false)
    {
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit() && !$forceFetch) {
            $value = $cachedItem->get();
        } else {
            $value = $credentialsProvider->$methodName();

            $cachedItem->set($value);
            $cachedItem->expiresAfter($credentialsProvider->getCacheExpiresAfter());

            $this->cache->saveDeferred($cachedItem);
        }

        return $value;
    }
}
