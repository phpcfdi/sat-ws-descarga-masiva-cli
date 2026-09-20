<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\ExecutionException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\ServiceBuilder;
use PhpCfdi\SatWsDescargaMasiva\Services\Verify\VerifyResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyCommand extends WithFielAbstractCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('ws:verifica');
        $this->setDescription('Verifica el estado de una solicitud');
        // TODO: poner una descripción más larga
        $this->setHelp('Verifica el estado de una solicitud');

        $this->addArgument('solicitud', InputArgument::REQUIRED, 'Identificador de la solicitud');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serviceBuilder = new ServiceBuilder($input, $output);
        $service = $serviceBuilder->obtainService();

        $requestId = $this->buildRequestIdFromInput($input);

        $output->writeln([
            'Verificación:',
            sprintf('  RFC: %s', $serviceBuilder->obtainRfc()),
            sprintf('  Identificador de la solicitud: %s', $requestId),
        ]);

        $verifyResult = $service->verify($requestId);

        $verifyStatus = $verifyResult->getStatus();
        $downloadStatus = $verifyResult->getCodeRequest();
        $statusRequest = $verifyResult->getStatusRequest();
        $output->writeln([
            'Resultado:',
            sprintf('  Verificación: %d - %s', $verifyStatus->getCode(), $verifyStatus->getMessage()),
            sprintf('  Estado de la solicitud: %d - %s', $statusRequest->getValue(), $statusRequest->getMessage()),
            sprintf('  Estado de la descarga: %d - %s', $downloadStatus->getValue(), $downloadStatus->getMessage()),
            sprintf('  Número de CFDI: %d', $verifyResult->getNumberCfdis()),
            sprintf('  Paquetes: %s', implode(', ', $verifyResult->getPackagesIds())),
        ]);

        return $this->processResult($verifyResult);
    }

    private function buildRequestIdFromInput(InputInterface $input): string
    {
        /** @var string $requestId */
        $requestId = $input->getArgument('solicitud');
        return $requestId;
    }

    public function processResult(VerifyResult $verifyResult): int
    {
        $status = $verifyResult->getStatus();
        if (! $status->isAccepted()) {
            throw ExecutionException::make(
                sprintf('La petición no fue aceptada: %s - %s', $status->getCode(), $status->getMessage()),
            );
        }

        $downloadStatus = $verifyResult->getCodeRequest();
        if (
            $downloadStatus->isDuplicated()
            || $downloadStatus->isExhausted()
            || $downloadStatus->isMaximumLimitReaded()
        ) {
            throw ExecutionException::make(sprintf(
                'El código de estado de la solicitud de descarga no es correcto: %s - %s',
                $downloadStatus->getValue(),
                $downloadStatus->getMessage(),
            ));
        }

        $statusRequest = $verifyResult->getStatusRequest();
        if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
            throw ExecutionException::make(sprintf(
                'El estado de solicitud de la descarga no es correcto: %s - %s',
                $statusRequest->getValue(),
                $statusRequest->getMessage(),
            ));
        }

        return Command::SUCCESS;
    }
}
