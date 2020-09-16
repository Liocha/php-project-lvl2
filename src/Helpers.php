<?php

namespace Differ\Helpers;

function fixBoolVal($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}
