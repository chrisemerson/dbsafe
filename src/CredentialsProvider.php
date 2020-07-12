<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use DateInterval;

interface CredentialsProvider
{
    /** A unique identifier for the database that this CredentialsProvider represents, for use with caching */
    public function getDBIdentifier(): string;

    /** Optionally looks up, and returns the DSN for this database connection */
    public function getDSN(): string;

    /** Optionally looks up, and returns the username for this database connection */
    public function getUsername(): string;

    /** Optionally looks up, and returns the password for this database connection */
    public function getPassword(): string;

    /** How long to cache the current credentials for */
    public function getCacheExpiresAfter(): ?DateInterval;
}
