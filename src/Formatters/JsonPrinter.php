<?php

namespace Differ\Formatters\JsonPrinter;

function jsonPrinter($diffTree)
{
    return json_encode($diffTree);
}
