<?php

namespace SeedCloud\Validation;

class ID0
{
    public static function IsValid($id0)
    {
        //This checks if it could be an id1, if it is an id1 its very likely that the user copied the wrong foldername
        return preg_match('/^(?![0-9a-fA-F]{4}(01|00)[0-9a-fA-F]{18}00[0-9a-fA-F]{6})[0-9a-fA-F]{32}$/', $id0);
    }
}
