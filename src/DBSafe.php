<?php declare(strict_types=1);

namespace CEmerson\DBSafe;

use CEmerson\DBSafe\Exceptions\IncorrectCredentials;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DBSafe
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

    public function getDB(CredentialsProvider $credentialsProvider, DBFactory $DBFactory, $forceFetch = false)
    {
        $credentialsProvider->setLogger($this->logger);
        $DBFactory->setLogger($this->logger);

        while (true) {
            try {
                $this->logger->debug("Setting up DB Object");

                $db = $DBFactory->getDB(
                    $this->getDSN($credentialsProvider, $forceFetch),
                    $this->getUsername($credentialsProvider, $forceFetch),
                    $this->getPassword($credentialsProvider, $forceFetch),
                );

                $this->logger->debug("DB item created, committing cache to save values");

                $this->cache->commit();

                $this->logger->debug("Cache committed");

                return $db;
            } catch (IncorrectCredentials $e) {
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
            "cemerson.dbsafe." . $credentialsProvider->getDBIdentifier() . ".dsn",
            $force
        );
    }

    private function getUsername(CredentialsProvider $credentialsProvider, $force = false)
    {
        return $this->getCachedItem(
            $credentialsProvider,
            'getUsername',
            "cemerson.dbsafe." . $credentialsProvider->getDBIdentifier() . ".username",
            $force
        );
    }

    private function getPassword(CredentialsProvider $credentialsProvider, $force = false)
    {
        return $this->getCachedItem(
            $credentialsProvider,
            'getPassword',
            "cemerson.dbsafe." . $credentialsProvider->getDBIdentifier() . ".password",
            $force
        );
    }

    private function getCachedItem(
        CredentialsProvider $credentialsProvider,
        string $methodName,
        string $cacheKey,
        bool $forceFetch = false
    ) {
        $this->logger->debug("Looking in cache for item " . $methodName);

        if (!is_null($this->cache)) {
            $cachedItem = $this->cache->getItem($cacheKey);

            if ($cachedItem->isHit() && !$forceFetch) {
                $this->logger->debug("Cache hit - value found");

                $value = $cachedItem->get();
            } else {
                $this->logger->debug("No cached item found - calling " . $methodName . " to fetch value");

                $value = $credentialsProvider->$methodName();

                $cachedItem->set($value);
                $cachedItem->expiresAfter($credentialsProvider->getCacheExpiresAfter());

                $this->cache->saveDeferred($cachedItem);
            }
        } else {
            $value = $credentialsProvider->$methodName();
        }

        return $value;
    }
}
