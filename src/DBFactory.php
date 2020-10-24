<?php declare(strict_types=1);

namespace CEmerson\DBSafe;

use Psr\Log\LoggerAwareInterface;

interface DBFactory extends LoggerAwareInterface
{
    /** Returns the database connection, in whatever form that may be for this factory - resource, Object etc
     *  This method MUST throw an IncorrectCredentials exception if the credentials are incorrect,
     *  to allow DBSafe to re-fetch and try again */
    public function getDB($dsn, $username = null, $password = null);
}
