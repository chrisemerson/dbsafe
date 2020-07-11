<?php declare(strict_types=1);

namespace CEmerson\PDOSafe;

use DateInterval;

interface CredentialsProvider
{
    public function getDSN(): string;

    public function getUsername(): string;

    public function getPassword(): string;

    public function getCacheExpiresAfter(): ?DateInterval;
}
