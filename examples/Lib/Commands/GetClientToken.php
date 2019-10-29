<?php

namespace Legalweb\MortgageSourceClientExample\Lib\Commands;

use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Option;
use Legalweb\MortgageSourceClient\MortgageSourceService;
use Legalweb\MortgageSourceClient\Traits\Castable;
use Legalweb\MortgageSourceClientExample\Lib\Traits\Configurable;

class GetClientToken extends Command {

    use Castable;
    use Configurable;

    public function __construct()
    {
        $options = [
            Option::create("u", "user", Getopt::REQUIRED_ARGUMENT)->setDescription("Specify user to act on behalf of"),
        ];

        parent::__construct('getclienttoken', [$this, 'handle'], $options);
    }

    /**
     * @param GetOpt $opt
     */
    public function handle(GetOpt $opt)
    {
        try {
            $c = $this->getConfig($opt);
        } catch (\Exception $exception) {
            trigger_error("Invalid configuration: " . $exception->getMessage());
            return;
        }

        try {
            $cs = MortgageSourceService::NewMortgageSourceService($c, false, $opt->getOption("user"));
            $r = $cs->GetClientToken();

            if ($r) {
                echo "\nToken: ", $r->Token, "\nVendor: ", $r->Vendor, "\nExpires: ", $r->Expires, "\n";
            } else {
                echo "\nNo token retrieved.\n";
            }
        } catch (\Exception $exception) {
            trigger_error("Unexpected error occurred: " . $exception->getMessage());
        }
    }
}