<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Commands\QueryCommand;

use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\Exceptions\InputException;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand;
use PhpCfdi\SatWsDescargaMasiva\CLI\Commands\QueryCommand\QueryBuilder;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Helpers\ExceptionCatcherTrait;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;

final class QueryBuilderTest extends TestCase
{
    use ExceptionCatcherTrait;

    /** @param string[] $inputs */
    private function createQueryBuilder(array $inputs, ?ServiceType $serviceType = null): QueryBuilder
    {
        $input = new ArrayInput($inputs, new QueryCommand()->getDefinition());
        $serviceType ??= ServiceType::cfdi();
        return new QueryBuilder($input, $serviceType);
    }

    public function testBuildPeriodValid(): void
    {
        $desde = '2020-01-02 03:04:05';
        $hasta = '2020-12-31 23:59:59';
        $queryBuilder = $this->createQueryBuilder([
            '--desde' => $desde,
            '--hasta' => $hasta,
        ]);
        $period = $queryBuilder->buildPeriod();
        $this->assertSame($desde, $period->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame($hasta, $period->getEnd()->format('Y-m-d H:i:s'));
    }

    /** @return array<string, array{string, RequestType}> */
    public static function providerBuildRequestType(): array
    {
        return [
            'metadata' => ['METADATA', RequestType::metadata()],
            'xml' => ['XML', RequestType::xml()],
        ];
    }

    #[DataProvider('providerBuildRequestType')]
    public function testBuildRequestType(string $argument, RequestType $expected): void
    {
        $queryBuilder = $this->createQueryBuilder(['--paquete' => $argument]);
        $requestType = $queryBuilder->buildRequestType();
        $this->assertEquals($expected, $requestType);
    }

    public function testBuildRequestTypeInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--paquete' => '']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildRequestType();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'La opción "paquete" debe ser "xml" o "metadata"',
            $catched->getMessage(),
        );
        $this->assertSame('paquete', $catched->getArgumentName());
    }

    /** @return array<string, array{string, DownloadType}> */
    public static function providerBuildDownloadType(): array
    {
        return [
            'emitidos' => ['EMITIDOS', DownloadType::issued()],
            'recibidos' => ['RECIBIDOS', DownloadType::received()],
        ];
    }

    #[DataProvider('providerBuildDownloadType')]
    public function testBuildDownloadType(string $argument, DownloadType $expected): void
    {
        $queryBuilder = $this->createQueryBuilder(['--tipo' => $argument]);
        $downloadType = $queryBuilder->buildDownloadType();
        $this->assertEquals($expected, $downloadType);
    }

    public function testBuildDownloadTypeInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--tipo' => '']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildDownloadType();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'La opción "tipo" debe ser "recibidos" o "emitidos"',
            $catched->getMessage(),
        );
        $this->assertSame('tipo', $catched->getArgumentName());
    }

    public function testBuildRfcMatchEmpty(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--rfc' => '']);
        $rfc = $queryBuilder->buildRfcMatch();
        $this->assertTrue($rfc->isEmpty());
    }

    public function testBuildRfcMatchValid(): void
    {
        $input = 'AAAA010101AAA';
        $queryBuilder = $this->createQueryBuilder(['--rfc' => $input]);
        $rfc = $queryBuilder->buildRfcMatch();
        $this->assertSame($input, $rfc->getValue());
    }

    public function testBuildRfcMatchInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--rfc' => 'invalid-rfc']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildRfcMatch();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'La opción "rfc" tiene un valor inválido',
            $catched->getMessage(),
        );
        $this->assertSame('rfc', $catched->getArgumentName());
    }

    /** @return array<string, array{string, DocumentStatus}> */
    public static function providerBuildDocumentStatus(): array
    {
        return [
            '(empty)' => ['', DocumentStatus::undefined()],
            'vigentes' => ['VIGENTES', DocumentStatus::active()],
            'canceladas' => ['CANCELADAS', DocumentStatus::cancelled()],
        ];
    }

    #[DataProvider('providerBuildDocumentStatus')]
    public function testBuildDocumentStatus(string $argument, DocumentStatus $expected): void
    {
        $queryBuilder = $this->createQueryBuilder(['--estado' => $argument]);
        $documentStatus = $queryBuilder->buildDocumentStatus();
        $this->assertEquals($expected, $documentStatus);
    }

    public function testBuildDocumentStatusInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--estado' => 'foo']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildDocumentStatus();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'Si se especifica, la opción "estado" debe ser "vigentes" o "canceladas"',
            $catched->getMessage(),
        );
        $this->assertSame('estado', $catched->getArgumentName());
    }

    /** @return array<string, array{string, DocumentType}> */
    public static function providerBuildDocumentTypeValid(): array
    {
        return [
            '(empty)' => ['', DocumentType::undefined()],
            'ingreso' => ['INGRESO', DocumentType::ingreso()],
            'egreso' => ['EGRESO', DocumentType::egreso()],
            'traslado' => ['TRASLADO', DocumentType::traslado()],
            'pago' => ['PAGO', DocumentType::pago()],
            'nómina' => ['NÓMINA', DocumentType::nomina()],
            'nomina' => ['NOMINA', DocumentType::nomina()],
        ];
    }

    #[DataProvider('providerBuildDocumentTypeValid')]
    public function testBuildDocumentTypeValid(string $argument, DocumentType $expectedDocumentType): void
    {
        $queryBuilder = $this->createQueryBuilder(['--documento' => $argument]);
        $documentType = $queryBuilder->buildDocumentType();
        $this->assertEquals($expectedDocumentType, $documentType);
    }

    public function testBuildDocumentTypeInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--documento' => 'foo']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildDocumentType();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'Si se especifica la opción "documento" debe ser "ingreso", "egreso", "traslado", "pago" o "nómina"',
            $catched->getMessage(),
        );
        $this->assertSame('documento', $catched->getArgumentName());
    }

    public function testBuildUuidEmpty(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--uuid' => '']);
        $uuid = $queryBuilder->buildUuid();
        $this->assertTrue($uuid->isEmpty());
    }

    public function testBuildUuidValid(): void
    {
        $input = 'b84835d8-2c55-4194-88a1-79edd961e4e7';
        $queryBuilder = $this->createQueryBuilder(['--uuid' => $input]);
        $uuid = $queryBuilder->buildUuid();
        $this->assertEquals($input, $uuid->getValue());
    }

    public function testBuildUuidInvalid(): void
    {
        $input = 'invalid-uuid';
        $queryBuilder = $this->createQueryBuilder(['--uuid' => $input]);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildUuid();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'Si se especifica la opción "uuid" debe contener un UUID válido',
            $catched->getMessage(),
        );
        $this->assertSame('uuid', $catched->getArgumentName());
    }

    public function testBuildComplementEmpty(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--complemento' => '']);
        $complement = $queryBuilder->buildComplement();
        $this->assertTrue($complement->isUndefined());
    }

    public function testBuildComplementCfdiValid(): void
    {
        $input = 'nomina12';
        $queryBuilder = $this->createQueryBuilder(['--complemento' => $input]);
        $complement = $queryBuilder->buildComplement();
        $this->assertSame($input, $complement->value());
    }

    public function testBuildComplementRetencionesValid(): void
    {
        $input = 'dividendos';
        $queryBuilder = $this->createQueryBuilder(['--complemento' => $input], ServiceType::retenciones());
        $complement = $queryBuilder->buildComplement();
        $this->assertSame($input, $complement->value());
    }

    public function testBuildComplementInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--complemento' => 'invalid-complement']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildComplement();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'La opción "complemento" de Cfdi tiene un valor inválido',
            $catched->getMessage(),
        );
        $this->assertSame('complemento', $catched->getArgumentName());
    }

    public function testBuildRfcOnBehalfEmpty(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--tercero' => '']);
        $rfc = $queryBuilder->buildRfcOnBehalf();
        $this->assertTrue($rfc->isEmpty());
    }

    public function testBuildRfcOnBehalfValid(): void
    {
        $input = 'AAAA010101AAA';
        $queryBuilder = $this->createQueryBuilder(['--tercero' => $input]);
        $rfc = $queryBuilder->buildRfcOnBehalf();
        $this->assertSame($input, $rfc->getValue());
    }

    public function testBuildRfcOnBehalfInvalid(): void
    {
        $queryBuilder = $this->createQueryBuilder(['--tercero' => 'invalid-rfc']);
        $catched = $this->catch(function () use ($queryBuilder): void {
            $queryBuilder->buildRfcOnBehalf();
        });
        $this->assertInstanceOf(InputException::class, $catched);
        /** @var InputException $catched */
        $this->assertStringContainsString(
            'La opción "tercero" tiene un valor inválido',
            $catched->getMessage(),
        );
        $this->assertSame('tercero', $catched->getArgumentName());
    }
}
