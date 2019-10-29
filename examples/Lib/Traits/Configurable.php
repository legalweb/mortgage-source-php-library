<?php

namespace Legalweb\MortgageSourceClientExample\Lib\Traits;

use GetOpt\GetOpt;
use Yosymfony\Toml\Toml;
use Legalweb\MortgageSourceClient\Config;

trait Configurable {

    /**
     * @param GetOpt $opt
     *
     * @return Config
     */
    protected function getConfig(GetOpt $opt) {
        $c = new Config();

        list($client, $secret, $endpoint, $verifyssl) = $this->getConfigOptions($opt);

        $c->Name = "Example";
        $c->Client = $client;
        $c->Secret = $secret;
        $c->EndPoint = $endpoint;
        $c->VerifySSL = $verifyssl;

        return $c;
    }

    /**
     * @param GetOpt $opt
     *
     * @return array
     */
    protected function getConfigOptions(GetOpt $opt) {
        $config = $opt->getOption('config');

        $client = "";
        $secret = "";
        $endpoint = "";
        $verifyssl = false;

        if (is_null($config)) {
            $client = $opt->getOption('client');
            $secret = $opt->getOption('secret');
            $endpoint = $opt->getOption('endpoint');
            $verifyssl = $opt->getOption('verifyssl');

            if (!$client || !$secret || !$endpoint) {
                throw new \InvalidArgumentException("client, secret & endpoint must be set if not providing config file");
            }
        } else {
            $c = Toml::parseFile($config);

            if (!$c['CLIENT'] || !$c['SECRET'] || !$c['ENDPOINT']) {
                throw new \InvalidArgumentException("client, secret & endpoint must be set if not providing config file");
            }

            $client = $c['CLIENT'];
            $secret = $c['SECRET'];
            $endpoint = $c['ENDPOINT'];
            $verifyssl = $c['VERIFYSSL'];
        }

        return [ $client, $secret, $endpoint, $verifyssl ];
    }

}