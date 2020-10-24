<?php declare(strict_types=1);

namespace CEmerson\DBSafe\DBFactories;

use CEmerson\DBSafe\DBFactory;
use CEmerson\DBSafe\Exceptions\IncorrectCredentials;
use PDO;
use PDOException;
use Psr\Log\LoggerAwareTrait;

final class PDOFactory implements DBFactory
{
    use LoggerAwareTrait;

    /** @var array */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getDB($dsn, $username = null, $passwd = null)
    {
        try {
            return new PDO($dsn, $username, $passwd, $this->options);
        } catch (PDOException $e) {
            $this->logger->info("PDO Exception thrown: " . get_class($e) . " " . $e->getMessage());

            throw new IncorrectCredentials();
        }
    }
}
