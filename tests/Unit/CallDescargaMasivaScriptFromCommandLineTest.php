<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit;

use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use Symfony\Component\Process\Process;

final class CallDescargaMasivaScriptFromCommandLineTest extends TestCase
{
    public function testCallWithoutArguments(): void
    {
        $workingDirectory = dirname(__DIR__, 2);
        $command = [
            PHP_BINARY,
            'bin/descarga-masiva.php',
        ];
        $process = new Process($command, $workingDirectory);
        $process->run();
        $this->assertSame(0, $process->getExitCode());
    }
}
