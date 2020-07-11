<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use CEmerson\PDOSafe\CredentialsProvider;
use DateInterval;

final class PlainTextCredentialsProvider implements CredentialsProvider
{
    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
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
