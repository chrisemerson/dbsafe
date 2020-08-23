<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use PDO;
use PDOException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PDOSafe
{
    /** @var PDOFactory */
    private $PDOFactory;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PDOFactory $PDOFactory,
        CacheItemPoolInterface $cache = null,
        LoggerInterface $logger = null
    ) {
        $this->PDOFactory = $PDOFactory;
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getPDO(CredentialsProvider $credentialsProvider, array $options, $forceFetch = false): PDO
    {
        $credentialsProvider->setLogger($this->logger);

        while (true) {
            try {
                $this->logger->debug("Setting up PDO Object");

                $pdo = $this->PDOFactory->getPDO(
                    $this->getDSN($credentialsProvider, $forceFetch),
                    $this->getUsername($credentialsProvider, $forceFetch),
                    $this->getPassword($credentialsProvider, $forceFetch),
                    $options
                );

                $this->logger->debug("PDO item created, committing cache to save values");

                $this->cache->commit();

                $this->logger->debug("Cache committed");

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

    private function getCachedItem(
        CredentialsProvider $credentialsProvider,
        string $methodName,
        string $cacheKey,
        bool $forceFetch = false
    ) {
        $this->logger->debug("Looking in cache for item " . $methodName);

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

        return $value;
    }
}
