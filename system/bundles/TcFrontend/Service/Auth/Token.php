<?php

namespace TcFrontend\Service\Auth;

use Access_Frontend;

class Token {

    const DELIMITER = '.';

    public static function decode(string $sToken): array {
        $aParts = explode(self::DELIMITER, $sToken);
        if(count($aParts) === 2) {
            return array_combine(['user', 'token'], $aParts);
        }

        return [];
    }

    public static function generate(Access_Frontend $oAccess): string  {
        return implode(self::DELIMITER, [
            $oAccess->getAccessUser(),
            $oAccess->getAccessPass()
        ]);
    }
}
