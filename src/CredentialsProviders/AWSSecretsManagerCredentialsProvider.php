<?php declare(strict_types=1);

namespace CEmerson\PDOSafe\CredentialsProviders;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use CEmerson\PDOSafe\Exceptions\ErrorFetchingCredentials;
use DateInterval;

class AWSSecretsManagerCredentialsProvider extends AbstractCredentialsProvider
{
    /** @var string */
    private $DBIdentifier;

    /** @var SecretsManagerClient */
    private $secretsManagerClient;

    /** @var string */
    private $credentialsName;

    /** @var string */
    private $charset;

    /** @var DateInterval */
    private $expiresAfter;

    private $credentials = null;

    public function __construct(
        string $DBIdentifier,
        SecretsManagerClient $secretsManagerClient,
        string $credentialsName,
        string $charset = 'utf8',
        ?DateInterval $expiresAfter = null
    ) {
        $this->DBIdentifier = $DBIdentifier;
        $this->secretsManagerClient = $secretsManagerClient;
        $this->credentialsName = $credentialsName;
        $this->charset = $charset;
        $this->expiresAfter = $expiresAfter;

        parent::__construct();
    }

    public function getDBIdentifier(): string
    {
        return $this->DBIdentifier;
    }

    public function getDSN(): string
    {
        $this->fetchCredentials();

        return $this->getDSNString(
            $this->credentials->engine,
            $this->credentials->host,
            $this->credentials->dbname,
            $this->charset,
            (int) $this->credentials->port
        );
    }

    public function getUsername(): string
    {
        $this->fetchCredentials();

        return $this->credentials->username;
    }

    public function getPassword(): string
    {
        $this->fetchCredentials();

        return $this->credentials->password;
    }

    public function getCacheExpiresAfter(): ?DateInterval
    {
        return $this->expiresAfter;
    }

    private function fetchCredentials(): void
    {
        if (is_null($this->credentials)) {
            try {
                $this->logger->debug("Attempting to call Secrets Manager for credentials");

                $result = $this->secretsManagerClient->getSecretValue([
                    'SecretId' => $this->credentialsName
                ]);
            } catch (AwsException $awsException) {
                $error = $awsException->getAwsErrorCode();

                $errorMessage = '';

                switch ($error) {
                    case 'DecryptionFailureException':
                        $errorMessage =
                            ": Secrets Manager can't decrypt the protected secret text using the provided AWS KMS key.";
                        break;

                    case 'InternalServiceErrorException':
                        $errorMessage = ": An error occurred on the server side.";
                        break;

                    case 'InvalidParameterException':
                        $errorMessage = ": You provided an invalid value for a parameter.";
                        break;

                    case 'InvalidRequestException':
                        $errorMessage =
                            ": You provided a parameter value that is not valid for the current state of the resource.";
                        break;

                    case 'ResourceNotFoundException':
                        $errorMessage = ": We can't find the resource that you asked for.";
                        break;
                }

                $this->logger->error($error . $errorMessage);

                throw new ErrorFetchingCredentials($error . $errorMessage,0, $awsException);
            }

            $this->logger->debug("Success fetching credentials - extracting from results object");

            if (isset($result['SecretString'])) {
                $secret = $result['SecretString'];
            } else {
                $secret = base64_decode($result['SecretBinary']);
            }

            $this->credentials = json_decode($secret);
        } else {
            $this->logger->debug("Credentials already fetched - skipping fetch instruction");
        }
    }
}
