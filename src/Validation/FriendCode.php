<?php

namespace SeedCloud\Validation;

class FriendCode
{
    private static function CalculateChecksum($principalId)
    {
        return floor(intval(substr(sha1(pack('V', $principalId)), 0, 2), 16) / 2);
    }

    private static function IsInValidPrincipalRange($principal)
    {
        return ($principal > 130543475 && $principal <= 149643182) ||
            ($principal >= 1798000000 && $principal <= 1875939608);
    }

    public static function IsValid($friendCode)
    {
        $friendCode = preg_replace("/[^0-9]/", "", $friendCode);
        if (strlen($friendCode) !== 12) {
            return false;
        }

        //Friendcode as 64bit little endian raw
        $rawFriendcodePayload = pack("P", intval($friendCode));
        list($principalId, $checksum) = array_values(unpack("V2", $rawFriendcodePayload));

        return self::CalculateChecksum($principalId) == $checksum &&
            self::IsInValidPrincipalRange($principalId);
    }
}
