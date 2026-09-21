<?php

declare(strict_types=1);

namespace PhpCfdi\SatWsDescargaMasiva\CLI\Tests\Unit\Service;

use PhpCfdi\SatWsDescargaMasiva\CLI\Service\ServiceWithStorageToken;
use PhpCfdi\SatWsDescargaMasiva\CLI\Service\StorageToken;
use PhpCfdi\SatWsDescargaMasiva\CLI\Tests\TestCase;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\RequestBuilderInterface;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceEndpoints;
use PhpCfdi\SatWsDescargaMasiva\Shared\Token;
use PhpCfdi\SatWsDescargaMasiva\WebClient\WebClientInterface;

final class ServiceWithStorageTokenTest extends TestCase
{
    public function testServiceTokenIsSetUpWithStorageToken(): void
    {
        $storageToken = new StorageToken('');
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');
        $storageToken->store($token);

        $requestBuilder = $this->createStub(RequestBuilderInterface::class);
        $webClient = $this->createStub(WebClientInterface::class);
        $serviceEndpoints = ServiceEndpoints::cfdi();
        $service = new ServiceWithStorageToken($requestBuilder, $webClient, $storageToken, $serviceEndpoints);

        $this->assertSame($token, $service->getToken());
    }

    public function testObtainCurrentTokenWhenTokensAreEqual(): void
    {
        $storageToken = new StorageToken('');
        $token = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token');
        $storageToken->store($token);

        $requestBuilder = $this->createStub(RequestBuilderInterface::class);
        $webClient = $this->createStub(WebClientInterface::class);
        $serviceEndpoints = ServiceEndpoints::cfdi();
        $service = new ServiceWithStorageToken($requestBuilder, $webClient, $storageToken, $serviceEndpoints);

        $this->assertSame($token, $service->obtainCurrentToken());
    }

    public function testObtainCurrentTokenWhenTokensAreNotEqual(): void
    {
        $currentToken = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token-current');
        $storedToken = new Token(DateTime::create(time() - 10), DateTime::create(time()), 'x-token-stored');
        $storageToken = new StorageToken('');
        $storageToken->store($storedToken);

        $requestBuilder = $this->createStub(RequestBuilderInterface::class);
        $webClient = $this->createStub(WebClientInterface::class);
        $serviceEndpoints = ServiceEndpoints::cfdi();
        $service = new ServiceWithStorageToken($requestBuilder, $webClient, $storageToken, $serviceEndpoints);
        $this->assertEquals($storedToken, $service->obtainCurrentToken());
        $service->setToken($currentToken);

        $this->assertEquals($currentToken, $service->obtainCurrentToken());
        $this->assertEquals($currentToken, $storageToken->current());
    }
}
