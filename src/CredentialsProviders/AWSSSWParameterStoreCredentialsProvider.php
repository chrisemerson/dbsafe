<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use CEmerson\PDOSafe\CredentialsProvider;
use DateInterval;

final class AWSSSWParameterStoreCredentialsProvider implements CredentialsProvider
{
    public function getUsername(): string
    {
        // TODO: Implement getUsername() method.
    }

    public function getPassword(): string
    {
        // TODO: Implement getPassword() method.
    }

    public function getCacheExpiresAfter(): ?DateInterval
    {
        // TODO: Implement getCacheExpiresAfter() method.
    }
}
