<?php declare(strict_types=1);

namespace CEmerson\DBSafe\Exceptions;

use Throwable;

class CredentialsNotFound extends ErrorFetchingCredentials
{
    /** @var array */
    private $parametersNotFound;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $parametersNotFound = [])
    {
        $this->parametersNotFound = $parametersNotFound;

        parent::__construct($message, $code, $previous);
    }

    public function getParametersNotFound(): array
    {
        return $this->parametersNotFound;
    }
}
