<?php

require __DIR__ . '/../Converter.php';

$converter = new \Isitnikov\Converter\Converter($argv);
echo $converter->convert();

