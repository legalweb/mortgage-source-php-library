<?php

namespace Legalweb\MortgageSourceClient;

use Legalweb\MortgageSourceClient\Traits\Castable;

class ClientToken
{
    use Castable;

    var $Expires;
    var $Token;
    var $Vendor;

    /**
     * @param \stdClass $s
     *
     * @return ClientToken
     */
    public static function FromStdClass(\stdClass $s)
    {
        $d = new ClientToken();

        $d = self::Cast($s, $d);

        return $d;
    }
}