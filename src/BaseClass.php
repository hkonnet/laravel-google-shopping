<?php


namespace Hkonnet\LaravelGoogleShopping;


use DomainException;
use Google_Client;
use Google_Service_ShoppingContent;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

class BaseClass
{
    private $nonce = 0; // used by newOperationId()

    public $config = [];
    public $merchantId;
    public $mcaStatus;
    public $service;
    public $requestService;
    protected $mode;
    protected $appName;
    protected $configDir;
    protected $serviceFilename;

    function __construct()
    {
        $this->configDir = config('google_shopping.config_dir');
        $this->mode = config('google_shopping.mode');
        $this->appName = config('google_shopping.app_name');
        $this->serviceFilename = config('google_shopping.file_name.service_account_filename');


        $client = new Google_Client();
        $client->setApplicationName($this->appName);
        $client->setScopes(Google_Service_ShoppingContent::CONTENT);


        $this->authenticate($client);
        $this->prepareServices($client);
        $this->getMerchantBasicData();
    }

    protected function authenticate(Google_Client $client) {

        try {
            // Try loading the credentials.
            $credentials = \Google\Auth\ApplicationDefaultCredentials::getCredentials(
                Google_Service_ShoppingContent::CONTENT);

            // If we got here, the credentials are there, so tell the client.
            $client->useApplicationDefaultCredentials();
        } catch (DomainException $exception) {
            // Safe to ignore this error, since we'll fall back on other creds unless
            // we are not using a configuration directory.
            if (!$this->configDir) {
                throw new InvalidArgumentException(
                    'Must use Google Application Default Credentials if running '
                    . 'without a configuration directory');
            }
            $this->authenticateFromConfig($client);
        }
    }

    protected function authenticateFromConfig(Google_Client $client) {
        $accountFile = join(DIRECTORY_SEPARATOR,
            [$this->configDir, $this->serviceFilename]);
        if (file_exists($accountFile)) {
//            print 'Loading service account credentials from ' . $accountFile . ".\n";
            $client->setAuthConfig($accountFile);
            $client->setScopes(Google_Service_ShoppingContent::CONTENT);
            return;
        }
        $oauthFile = join(DIRECTORY_SEPARATOR,
            [$this->configDir, self::OAUTH_CLIENT_FILE_NAME]);
        if (file_exists($oauthFile)) {
            print 'Loading OAuth2 credentials from ' . $oauthFile . ".\n";
            $client->setAuthConfig($oauthFile);
            $tokenFile = join(DIRECTORY_SEPARATOR,
                [$this->configDir, self::OAUTH_TOKEN_FILE_NAME]);
            $token = null;
            if (file_exists($tokenFile)) {
                printf("Loading stored token from '%s'.\n", $tokenFile);
                $token = json_decode(file_get_contents($tokenFile), true);
            }
            if (is_null($token) || !array_key_exists('refresh_token', $token)) {
                $this->cacheToken($client);
            } else {
                try {
                    $client->refreshToken($token['refresh_token']);
                    printf("Successfully loaded token from '%s'.\n", $tokenFile);
                } catch (Google_Auth_Exception $exception) {
                    $this->cacheToken($client);
                }
            }
            return;
        }
        // All authentication failed.
        $msg = sprintf('Could not find or read credentials from '
            . 'either the Google Application Default credentials, '
            . '%s, or %s.', $accountFile, $oauthFile);
        throw new DomainException($msg);
    }

    /**
     * Prepares the service and requestService fields, taking into
     * consideration any needed endpoint changes.
     */
    private function prepareServices($client) {

        $this->service = new Google_Service_ShoppingContent($client);
        // Fetch the standard rootUrl and basePath to set things up
        // for sandbox creation.
        $class = new ReflectionClass('Google_Service_Resource');
        $rootProperty = $class->getProperty('rootUrl');
        $rootProperty->setAccessible(true);
        $pathProperty = $class->getProperty('servicePath');
        $pathProperty->setAccessible(true);
        $rootUrl = $rootProperty->getValue($this->service->accounts);
        $basePath = $pathProperty->getValue($this->service->accounts);

        // Attempt to determine a sandbox endpoint from the given endpoint.
        // If we can't, then fall back to using the same endpoint for
        // sandbox methods.
        $pathParts = explode('/', rtrim($basePath, '/'));

        if ($pathParts[count($pathParts) - 1] === 'v2.1') {
            $pathParts = array_slice($pathParts, 0, -1);
            $pathParts[] = $this->mode == 'sandbox'?'v2sandbox':'v2';
            $basePath = implode('/', $pathParts) . '/';
        } else {
            print 'Using same endpoint for sandbox methods.';
        }
        $this->requestService = $this->getServiceWithEndpoint($client, $rootUrl, $basePath);
    }

