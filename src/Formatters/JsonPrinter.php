<?php

namespace Differ\Formatters\JsonPrinter;

function json_printer($diff_tree)
{
    return json_encode($diff_tree);
}
