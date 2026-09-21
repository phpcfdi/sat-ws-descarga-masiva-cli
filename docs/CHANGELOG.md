CHANGELOG

## Acerca de los números de versiones

Respetamos el estándar [Versionado Semántico 2.0.0](https://semver.org/lang/es/).

En resumen, [SemVer](https://semver.org/) es un sistema de versiones de tres componentes `X.Y.Z`
que nombraremos así: ` Breaking . Feature . Fix `, donde:

- `Breaking`: Rompe la compatibilidad de código con versiones anteriores.
- `Feature`: Agrega una nueva característica que es compatible con lo anterior.
- `Fix`: Incluye algún cambio (generalmente correcciones) que no agregan nueva funcionalidad.

**Importante:** Las reglas de SEMVER no aplican si estás usando una rama (por ejemplo `main-dev`)
o estás usando una versión cero (por ejemplo `0.18.4`).

## Versión 0.0.6 2026-09-20

- Se actualiza el archivo de licencia a 2026.
- Se actualiza el código para asegurar la compatibilidad con PHP 8.5.
- Se elimina la compatibilidad con versiones menores a PHP 8.4.
- Se modifican las librerías del proyecto:
    - `phpcfdi/sat-ws-descarga-masiva` versión `1.1.4`.
    - `guzzlehttp/guzzle` versión `8.2.0`.
    - `symfony/console` versión `8.1.7`.
    - `azjezz/psl` se sustituye por `php-standard-library/php-standard-library` versión `6.2`.
    - `phpunit/phpunit` versión `11.3.4` (desarrollo).
    - `symfony/process` versión `8.1.7` se agrega (desarrollo).
- Debido a la actualización de `symfony/console` se actualiza la construcción de la aplicación y los comandos.
- En los flujos de trabajo de GitHub:
    - Se actualizan las versiones de las acciones.
    - Se ejecutan los trabajos en PHP 8.5.
    - Si se está ejecutando el trabajo en `nectos/act` se instala `phpseclib/bcmath_compat`
      debido a que la extensión `bcmath` falla al compilar en `setupphp/node`.
- Se crea una prueba para ejecutar `bin/descarga-masiva.php`.
- Se actualiza la imagen de Docker para correr en PHP 8.5.
- Se actualiza eliminan las dependencias de compilación de extensiones en la imagen de Docker.
- Se actualiza el estilo de código con reglas mínimas de PHP 8.4.
- Se actualizan las herramientas de desarrollo.

## Versión 0.0.5 2025-11-07

- Se actualiza el código para asegurar la compatibilidad con PHP 8.4.
  También se elimina la restricción de compatibilidad impuesta en `composer.json`.
- Se actualizan las librerías del proyecto:
  - `phpcfdi/sat-ws-descarga-masiva` versión `1.1.2`.
  - `eclipxe/enum` versión `0.2.7`.
  - `eclipxe/xlsxexporter` versión `2.0.1`.
  - `azjezz/psl` versión `3.3` o `4.2`.
- Se actualiza el año de la licencia a 2025.
- Se actualiza la compatibilidad con SonarQube Cloud (y las insignias).
- Se actualiza el estándar de código.
- Se elimina el archivo de configuración de `rector/rector`.
- En los flujos de trabajo:
  - Se corrige el trabajo de `composer-normalize` pues no fallaba al encontrar un error.
  - Se agrega PHP 8.4 a la matriz de pruebas.
  - Se actualizan los trabajos en PHP 8.4.
- Se actualizan las herramientas de desarrollo.

## Versión 0.0.4 2025-06-02

- Las consultas se prevalidan por defecto.
- Se puede omitir la prevalidación con la opción `--no-prevalidar`. 
- Se actualiza a `phpcfdi/sat-ws-descarga-masiva` versión `1.1.1`. 

## Versión 0.0.3 2025-05-31

- Se actualiza a `phpcfdi/sat-es-descarga-masiva` versión `1.1.0` para funcionar con el 
  webservice de descarga masiva del SAT versión 1.5.
- Se utilizan las excepciones de la librería en lugar de excepciones genpericas.
- Se actualizan las herramientas de desarrollo.

## Versión 0.0.2 2024-10-21

- Se agrega la información del archivo `bin/descarga-masiva.php` a la sección de "binarios" de *Composer*.

## Versión 0.0.1 2024-10-18

- Primera versión pública.

## Versión 0.0.0 2020-01-01

- Trabajo inicial para hacer pruebas con amigos.