    /**
     * Creates a new Content API service object from the given client
     * and changes the rootUrl and/or the basePath of the Content API
     * service resource objects within.
     */
    private function getServiceWithEndpoint($client, $rootUrl, $basePath) {
        $service = new Google_Service_ShoppingContent($client);
        // First get the fields that are directly defined in
        // Google_Service_ShoppingContent, as those are the fields that
        // contain the different service resource objects.
        $gsClass = new ReflectionClass('Google_Service');
        $gsscClass = new ReflectionClass('Google_Service_ShoppingContent');
        $gsProps = $gsClass->getProperties(ReflectionProperty::IS_PUBLIC);
        $gsscProps = array_diff($gsscClass->getProperties(ReflectionProperty::IS_PUBLIC), $gsProps);
        // Prepare the properties we (may) be modifying in these objects.
        $class = new ReflectionClass('Google_Service_Resource');
        $rootProperty = $class->getProperty('rootUrl');
        $rootProperty->setAccessible(true);
        $pathProperty = $class->getProperty('servicePath');
        $pathProperty->setAccessible(true);
        foreach ($gsscProps as $prop) {
            $resource = $prop->getValue($service);
            $rootProperty->setValue($resource, $rootUrl);
            $pathProperty->setValue($resource, $basePath);
        }
        return $service;
    }

    /**
     * Retrieves information that can be determined via API calls, including
     * configuration fields that were not provided.
     *
     * <p>Retrieves the following fields if missing:
     * <ul>
     * <li>merchantId
     * </ul>
     *
     * <p>Retrieves the following fields, ignoring any existing configuration:
     * <ul>
     * <li>isMCA
     * <li>websiteUrl
     * </ul>
     */
    public function retrieveConfig() {
        $response = $this->service->accounts->authinfo();

        if (is_null($response->getAccountIdentifiers())) {
            throw new InvalidArgumentException('Authenticated user has no access to any Merchant Center accounts');
        }
        // If there is no configured Merchant Center account ID, use the first one
        // that this user has access to.
        if (array_key_exists('merchantId', $this->config)) {
            $this->merchantId = strval($this->config['merchantId']);
        } else {
            $firstAccount = $response->getAccountIdentifiers()[0];
            if (!is_null($firstAccount->getMerchantId())) {
                $this->merchantId = $firstAccount->getMerchantId();
            } else {
                $this->merchantId = $firstAccount->getAggregatorId();
            }
            printf("Running samples on Merchant Center %d.\n", $this->merchantId);
        }

        // The current account can only be an aggregator if the authenticated
        // account has access to it (is a user) and it's listed in authinfo as
        // an aggregator.
        $this->mcaStatus = false;
        foreach ($response->getAccountIdentifiers() as $accountId) {
            if (!is_null($accountId->getAggregatorId()) &&
                ($accountId->getAggregatorId() === $this->merchantId)) {
                $this->mcaStatus = true;
                break;
            }
            if (!is_null($accountId->getMerchantId()) &&
                ($accountId->getMerchantId() === $this->merchantId)) {
                break;
            }
        }
        printf("Merchant Center %d is%s an MCA.\n",
            $this->merchantId, $this->mcaStatus ? '' : ' not');
        $account = $this->service->accounts->get(
            $this->merchantId, $this->merchantId);
        $this->websiteUrl = $account->getWebsiteUrl();
        if (is_null($this->websiteUrl)) {
            printf("No website listed for Merchant Center %d.\n", $this->merchantId);
        } else {
            printf("Website for Merchant Center %d: %s\n",
                $this->merchantId, $this->websiteUrl);
        }
    }

    private function getMerchantBasicData(){
        $response = $this->service->accounts->authinfo();

        if (is_null($response->getAccountIdentifiers())) {
            throw new InvalidArgumentException('Authenticated user has no access to any Merchant Center accounts');
        }
        // If there is no configured Merchant Center account ID, use the first one
        // that this user has access to.
        if (array_key_exists('merchantId', $this->config)) {
            $this->merchantId = strval($this->config['merchantId']);
        } else {
            $firstAccount = $response->getAccountIdentifiers()[0];
            if (!is_null($firstAccount->getMerchantId())) {
                $this->merchantId = $firstAccount->getMerchantId();
            } else {
                $this->merchantId = $firstAccount->getAggregatorId();
            }
        }

        // The current account can only be an aggregator if the authenticated
        // account has access to it (is a user) and it's listed in authinfo as
        // an aggregator.
        $this->mcaStatus = false;
        foreach ($response->getAccountIdentifiers() as $accountId) {
            if (!is_null($accountId->getAggregatorId()) &&
                ($accountId->getAggregatorId() === $this->merchantId)) {
                $this->mcaStatus = true;
                break;
            }
            if (!is_null($accountId->getMerchantId()) &&
                ($accountId->getMerchantId() === $this->merchantId)) {
                break;
            }
        }
    }

}