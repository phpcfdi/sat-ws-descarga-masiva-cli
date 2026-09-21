<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\WithFielAbstractCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\ServiceBuilder;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers\TemporaryFile;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final class WithfieldAbstractCommandTest extends TestCase
{
    /** @param array<string, string> $inputParameters */
    public function createServiceBuilder(array $inputParameters): ServiceBuilder
    {
        $command = new class () extends WithFielAbstractCommand {
        };
        $input = new ArrayInput($inputParameters, $command->getDefinition());
        $output = $this->createStub(OutputInterface::class);
        return new ServiceBuilder($input, $output);
    }

    public function testServiceWithoutDefinition(): void
    {
        $builder = $this->createServiceBuilder([]);

        $serviceEndpoints = $builder->obtainServiceEndPoints();
        $this->assertTrue($serviceEndpoints->getServiceType()->isCfdi());
    }

    public function testServiceWithCfdi(): void
    {
        $builder = $this->createServiceBuilder([
            '--servicio' => 'cfdi',
        ]);

        $serviceEndpoints = $builder->obtainServiceEndPoints();
        $this->assertTrue($serviceEndpoints->getServiceType()->isCfdi());
    }

    public function testServiceWithRetencion(): void
    {
        $builder = $this->createServiceBuilder([
            '--servicio' => 'retenciones',
        ]);

        $serviceEndpoints = $builder->obtainServiceEndPoints();
        $this->assertTrue($serviceEndpoints->getServiceType()->isRetenciones());
    }

    public function testServiceWithInvalidValue(): void
    {
        $builder = $this->createServiceBuilder([
            '--servicio' => 'xxx',
        ]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('La opción "servicio" no es válida');
        $builder->obtainServiceEndPoints();
    }

    public function testBuildFielFromInputWithoutCertificate(): void
    {
        $builder = $this->createServiceBuilder([]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('La opción "certificado" no es válida');
        $builder->obtainFiel();
    }

    public function testBuildFielWithPasswordFromServer(): void
    {
        $passwordOnServer = $_SERVER['EFIRMA_PASSPHRASE'] ?? null;
        $_SERVER['EFIRMA_PASSPHRASE'] = trim($this->fileContents('fake-fiel/EKU9003173C9-password.txt'));
        $builder = $this->createServiceBuilder([
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
        ]);

        try {
            $fiel = $builder->obtainFiel();
        } finally {
            if (null === $passwordOnServer) {
                unset($_SERVER['EFIRMA_PASSPHRASE']);
            } else {
                $_SERVER['EFIRMA_PASSPHRASE'] = $passwordOnServer;
            }
        }

        $this->assertSame('EKU9003173C9', $fiel->getRfc());
    }

    public function testBuildFielFromInputWithoutPrimaryKey(): void
    {
        $builder = $this->createServiceBuilder([
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
        ]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('La opción "llave" no es válida');
        $builder->obtainFiel();
    }

    public function testBuildFielFromInputWithoutPassword(): void
    {
        $builder = $this->createServiceBuilder([
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
        ]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('No fue posible crear la eFirma');
        $builder->obtainFiel();
    }

    public function testBuildFielFromInputWithIncorrectPassword(): void
    {
        $builder = $this->createServiceBuilder([
            '--certificado' => $this->filePath('fake-fiel/EKU9003173C9.cer'),
            '--llave' => $this->filePath('fake-fiel/EKU9003173C9.key'),
            '--password' => trim($this->fileContents('fake-fiel/EKU9003173C9-password.txt')) . '-foo',
        ]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('No fue posible crear la eFirma');
        $builder->obtainFiel();
    }

    public function testBuildFielWithEfirma(): void
    {
        $builder = $this->createServiceBuilder([
            '--efirma' => $this->filePath('fake-fiel/EKU9003173C9-efirma.json'),
        ]);
        $fiel = $builder->obtainFiel();

        $this->assertSame('EKU9003173C9', $fiel->getRfc());
    }

    #[TestWith(['not a json content'])]
    #[TestWith([''])]
    public function testBuildFielWithInvalidJsonContents(string $invalidJson): void
    {
        $temporaryFile = new TemporaryFile();
        $temporaryFile->putContents($invalidJson);
        $builder = $this->createServiceBuilder([
            '--efirma' => $temporaryFile->getPath(),
        ]);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage(
            sprintf(
                'El archivo de configuración de eFirma "%s" no se pudo interpretar como JSON',
                $temporaryFile->getPath(),
            ),
        );
        $builder->obtainFiel();
    }
}
