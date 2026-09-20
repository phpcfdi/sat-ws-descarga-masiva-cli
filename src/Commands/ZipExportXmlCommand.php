<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\PackageReader\CfdiPackageReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ZipExportXmlCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('zip:xml');
        $this->setDescription('Exporta un archivo ZIP con archivos XML a una carpeta');
        // TODO: poner una descripción más larga
        $this->setHelp('Exporta un archivo ZIP con archivos XML a una carpeta');

        $this->addArgument('paquete', InputArgument::REQUIRED, 'Archivo del paquete CFDI');
        $this->addArgument('destino', InputArgument::REQUIRED, 'Ruta de la carpeta de destino');
    }

    public function buildSourceFromInput(InputInterface $input): string
    {
        /** @var string $source */
        $source = $input->getArgument('paquete');
        return $source;
    }

    public function buildDestinationFromInput(InputInterface $input): string
    {
        /** @var string $destination */
        $destination = $input->getArgument('destino');
        return $destination;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourcePath = $this->buildSourceFromInput($input);
        $destinationPath = $this->buildDestinationFromInput($input);

        $fs = new Filesystem();
        try {
            if (! $fs->exists($sourcePath)) {
                throw new ExecutionException("El archivo $sourcePath no existe");
            }
            $cfdiReader = CfdiPackageReader::createFromFile($sourcePath);
        } catch (Throwable $exception) {
            throw ExecutionException::make("El archivo $sourcePath no se pudo abrir", $exception);
        }

        if (! $fs->isDirectory($destinationPath)) {
            throw ExecutionException::make("La carpeta de destino $destinationPath no existe");
        }
        if (! $fs->isWritable($destinationPath)) {
            throw ExecutionException::make("La carpeta de destino $destinationPath no se puede escribir");
        }

        $totalFiles = $cfdiReader->count();
        $exported = 0;
        foreach ($cfdiReader->cfdis() as $cfdi => $content) {
            $destinationFile = sprintf('%s/%s.xml', $destinationPath, $cfdi);
            try {
                $fs->write($destinationFile, $content);
            } catch (Throwable $exception) {
                $message = sprintf(
                    'Error al escribir %s, se exportaron %d de %d archivos',
                    $destinationFile,
                    $exported,
                    $totalFiles,
                );
                throw ExecutionException::make($message, $exception);
            }
            $exported = $exported + 1;
        }
        $output->writeln(sprintf('Exportados %d archivos', $exported));

        return Command::SUCCESS;
    }
}
