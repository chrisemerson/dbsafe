# DBSafe

This library started out as PDO Safe, a library for using AWS Parameter Store or AWS Secrets Manager with PDO. Over time
though it became clear it could serve as a more generic database factory, allowing credentials from any location to be
used for creating databases of any kind. It was therefore renamed to DBSafe to reflect this, although there is only a
factory for PDO currently included, and only currently support for fetching credentials from AWS Secrets Manager and AWS
SSM Parameter Store, as well as a simple hard coded credentials provider for testing or ease of migration.

DBSafe will cache your credentials using whichever cache you plug in to it, in order to avoid repeated calls to the
credentials store. You can specify how long the credentials are cached for. In addition, if a connection to the database
fails, DBSafe will first try re-fetching the credentials fresh from the credentials store and try the connection again
before failing. This allows credentials to be rolled over externally and have your application still work without
interruption.

## Installation

Use composer to install into your project:

    composer require cemerson/dbsafe

## Quick Start

The DBSafe class is the main point of interaction. In order to create it, you need to pass it a PSR-6 compatible
Cache Pool, and a PSR-3 compatible logger.

    $dbSafe = new DBSafe($cache, $logger);
    
Once you have this object, you need to set up a Credentials Provider and a DB Factory for each database connection you
want DBSafe to manage in your application - though each of these could be shared between connections if appropriate.

    $credentialsProvider = new PlainTextCredentialsProvider(
        'mydb',
        'mysql:host=localhost;port=3306;dbname=mydb;charset=utf8',
        'username',
        'password'
    );
    
    $dbFactory = new PDODBFactory([
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
    ]);

Once you have the Credentials Provider and DB Factory objects, you can just call getDB on the DBSafe object to get your
database connection. DBSafe will fetch the credentials, cache them with your cache provider and return the DB connection
as returned by the factory.

    $dbConnection = $dbSafe->getDB($credentialsProvider, $dbFactory);

## Concepts

The library is made up of Credentials Providers & Factories and essentially glues these together along with cache and
logging. You can use any user-provided credentials provider, any user-provided factory, any user-provided PSR-6
compatible cache and any PSR-3 compatible logger. Some credentials providers are provided with the library for use with
AWS services and a PDO factory also, since I think this will be the most common use case.

### Credentials Provider

The credentials provider is responsible for fetching the database credentials. This could be from an AWS service like
Secrets Manager, SSM Parameter Store, just passed as plain text straight into the credentials provider or any other
source. Each credentials provider is expected to provide a unique identifier for the database, a DSN string, a username
and a password. There is an Abstract class with a method to help construct DSN strings in a standard format.

All the Credentials Providers take a unique identifier as their first parameter. This must be a unique value within your
application, and is used for creating a unique caching key for the particular credentials.

The use of each of the built-in providers is described here.

#### Plain Text Provider
    CEmerson\DBSafe\CredentialsProviders\PlainTextCredentialsProvider

This one is very simple - you just pass the DSN, username and password into the provider, and it uses these to create
the database connection. This is ideal if you want to integrate DBSafe into an existing project and still have the
credentials come from a local source while you test things out, or for local development. Caching is not used for this
provider.

#### AWS Secrets Manager Provider
    CEmerson\DBSafe\CredentialsProviders\AWSSecretsManagerCredentialsProvider

If your credentials are in AWS Secrets Manager, you can use this provider in order to fetch them. It takes the unique
identifier, then 4 other parameters - the AWS SecretsManagerClient from the AWS PHP SDK, the name of the secret that
stores the credentials, a character set to use for the connection and a DateInterval representing the amount of time the
credentials should be cached.

    $credentialsProvider = new AWSSecretsManagerCredentialsProvider(
        $awsSecretsManagerClient,
        'my-db-credentials',
        'utf8',
        new DateInterval('P1D')
    );

The creation of the AWS client is left up to the user, so that the appropriate version and region can be used. This
provider would fetch credentials from AWS Secrets Manager with the name 'my-db-credentials', use the utf-8 character set
as part of the DSN, and cache the credentials for 1 day.

#### AWS SSM Parameter Store Provider
    CEmerson\DBSafe\CredentialsProviders\AWSSSMParameterStoreCredentialsProvider

The AWS Parameter Store doesn't store details of a whole connection at once like Secrets Manager does, but the DSN,
username and password need to be stored as separate parameters. This credentials provider takes the unique identifier,
the AWS SSM client, then the parameter names for the DSN, Username and Password, and finally the cache time.

    $credentialsProvider = new AWSSSMParameterStoreCredentialsProvider(
        $awsSSMClient,
        'my-other-db-credentials',
        'my-database-dsn',
        'my-database-username',
        'my-database-password',
        new DateInterval('PT1H')    
    );

#### Custom Providers

You can create a custom provider in order to fetch credentials from elsewhere simply by creating a class that implements
the CredentialsProvider interface. If fetching the credentials involves a call somewhere or some other task that should
only be done if the credentials aren't cached, be sure to only fetch credentials when asked for them, not in the
constructor of your class.

This interface also extends the LoggerAwareInterface. You can simply use the LoggerAwareTrait from the psr/log package
and it will cover all the requirements here. You can then log messages to the logger using (eg)
`$this->logger->info('message')`.

### DB Factories

The DB Factory is responsible for creating the connection using the credentials fetched from cache or from the
Credentials Provider. It is very simple, with only a single method - `getDB`.

There is currently only 1 included factory, the `PDODBFactory`.

#### PDODBFactory
    CEmerson\DBSafe\DBFactories\PDODBFactory

This factory should be used when you want PDO connection objects to be returned for your application's use. The
constructor allows options to be passed in which will be passed to the PDO object constructor:

    $dbFactory = new PDODBFactory([
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
    ]);

#### Custom DB Factories

You can create a custom DB Factory by creating a class that implements the DBFactory interface. It only has a single
method that needs implementing - one that returns the DB connection from the given DSN, Username and Password. This
method MUST throw an IncorrectCredentials exception if the credentials passed are incorrect, so that DBSafe's retry
feature can work.

This interface also extends the LoggerAwareInterface, which can be met by simply using the LoggerAwareTrait from
psr/log.
