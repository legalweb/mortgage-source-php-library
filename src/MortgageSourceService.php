<?php

namespace Legalweb\MortgageSourceClient;

use Legalweb\MortgageSourceClient\Exceptions\AccessForbiddenException;
use Legalweb\MortgageSourceClient\Exceptions\APIRequestException;
use Legalweb\MortgageSourceClient\Exceptions\APIUnavailableException;
use Legalweb\MortgageSourceClient\Exceptions\ClientTokenDecodingException;
use Legalweb\MortgageSourceClient\Exceptions\InvalidJSONResponseException;
use Legalweb\MortgageSourceClient\Exceptions\NotConfiguredException;
use Legalweb\MortgageSourceClient\Exceptions\TokenNotFoundException;
use Legalweb\MortgageSourceClient\Exceptions\UserNotConfiguredException;

/**
 * Class MortgageSourceService
 * @package Legalweb\MortgageSourceClient
 */
class MortgageSourceService
{
    /**
     * @var MortgageSourceService
     */
    protected static $defaultInstance;

    /**
     * @var MortgageSourceService[]
     */
    protected static $instances = [];

    /** @var Config */
    protected $config;

    /** @var string */
    protected $user = "";

    /**
     * @param Config $config
     * @param bool   $isDefault
     * @param string $user
     *
     * @return MortgageSourceService
     */
    public static function NewMortgageSourceService(Config $config, bool $isDefault = false, $user = "")
    {
        $cs = new MortgageSourceService();
        $cs->SetConfig($config);

        if (is_null(self::$defaultInstance) || $isDefault) {
            self::$defaultInstance = $cs;
        }

        if (strlen($config->Name) > 0) {
            self::$instances[$config->Name] = $cs;
        }

        if (strlen($user) > 0) {
            $cs->SetUser($user);
        }

        return $cs;
    }

    /**
     * @param string $name
     *
     * @return MortgageSourceService|null
     * @throws NotConfiguredException
     */
    public static function GetInstance(string $name = '')
    {
        if (strlen($name) > 0) {
            if (isset(self::$instances[$name])) {
                return self::$instances[$name];
            } else {
                throw new NotConfiguredException("Mortgage Source Service " . $name . " not configured");
            }
        }

        if (isset(self::$defaultInstance)) {
            return self::$defaultInstance;
        }

        throw new NotConfiguredException("Mortgage Source Service not configured");

        return null;
    }

    /**
     * @param Config $config
     */
    protected function SetConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $user
     */
    public function SetUser(string $user)
    {
        $this->user = $user;
    }

    /**
     * @return ClientToken|null
     * @throws TokenNotFoundException
     */
    public function GetClientToken() {
        $this->mustHaveUser();

        $r = $this->curlRequest("/token/");

        if ($r === null) {
            return null;
        }

        if (!isset($r->Token)) {
            throw new TokenNotFoundException("Token not found in JSON response");
        }

        return ClientToken::FromStdClass($r->Token);
    }

    /**
     * @return bool
     * @throws UserNotConfiguredException
     */
    protected function mustHaveUser()
    {
        if (strlen($this->user) > 0) {
            return true;
        }

        throw new UserNotConfiguredException("User not configured for request");
    }

    /**
     * @param string $r
     *
     * @return object|null
     * @throws APIRequestException
     * @throws ClientTokenDecodingException
     * @throws InvalidJSONResponseException
     */
    protected function decodeResponse(string $r) {
        if ($r === null) {
            return null;
        }

        $o = json_decode($r);

        if ($o === null) {
            throw new ClientTokenDecodingException("Error decoding JSON response for client token");
        }

        if (!isset($o->ResponseCode) || !isset($o->Response)) {
            throw new InvalidJSONResponseException("Invalid JSON response");
        }

        if ($o->ResponseCode !== 200) {
            throw new APIRequestException("API request failed", $o->ResponseCode);
        }

        return (object) $o->Response;
    }

    /**
     * @param string $url
     * @param string $json
     *
     * @return object|null
     * @throws APIRequestException
     * @throws APIUnavailableException
     * @throws AccessForbiddenException
     * @throws ClientTokenDecodingException
     * @throws InvalidJSONResponseException
     */
    protected function curlRequest(string $url, string $json = "") {
        $ch = curl_init($this->config->EndPoint . $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config->Client . ":" . $this->config->Secret);

        $headers = [];

        if (strlen($this->user)) {
            $headers[] = "X-Auth-User: " . $this->user;
        }

        if (strlen($json) > 0) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($json);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } else if (count($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!$this->config->VerifySSL) {
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYSTATUS, false);
        }

        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        $r = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (is_bool($r)) {
            if ($r === false) {
                throw new APIRequestException("Error making API request");
            } else {
                throw new APIRequestException("No response data for API request");
            }
        }

        switch ($httpcode) {
            case 503:
                throw new APIUnavailableException("API Service Unavailable");
            case 403:
                throw new AccessForbiddenException("Access Forbidden");
            case 400:
                throw new APIRequestException("Bad API request");
            case 200:
                return $this->decodeResponse((string) $r);
            default:
                throw new APIRequestException("Unhandled API response", $httpcode);
        }
    }
}