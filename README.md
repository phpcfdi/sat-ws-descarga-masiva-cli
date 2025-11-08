# phpcfdi/sat-ws-descarga-masiva-cli

[![Source Code][badge-source]][source]
[![Packagist PHP Version Support][badge-php-version]][php-version]
[![Discord][badge-discord]][discord]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Reliability][badge-reliability]][reliability]
[![Maintainability][badge-maintainability]][maintainability]
[![Code Coverage][badge-coverage]][coverage]
[![Violations][badge-violations]][violations]
[![Total Downloads][badge-downloads]][downloads]
[![Docker Downloads][badge-docker]][docker]


> Consumo del web service de descarga masiva del SAT por línea de comandos

:us: The documentation of this project is in spanish as this is the natural language for intented audience.

:mexico: La documentación del proyecto está en español porque ese es el lenguaje principal de los usuarios.
También te esperamos en [el canal #phpcfdi de discord](https://discord.gg/aFGYXvX)

Esta librería contiene un cliente (consumidor) del servicio del SAT de
**Servicio Web de Descarga Masiva de CFDI y Retenciones**.

## Requerimientos

Esta herramienta usa **PHP versión 8.2** o superior con las extensiones `xml`, `openssl`, `zip`, `curl`, `intl` y `bcmath`.

## Instalación

### Ejecutable

Puedes descargar el archivo PHAR desde la dirección 
<https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/releases/latest/download/descarga-masiva.phar>.

```shell
wget https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/releases/latest/download/descarga-masiva.phar -O descarga-masiva.phar
php descarga-masiva.phar --version
```

### Phive

Pendiente.

### Docker

```shell
docker pull phpcfdi/descarga-masiva
docker run --rm -it phpcfdi/descarga-masiva --version
```

### Composer

Puedes instalar el proyecto en una carpeta especial y usar la herramienta o como dependencia de tu proyecto.
Personalmente, no recomiendo instalarla como una dependencia de algún proyecto, dado que se trata de
una herramienta y no de un componente o libería.

```shell
# instalar la herramienta
composer require phpcfdi/sat-ws-descarga-masiva-cli
# ejecutar el script
php vendor/bin/descarga-masiva.php --version
```

Suponiendo que la herramienta se instaló en `~/projects/sat-ws-descarga-masiva-cli`, entonces podrías poner después
un script de ejecución como el siguiente en `/usr/local/bin/descarga-masiva` o en `~/.local/bin/descarga-masiva`:

```bash
!#/usr/bin/env bash -e
php ~/projects/sat-ws-descarga-masiva-cli/vendor/bin/descarga-masiva.php "${@}"
```

### Instalación desde el repositorio Git

Puedes decargar el proyecto de github y ejecutar el archivo `bin/descarga-masiva.php`.
Esta opción la recomiendo aún menos, dado que no es fácil mantener la herramienta desde Git.

```shell
# descargar el proyecto
git clone https://github.com/phpcfdi/sat-ws-descarga-masiva-cli /opt/descarga-masiva
# instalar dependencias
composer --working-dir=/opt/descarga-masiva update --no-dev
# ejecución del proyecto
php /opt/descarga-masiva/bin/descarga-masiva.php --version
```

## Ejemplos de uso

Para entender plenamente el uso del servicio web y los códigos de respuesta consulta la documentación de la librería 
[`phpcfdi/sat-ws-descarga-masiva`](https://github.com/phpcfdi/sat-ws-descarga-masiva).

La aplicación cuenta con dos tipos de comandos: `ws` para trabajar con el servicio y `zip` para trabajar con los paquetes.

Para obtener la lista de comandos disponibles usa el comando `list`.

Para obtener ayuda de la aplicación o cualquier comando agrega el parámetro `--help`.

### Comando `ws:consulta`

El comando `ws:consulta` presenta una consulta con los parámetros establecidos.

El siguiente comando presenta una consulta de CFDI de metadata de comprobantes emitidos en el periodo
`2023-01-01 00:00:00` al `2023-12-31 23:59:59` con los datos de la FIEL del RFC `EKU9003173C9`.

```shell
php bin/descarga-masiva ws:consulta \
    --certificado fiel/EKU9003173C9.cer --llave fiel/EKU9003173C9.key --password=12345679a \
    --desde "2023-01-01 00:00:00" --hasta "2023-12-31 23:59:59"
```

Con lo que puede entregar el siguiente resultado:

```text
Consulta: 
  Servicio: cfdi
  Paquete: Metadata
  RFC: EKU9003173C9
  Desde: 2024-01-01T00:00:00.000UTC
  Hasta: 2024-12-31T23:59:59.000UTC
  Tipo: Emitidos
  RFC de/para: (cualquiera)
  Documentos: (cualquiera)
  Complemento: (cualquiera)
  Estado: (cualquiera)
  Tercero: (cualquiera)
Resultado: 
  Consulta: 5000 - Solicitud Aceptada
  Identificador de solicitud: ba31f7fa-3713-4395-8e1f-39a79f02f5cc
```

Los parámetros `--efirma`,  `--certificado`, `--llave`, `--password`, `--token` son de autenticación
y se documentan más adelante.

Adicionalmente, se pueden especificar los siguientes parámetros:

- `--servicio`: Si se consultarán los CFDI regulares (`cfdi`) o CFDI de Retención e información de pagos (`retenciones`).
  Por omisión: `cfdi`.
- `--tipo`: Si se consultarán los comprobantes emitidos (`emitidos`) o recibidos (`recibidos`). Por omisión: `emitidos`.
- `--paquete`: Si se solicita un paquete de Metadatos (`metadata`) o de XML (`xml`). Por omisión: `metadata`.

Y los siguientes filtros, que son opcionales:

- `--estado`: Filtra por el estado se encuentra el comprobante:
  Vigentes `vigentes` o canceladas `canceladas`.
- `--rfc`: Filtra la información por RFC, si se solicitan emitidos entonces es el RFC receptor, 
  si se solicitan recibidos entonces es el RFC emisor.
- `--documento`: Filtra por el tipo de documento:
  Ingreso (`ingreso`), egreso (`egreso`), traslado (`traslado`), pago (`pago`) o nómina (`nomina`).
- `--complemento`: Filtra por el tipo de complemento, ver el comando `info:complementos`.
- `--tercero`: Filtra por el RFC a cuenta de terceros.

También se pueden hacer consultas por UUID con el paámetro `--uuid`. En caso de usar el filtro de UUID entonces 
no se toman en cuenta los parámetros `--desde`, `--hasta` o cualquiera de los filtros antes mencionados.

En la respuesta, entrega el resultado de la operación y el identificador de la solicitud, 
que puede ser usado después en el comando `ws:verifica`.

### Comando `ws:verifica`

El comando `ws:verifica` verifica una consulta previamente presentada con los parámetros establecidos.

El siguiente comando verifica una consulta de CFDI con el identificador `ba31f7fa-3713-4395-8e1f-39a79f02f5cc`.

```shell
php bin/descarga-masiva ws:verifica \
    --certificado fiel/EKU9003173C9.cer --llave fiel/EKU9003173C9.key --password=12345679a \
    ba31f7fa-3713-4395-8e1f-39a79f02f5cc
```

En la respuesta, entrega el resultado de la operación y el identificador de uno o más paquetes para descarga,
que pueden ser usados después en el comando `ws:descarga`.

```text
Verificación: 
  RFC: EKU9003173C9
  Identificador de la solicitud: ba31f7fa-3713-4395-8e1f-39a79f02f5cc
Resultado: 
  Verificación: 5000 - Solicitud Aceptada
  Estado de la solicitud: 3 - Terminada
  Estado de la descarga: 5000 - Solicitud recibida con éxito
  Número de CFDI: 572
  Paquetes: BA31F7FA-3713-4395-8E1F-39A79F02F5CC_01
```

Los parámetros `--efirma`,  `--certificado`, `--llave`, `--password`, `--token` son de autenticación
y se documentan más adelante.

Adicionalmente, se pueden especificar los siguientes parámetros:

- `--servicio`: Si se verificará la consulta en el servicio web de CFDI regulares (`cfdi`) o 
  de CFDI de Retención e información de pagos (`retenciones`). Por omisión: `cfdi`.

### Comando `ws:descarga`

El comando `ws:descarga` descarga un paquete de una consulta previamente verificada.

El siguiente comando descarga un paquete de CFDI con el identificador `BA31F7FA-3713-4395-8E1F-39A79F02F5CC_01` 
en el directorio de destino `storage/paquetes`.

```shell
php bin/descarga-masiva ws:descarga \
    --certificado fiel/EKU9003173C9.cer --llave fiel/EKU9003173C9.key --password=12345679a \
    --destino storage/paquetes BA31F7FA-3713-4395-8E1F-39A79F02F5CC_01
```

En la respuesta, entrega el resultado de la operación y el identificador de uno o más paquetes para descarga,
que pueden ser usados después en el comando `ws:descarga`.

```text
Descarga:
  RFC: DIM8701081LA
  Identificador del paquete: BA31F7FA-3713-4395-8E1F-39A79F02F5CC_01
  Destino: storage/paquetes/ba31f7fa-3713-4395-8e1f-39a79f02f5cc_01.zip
Resultado:
  Descarga: 5000 - Solicitud Aceptada
  Tamaño: 216126
```

Los parámetros `--efirma`,  `--certificado`, `--llave`, `--password`, `--token` son de autenticación
y se documentan más adelante.

Adicionalmente, se pueden especificar los siguientes parámetros:

- `--servicio`: Si se descargará el paquete en el servicio web de CFDI regulares (`cfdi`) o 
  de CFDI de Retención e información de pagos (`retenciones`). Por omisión: `cfdi`.
- `--destino`: Si se establece, determina en qué carpeta se descargará el paquete, 
  en caso de no usarse se utiliza el directorio actual.

### Parámetros de autenticación

Los parámetros `--efirma`, `--certificado`, `--llave`, `--password` y `--token` son de autenticación 
y se utilizan en los comandos `ws:consulta`, `ws:descarga` y `ws:verifica`.

- `--efirma`: Ruta absoluta o relativa al archivo de especificación de eFirma.
- `--certificado`: Ruta absoluta o relativa al archivo de certificado.
- `--llave`: Ruta absoluta o relativa al archivo de llave privada.
- `--password`: Contraseña de la llave privada, si no se especifica entonces usa el valor de la 
  variable de entorno `EFIRMA_PASSPHRASE`.
- `--token`: Ruta absoluta o relativa a un archivo *Token* (que genera esta aplicación).

Es recomendado establecer la ruta del *token*, es en donde se almacena la autenticación con el servicio web
del SAT y se intenta reutilizar para no realizar más peticiones de autenticación de las necesarias.

#### Archivos de eFirma

Para no tener que especificar los parámetros `--certificado`, `--llave`, `--password` y `--token`, se puede
especificar el parámetro `--efirma` que espera la ubicación a un archivo JSON con la siguiente estructura:

- `certificateFile`: Ruta absoluta o relativa al archivo de certificado CER.
- `privateKeyFile`: Ruta absoluta o relativa al archivo de llave privada KEY.
- `passPhrase`: Contraseña de la llave privada.
- `tokenFile`: Ruta absoluta o relativa a un archivo *Token* (que genera esta aplicación).

### Comando `info:complementos`

El comando `info:complementos` muestra la información de los complementos registrados para usarse en una consulta.

Adicionalmente, se pueden especificar los siguientes parámetros:

- `--servicio`: Si se mostrarán los complementos del servicio web de CFDI regulares (`cfdi`) o
  de CFDI de Retención e información de pagos (`retenciones`). Por omisión: `cfdi`.

### Comando `zip:metadata`

El comando `zip:metadata` lee un paquete de metadatos desde `paquetes/ba31f7fa-3713-4395-8e1f-39a79f02f5cc_01.zip` 
y exporta su información a un archivo de Excel en `archivos/listado.xlsx`.

```shell
php bin/descarga-masiva zip:metadata paquetes/ba31f7fa-3713-4395-8e1f-39a79f02f5cc_01.zip archivos/listado.xlsx
```

### Comando `zip:xml`

El comando `zip:xml` lee un paquete de XML y exporta los comprobantes a un directorio, el nombre de cada 
comprobante es el UUID con la extensión `.xml`.

El siguiente comando lee un paquete de XML desde `paquetes/ba31f7fa-3713-4395-8e1f-39a79f02f5cc_01.zip` 
y exporta todos los archivos de comprobantes en el directorio `archivos/xml/`.

```shell
php bin/descarga-masiva zip:metadata paquetes/ba31f7fa-3713-4395-8e1f-39a79f02f5cc_01.zip archivos/xml/
```

## Compatibilidad

Esta librería se mantendrá compatible con al menos la versión con
[soporte activo de PHP](https://www.php.net/supported-versions.php) más reciente.

También utilizamos [Versionado Semántico 2.0.0](https://semver.org/lang/es/)
por lo que puedes usar esta librería sin temor a romper tu aplicación.

## Contribuciones

Las contribuciones con bienvenidas. Por favor lee [CONTRIBUTING][] para más detalles
y recuerda revisar el archivo de tareas pendientes [TODO][] y el archivo [CHANGELOG][].

## Copyright and License

The `phpcfdi/sat-ws-descarga-masiva-cli` project is copyright © [PhpCfdi](https://www.phpcfdi.com)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/blob/main/docs/TODO.md

[source]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli
[php-version]: https://packagist.org/packages/phpcfdi/sat-ws-descarga-masiva-cli
[discord]: https://discord.gg/aFGYXvX
[release]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/releases
[license]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/blob/main/LICENSE
[build]: https://github.com/phpcfdi/sat-ws-descarga-masiva-cli/actions/workflows/build.yml?query=branch:main
[reliability]:https://sonarcloud.io/component_measures?id=phpcfdi_sat-ws-descarga-masiva-cli&metric=Reliability
[maintainability]: https://sonarcloud.io/component_measures?id=phpcfdi_sat-ws-descarga-masiva-cli&metric=Maintainability
[coverage]: https://sonarcloud.io/component_measures?id=phpcfdi_sat-ws-descarga-masiva-cli&metric=Coverage
[violations]: https://sonarcloud.io/project/issues?id=phpcfdi_sat-ws-descarga-masiva-cli&resolved=false
[downloads]: https://packagist.org/packages/phpcfdi/sat-ws-descarga-masiva-cli
[docker]: https://hub.docker.com/r/phpcfdi/descarga-masiva

[badge-source]: https://img.shields.io/badge/source-phpcfdi/sat--ws--descarga--masiva--cli-blue?logo=github
[badge-discord]: https://img.shields.io/discord/459860554090283019?logo=discord
[badge-php-version]: https://img.shields.io/packagist/php-v/phpcfdi/sat-ws-descarga-masiva-cli?logo=php
[badge-release]: https://img.shields.io/github/release/phpcfdi/sat-ws-descarga-masiva-cli?logo=git
[badge-license]: https://img.shields.io/github/license/phpcfdi/sat-ws-descarga-masiva-cli?logo=open-source-initiative
[badge-build]: https://img.shields.io/github/actions/workflow/status/phpcfdi/sat-ws-descarga-masiva-cli/build.yml?branch=main&logo=github-actions
[badge-reliability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_sat-ws-descarga-masiva-cli&metric=reliability_rating
[badge-maintainability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_sat-ws-descarga-masiva-cli&metric=sqale_rating
[badge-coverage]: https://img.shields.io/sonar/coverage/phpcfdi_sat-ws-descarga-masiva-cli/main?logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-violations]: https://img.shields.io/sonar/violations/phpcfdi_sat-ws-descarga-masiva-cli/main?format=long&logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-downloads]: https://img.shields.io/packagist/dt/phpcfdi/sat-ws-descarga-masiva-cli?logo=packagist
[badge-docker]: https://img.shields.io/docker/pulls/phpcfdi/descarga-masiva?logo=docker
