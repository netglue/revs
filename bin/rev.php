#!/usr/bin/env php
<?php
declare(strict_types=1);

use Netglue\Revs\Command\RevCommand;
use Symfony\Component\Console\Application;

$composerSearch = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];

foreach ($composerSearch as $file) {
    if (file_exists($file)) {
        define('REVS_COMPOSER_INSTALL', $file);
        break;
    }
}

unset($file);
if (! defined('REVS_COMPOSER_INSTALL')) {
    fwrite(
        STDERR,
        'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
        '    composer install' . PHP_EOL . PHP_EOL .
        'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
    );
    die(1);
}

require REVS_COMPOSER_INSTALL;

$console = new Application();
$console->setName('Filename Revving Tools');
$console->add(new RevCommand);
$console->run();
