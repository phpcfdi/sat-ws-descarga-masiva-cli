<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\ServiceBuilder;
use PhpCfdi\SatWsDescargaMasiva\Services\Download\DownloadResult;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadCommand extends WithFielAbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('ws:descarga');
        $this->setDescription('Descarga un paquete');
        // TODO: poner una descripción más larga
        $this->setHelp('Descarga un paquete previamente confirmado');

        $this->addOption('destino', '', InputOption::VALUE_REQUIRED, 'Carpeta de destino', '');

        $this->addArgument('paquete', InputArgument::REQUIRED, 'Identificador del paquete');
    }

    private function buildPackageIdFromInput(InputInterface $input): string
    {
        $packageId = $input->getArgument('paquete');
        if (! is_string($packageId)) {
            throw new InputException('El argumento "paquete" no es válido', 'paquete');
        }

        return $packageId;
    }

    private function buildDestinationFromInput(InputInterface $input): string
    {
        /** @var string $destinationFolder */
        $destinationFolder = $input->getOption('destino');
        if ('' === $destinationFolder) {
            $destinationFolder = '.';
        }

        $fs = new Filesystem();
        if (! $fs->isDirectory($destinationFolder)) {
            throw new InputException('La opción "destino" no es una carpeta', 'destino');
        }
        if (! $fs->isWritable($destinationFolder)) {
            throw new InputException('La opción "destino" no tiene los permisos de escritura', 'destino');
        }

        return $destinationFolder;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serviceBuilder = new ServiceBuilder($input, $output);
        $service = $serviceBuilder->obtainService();

        $packageId = $this->buildPackageIdFromInput($input);
        $destinationFolder = $this->buildDestinationFromInput($input);
        $destinationFile = sprintf(
            '%s%s%s.zip',
            rtrim($destinationFolder, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            strtolower($packageId),
        );

        $output->writeln([
            'Descarga:',
            sprintf('  RFC: %s', $serviceBuilder->obtainRfc()),
            sprintf('  Identificador del paquete: %s', $packageId),
            sprintf('  Destino: %s', $destinationFile),
        ]);

        $downloadResult = $service->download($packageId);
        $downloadStatus = $downloadResult->getStatus();

        $output->writeln([
            'Resultado:',
            sprintf('  Descarga: %d - %s', $downloadStatus->getCode(), $downloadStatus->getMessage()),
            sprintf('  Tamaño: %d', $downloadResult->getPackageSize()),
        ]);

        return $this->processResult($downloadResult, $destinationFile);
    }

    public function processResult(DownloadResult $downloadResult, string $destinationFile): int
    {
        $status = $downloadResult->getStatus();
        if (! $status->isAccepted()) {
            throw ExecutionException::make(
                sprintf('La petición no fue aceptada: %s - %s', $status->getCode(), $status->getMessage()),
            );
        }

        try {
            $fs = new Filesystem();
            $fs->write($destinationFile, $downloadResult->getPackageContent());
        } catch (RuntimeException $exception) {
            throw ExecutionException::make(
                sprintf('No se ha podido escribir el archivo %s', $destinationFile),
                $exception,
            );
        }

        return self::SUCCESS;
    }
}
