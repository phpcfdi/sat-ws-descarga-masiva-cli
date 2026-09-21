<?php

declare(strict_types=1);

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\DownloadCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ListComplementsCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\VerifyCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportMetadataCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportXmlCommand;
use Symfony\Component\Console\Application;

require $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

$application = new Application('descarga-masiva', '@box_git_version@');
$application->setCatchExceptions(true);

// ... register commands
$application->addCommands([
    new QueryCommand(),
    new VerifyCommand(),
    new DownloadCommand(),
    new ListComplementsCommand(),
    new ZipExportMetadataCommand(),
    new ZipExportXmlCommand(),
]);

/** @noinspection PhpUnhandledExceptionInspection */
exit($application->run());
