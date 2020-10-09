<?php

namespace Differ\Formatters\Json;

function render($diffTree)
{
    return json_encode($diffTree);
}
