<?php

$pharArchiveName = 'convert-for-composer.phar';
$phar = new Phar($pharArchiveName, 0, $pharArchiveName);
$phar->startBuffering();
$defaultStub = $phar->createDefaultStub('bin/convert-for-composer.php');
$phar->buildFromDirectory(__DIR__, '/\.php$/');
$stub = "#!/usr/bin/env php\n".$defaultStub;
$phar->setStub($stub);
$phar->stopBuffering();

$newPharArchivePath = '../' . basename($pharArchiveName, '.phar');
rename($pharArchiveName, $newPharArchivePath);
chmod($newPharArchivePath, '755');

