<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use DateInterval;

class PlainTextCredentialsProvider extends AbstractCredentialsProvider
{
    /** @var string */
    private $DSN;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(string $DSN, string $username, string $password)
    {
        $this->DSN = $DSN;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDSN(): string
    {
        return $this->DSN;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCacheExpiresAfter(): ?DateInterval
    {
        //Do not cache
        return null;
    }
}
