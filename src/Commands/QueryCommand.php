<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Common\LabelMethodsTrait;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand\QueryBuilder;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\ServiceBuilder;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryResult;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends WithFielAbstractCommand
{
    use LabelMethodsTrait;

    protected function configure(): void
    {
        parent::configure();

        $this->setName('ws:consulta');
        $this->setDescription('Genera una consulta y devuelve el número de solicitud');
        // TODO: poner una descripción más larga
        $this->setHelp('Genera una consulta y devuelve el número de solicitud');

        $this->addOption('desde', '', InputOption::VALUE_REQUIRED, 'Inicio del periodo de consulta');
        $this->addOption('hasta', '', InputOption::VALUE_REQUIRED, 'Fin del periodo de consulta');
        $this->addOption('tipo', '', InputOption::VALUE_REQUIRED, 'Recibidos o emitidos', 'emitidos');
        $this->addOption('rfc', '', InputOption::VALUE_REQUIRED, 'Filtra por el RFC de contraparte', '');
        $this->addOption('paquete', '', InputOption::VALUE_REQUIRED, 'Xml o metadata', 'metadata');
        $this->addOption('estado', '', InputOption::VALUE_REQUIRED, 'Indefinido, vigentes o canceladas', '');
        $this->addOption(
            'documento',
            '',
            InputOption::VALUE_REQUIRED,
            'Indefinido, ingreso, egreso, traslado, pago o nómina',
            '',
        );
        $this->addOption('complemento', '', InputOption::VALUE_REQUIRED, 'Filtra por el tipo de complemento', '');
        $this->addOption('tercero', '', InputOption::VALUE_REQUIRED, 'Filtra por el RFC a cuenta de terceros', '');
        $this->addOption('uuid', '', InputOption::VALUE_REQUIRED, 'Filtra por el UUID especificado', '');
        $this->addOption('no-prevalidar', '', InputOption::VALUE_NONE, 'No valida si la consulta es correcta');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serviceBuilder = new ServiceBuilder($input, $output);
        $service = $serviceBuilder->obtainService();

        $serviceType = $serviceBuilder->obtainServiceEndPoints()->getServiceType();
        $queryParameters = $this->buildQueryParametersFromInput($input, $serviceType);

        $output->writeln([
            'Consulta:',
            sprintf('  Servicio: %s', $this->getServiceTypeLabel($serviceType)),
            sprintf('  Paquete: %s', $this->getRequestTypeLabel($queryParameters->getRequestType())),
            sprintf('  RFC: %s', $serviceBuilder->obtainRfc()),
        ]);

        if ($queryParameters->getUuid()->isEmpty()) {
            $output->writeln([
                sprintf('  Desde: %s', $queryParameters->getPeriod()->getStart()->format('Y-m-d H:i:s')),
                sprintf('  Hasta: %s', $queryParameters->getPeriod()->getEnd()->format('Y-m-d H:i:s')),
                sprintf('  Tipo: %s', $this->getDownloadTypeLabel($queryParameters->getDownloadType())),
                sprintf('  RFC de/para: %s', $this->getRfcMatchLabel($queryParameters->getRfcMatch())),
                sprintf('  Documentos: %s', $this->getDocumentTypeLabel($queryParameters->getDocumentType())),
                sprintf('  Complemento: %s', $this->getComplementLabel($queryParameters->getComplement())),
                sprintf('  Estado: %s', $this->getDocumentStatusLabel($queryParameters->getDocumentStatus())),
                sprintf('  Tercero: %s', $this->getOnBehalfLabel($queryParameters->getRfcOnBehalf())),
            ]);
        } else {
            $output->writeln([
                sprintf('  UUID: %s', $this->getUuidLabel($queryParameters->getUuid())),
            ]);
        }

        $noValidate = $this->obtainNoValidateOption($input);
        if (! $noValidate) {
            $queryErrors = $queryParameters->validate();
            if ([] !== $queryErrors) {
                throw new InputException(implode(PHP_EOL, $queryErrors), '');
            }
        }

        $queryResult = $service->query($queryParameters);
        $queryStatus = $queryResult->getStatus();

        $output->writeln([
            'Resultado:',
            sprintf('  Consulta: %d - %s', $queryStatus->getCode(), $queryStatus->getMessage()),
            sprintf('  Identificador de solicitud: %s', $queryResult->getRequestId() ?: '(ninguno)'),
        ]);

        return $this->processResult($queryResult);
    }

    public function buildQueryParametersFromInput(InputInterface $input, ServiceType $serviceType): QueryParameters
    {
        $builder = new QueryBuilder($input, $serviceType);
        return $builder->build();
    }

    private function obtainNoValidateOption(InputInterface $input): bool
    {
        $value = $input->getOption('no-prevalidar');
        return is_bool($value) && $value;
    }

    public function processResult(QueryResult $queryResult): int
    {
        $status = $queryResult->getStatus();
        if (! $status->isAccepted()) {
            throw ExecutionException::make(
                sprintf('La petición no fue aceptada: %s - %s', $status->getCode(), $status->getMessage()),
            );
        }

        return self::SUCCESS;
    }
}
