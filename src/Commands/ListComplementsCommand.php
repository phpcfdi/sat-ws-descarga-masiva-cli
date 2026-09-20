<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Commands;

use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoCfdi;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoRetenciones;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ListComplementsCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this->setName('info:complementos');
        $this->setDescription('Muestra el listado de complementos de CFDI o Retenciones');

        $this->addOption('servicio', '', InputOption::VALUE_REQUIRED, 'Cfdi o Retenciones', 'Cfdi');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $labels = $this->buildComplementoLabels($input);
        $labels = array_filter($labels, 'boolval', ARRAY_FILTER_USE_KEY); // remove "undefined"

        $table = new Table($output);
        $table->setHeaders(['Código', 'Descripción']);
        foreach ($labels as $code => $label) {
            $table->addRow([$code, $label]);
        }
        $table->render();

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function buildComplementoLabels(InputInterface $input): array
    {
        /** @var string $serviceInput */
        $serviceInput = $input->getOption('servicio');
        $serviceInput = strtolower($serviceInput);
        return match ($serviceInput) {
            'cfdi' => ComplementoCfdi::getLabels(),
            'retenciones' => ComplementoRetenciones::getLabels(),
            default => throw new Exceptions\InputException(
                'La opción "servicio" no es válida, debe ser "cfdi" o "retenciones"',
                'servicio',
            ),
        };
    }
}
