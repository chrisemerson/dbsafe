<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use Aws\Exception\AwsException;
use Aws\Ssm\SsmClient;
use CEmerson\PDOSafe\Exceptions\CredentialsNotFound;
use CEmerson\PDOSafe\Exceptions\ErrorFetchingCredentials;
use CEmerson\PDOSafe\Exceptions\MissingCredential;
use DateInterval;

class AWSSSMParameterStoreCredentialsProvider extends AbstractCredentialsProvider
{
    /** @var string */
    private $DBIdentifier;

    /** @var SsmClient */
    private $ssmClient;

    /** @var ?string */
    private $DSNParameterName;

    /** @var ?string */
    private $usernameParameterName;

    /** @var ?string */
    private $passwordParameterName;

    /** @var DateInterval */
    private $expiresAfter;

    /** @var ?string */
    private $DSN = null;

    /** @var ?string */
    private $username = null;

    /** @var ?string */
    private $password = null;

    public function __construct(
        string $DBIdentifier,
        SsmClient $ssmClient,
        ?string $DSNParameterName = null,
        ?string $usernameParameterName = null,
        ?string $passwordParameterName = null,
        ?DateInterval $expiresAfter = null
    ) {
        $this->DBIdentifier = $DBIdentifier;
        $this->ssmClient = $ssmClient;
        $this->DSNParameterName = $DSNParameterName;
        $this->usernameParameterName = $usernameParameterName;
        $this->passwordParameterName = $passwordParameterName;
        $this->expiresAfter = $expiresAfter;
    }

    public function setPlainTextDSN(string $DSN): void
    {
        $this->DSN = $DSN;
    }

    public function setPlainTextUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPlainTextPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getDBIdentifier(): string
    {
        return $this->DBIdentifier;
    }

    public function getDSN(): string
    {
        $this->fetchCredentials();

        return $this->DSN;
    }

    public function getUsername(): string
    {
        $this->fetchCredentials();

        return $this->username;
    }

    public function getPassword(): string
    {
        $this->fetchCredentials();

        return $this->password;
    }

    public function getCacheExpiresAfter(): ?DateInterval
    {
        return $this->expiresAfter;
    }

    private function fetchCredentials(): void
    {
        $this->guardAgainstMissingCredentials();

        $credentialsToFetch = $this->credentialsToFetch();

        if (count($credentialsToFetch) > 0) {
            try {
                $result = $this->ssmClient->getParameters([
                    'Names' => array_values($credentialsToFetch),
                    'WithDecryption' => true
                ]);
            } catch (AwsException $awsException) {
                throw new ErrorFetchingCredentials($awsException->getAwsErrorCode(),0, $awsException);
            }

            if (count($result['InvalidParameters']) > 0) {
                throw new CredentialsNotFound(
                    "Credentials not found in AWS Parameter store: " . implode(', ', $result['InvalidParameters']),
                    0,
                    null,
                    $result['InvalidParameters']
                );
            }

            $paramNames = array_flip($credentialsToFetch);

            foreach ($result['Parameters'] as $parameterResult) {
                $credentialName = $paramNames[$parameterResult['Name']];
                $this->$credentialName = $parameterResult['Value'];
            }
        }
    }

    private function guardAgainstMissingCredentials()
    {
        if (is_null($this->DSN) && is_null($this->DSNParameterName)) {
            throw new MissingCredential(
                "You must specify the parameter name for the DSN or specify a plain text DSN with setPlainTextDSN()"
            );
        }

        if (is_null($this->username) && is_null($this->usernameParameterName)) {
            throw new MissingCredential(
                "You must specify the parameter name for the username or specify a plain text username with setPlainTextUsername()"
            );
        }

        if (is_null($this->password) && is_null($this->passwordParameterName)) {
            throw new MissingCredential(
                "You must specify the parameter name for the password or specify a plain text password with setPlainTextPassword()"
            );
        }
    }

    private function credentialsToFetch(): array
    {
        $credentialsToFetch = [];

        if (is_null($this->DSN) && !is_null($this->DSNParameterName)) {
            $credentialsToFetch['DSN'] = $this->DSNParameterName;
        }

        if (is_null($this->username) && !is_null($this->usernameParameterName)) {
            $credentialsToFetch['username'] = $this->usernameParameterName;
        }

        if (is_null($this->password) && !is_null($this->passwordParameterName)) {
            $credentialsToFetch['password'] = $this->passwordParameterName;
        }

        return $credentialsToFetch;
    }
}
