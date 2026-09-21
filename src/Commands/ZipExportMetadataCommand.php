<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use Eclipxe\XlsxExporter\CellTypes;
use Eclipxe\XlsxExporter\Column;
use Eclipxe\XlsxExporter\Columns;
use Eclipxe\XlsxExporter\Style;
use Eclipxe\XlsxExporter\Styles;
use Eclipxe\XlsxExporter\WorkBook;
use Eclipxe\XlsxExporter\WorkSheet;
use Eclipxe\XlsxExporter\WorkSheets;
use Eclipxe\XlsxExporter\XlsxExporter;
use Iterator;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\ZipExportMetadataCommand\MetadataProviderIterator;
use PhpCfdi\SatWsDescargaMasiva\CLI\Internal\Filesystem;
use PhpCfdi\SatWsDescargaMasiva\PackageReader\MetadataItem;
use PhpCfdi\SatWsDescargaMasiva\PackageReader\MetadataPackageReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ZipExportMetadataCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('zip:metadata');
        $this->setDescription('Exporta un archivo ZIP con Metadata a XSLX');
        // TODO: poner una descripción más larga
        $this->setHelp('Exporta un archivo ZIP con Metadata a XSLX');

        $this->addArgument('metadata', InputArgument::REQUIRED, 'Archivo del paquete metadata');
        $this->addArgument('destino', InputArgument::REQUIRED, 'Archivo de salida xlsx');
    }

    public function buildSourceFromInput(InputInterface $input): string
    {
        /** @var string $source */
        $source = $input->getArgument('metadata');
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

        // create package reader
        $fs = new Filesystem();
        try {
            if (! $fs->exists($sourcePath) || $fs->isDirectory($sourcePath)) {
                throw new ExecutionException("El archivo $sourcePath no existe");
            }
            $packageReader = MetadataPackageReader::createFromFile($sourcePath);
        } catch (Throwable $exception) {
            throw ExecutionException::make("El archivo $sourcePath no se pudo abrir", $exception);
        }

        // set up workbook
        $dateTimeStyle = new Style(['format' => ['code' => Styles\Format::FORMAT_DATE_YMDHM]]);
        $currencyStyle = new Style([
            'format' => ['code' => Styles\Format::FORMAT_ACCOUNTING_00],
        ]);
        $count = $packageReader->count();
        /**
         * @see MetadataItem for column names
         * @var Iterator<string, MetadataItem> $iterator
         */
        $iterator = $packageReader->metadata();
        $provider = new MetadataProviderIterator($iterator, $count);
        $workbook = new WorkBook(
            new WorkSheets(
                new WorkSheet('data', $provider, new Columns(
                    new Column('uuid', 'UUID'),
                    new Column('rfcEmisor', 'RFC Emisor'),
                    new Column('nombreEmisor', 'Emisor'),
                    new Column('rfcReceptor', 'RFC Receptor'),
                    new Column('nombreReceptor', 'Receptor'),
                    new Column('rfcPac', 'RFC PAC'),
                    new Column('fechaEmision', 'Emisión', CellTypes::DATETIME, $dateTimeStyle),
                    new Column('fechaCertificacionSat', 'Certificación', CellTypes::DATETIME, $dateTimeStyle),
                    new Column('monto', 'Monto', CellTypes::NUMBER, $currencyStyle),
                    new Column('efectoComprobante', 'Efecto'),
                    new Column('estatus', 'Estado', CellTypes::NUMBER),
                    new Column('fechaCancelacion', 'Cancelación', CellTypes::DATETIME, $dateTimeStyle),
                    new Column('rfcACuentaTerceros', 'RFC a cuenta de terceros'),
                    new Column('nombreACuentaTerceros', 'A cuenta de terceros'),
                )),
            ),
        );

        // export to file
        try {
            XlsxExporter::save($workbook, $destinationPath);
        } catch (Throwable $exception) {
            throw ExecutionException::make("El archivo $destinationPath no se pudo escribir", $exception);
        }

        return Command::SUCCESS;
    }
}
