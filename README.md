# Ceca Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Laravel 6 → 13 package to support payments and cancellations through the CECA (Cecabank) online tpv, con soporte de firma **SHA2** y **HMAC**.

## Install

Via Composer

``` bash
composer require azulae/ceca-laravel
```


Ahora debemos cargar nuestro Services Provider dentro del array **'providers'** (config/app.php)
>Si usas Laravel 5.5 o superior, no necesitas cargar el services provider
```php
Azulae\Ceca\CecaServiceProvider::class
```

Creamos un alias dentro del array **'aliases'** (config/app.php)
>Si usas Laravel 5.5 o superior no necesitas crear el alias
```php
'Ceca'    => Azulae\Ceca\Facades\Ceca::class,
```

Y finalmente publicamos nuestro archivo de configuración
```bash
php artisan vendor:publish --provider="Azulae\Ceca\CecaServiceProvider"
```
>Esto nos creará un archivo llamado *ceca.php* dentro de config, que tomará los valores de configuración de nuestro archivo .env:
```php
CECA_URL_PASARELA_PRODUCCION="https://pgw.ceca.es/tpvweb/tpv/compra.action"
CECA_URL_PASARELA_DESARROLLO="https://tpv.ceca.es/tpvweb/tpv/compra.action"
CECA_URL_OK=""
CECA_URL_KO=""
CECA_CODIGO_COMERCIO="XXXXXXXXX"
CECA_ACQUIRER_BIN="XXXXXXXXXX"
CECA_NOMBRE_COMERCIO="COMMERCE_NAME"
CECA_TERMINAL="00000003"
CECA_TERMINAL_DESARROLLO="00000003"
CECA_CLAVE_ENCRIPTACION="XXXXXXXX"
CECA_CLAVE_ENCRIPTACION_DESARROLLO="XXXXXXXX"
CECA_CIFRADO="HMAC"
CECA_MODO="desarrollo" 
```
NOTA: los parámetros de CECA_URL_OK y CECA_URL_KO en CECA están en desuso, las redirecciones se configuran en el panel de control del cliente, en https://comercios.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS

### Firma HMAC (obligatoria por Cecabank antes del 31/12/2026)

Según el [Boletín de Seguridad de Cecabank](https://comercios.ceca.es/), el TPV Virtual exige dos actualizaciones de seguridad:

1. **Ampliación de clave a 32 caracteres** (límite 30/09/2026): nueva clave en el Portal de Administración, campo *"Nueva clave de encripción"*.
2. **Nuevo sistema de firma HMAC** (límite 31/12/2026): sustituye a la firma SHA2.

Para usar HMAC basta con:
- Configurar la nueva clave de 32 caracteres (Base64, 24 bytes) en `CECA_CLAVE_ENCRIPTACION`.
- Añadir `CECA_CIFRADO="HMAC"` al `.env` (o llamar a `Ceca::setCifrado('HMAC')`).

La firma SHA2 sigue disponible (valor por defecto) por compatibilidad, pero **dejará de funcionar en Cecabank el 31/12/2026**.

## Usage

Lanzar la petición de pago:

``` php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Azulae\Ceca\Facades\Ceca;

class CecaController extends Controller
{
    //
    public function index()
    {
        try{            
            
            if(config('ceca.modo') == "desarrollo")
            {
                $CecaNumeroTerminal = config('ceca.terminal_desarrollo');
                $CecaClaveEncriptacion = config('ceca.clave_encriptacion_desarrollo');
            }
            else
            {
                $CecaNumeroTerminal = config('ceca.terminal');
                $CecaClaveEncriptacion = config('ceca.clave_encriptacion');
            }

            Ceca::setMerchantID(config('ceca.codigo_comercio'));
            Ceca::setEntorno(config('ceca.modo'));
            Ceca::setClaveEncriptacion($CecaClaveEncriptacion);
            Ceca::setCifrado(config('ceca.cifrado', 'SHA2'));
            Ceca::setTerminalID($CecaNumeroTerminal)
            Ceca::setAcquirerBIN(config('ceca.acquirer_bin'));
            Ceca::setUrlOk(config('ceca.url_ok'));
            Ceca::setUrlNok(config('ceca.url_ko'));
            Ceca::setNumOperacion($saleId);
            Ceca::setImporte(number_format($total,2));
            Ceca::launchRedirection(); 
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
}

```

Comprobar la firma de la notificación del pago (recomendado, compatible con SHA2 y HMAC):

``` php
<?php

    // Debe configurarse la misma clave y cifrado que en el envío
    Ceca::setClaveEncriptacion($CecaClaveEncriptacion);
    Ceca::setCifrado(config('ceca.cifrado', 'SHA2'));

    // true si la Firma recibida es válida (comparación timing-safe)
    if(Ceca::checkNotificationSignature($request->all()))
    {
        // Pago correcto
        .
        .
        .
    }

?>
```

Alternativamente, la firma puede comprobarse manualmente (ejemplo con SHA2):

``` php
<?php

    $input = $request->all();

    if(config('ceca.modo') == "desarrollo")
    {
        $CecaNumeroTerminal = config('ceca.terminal_desarrollo');
        $CecaClaveEncriptacion = config('ceca.clave_encriptacion_desarrollo');
    }
    else
    {
        $CecaNumeroTerminal = config('ceca.terminal');
        $CecaClaveEncriptacion = config('ceca.clave_encriptacion');
    }                        

    // Firma = sha256( Clave_encriptacion+MerchantID+AcquirerBIN+TerminalID+Num_operacion+Importe+TipoMoneda+Exponente+Referencia )
    $preFirma = $CecaClaveEncriptacion.config('ceca.codigo_comercio').config('ceca.acquirer_bin').$CecaNumeroTerminal.
                $input["Num_operacion"].$input["Importe"].$input["TipoMoneda"].$input["Exponente"].$input["Referencia"];

    $firma = hash('sha256', $preFirma);

    $respuestaFirma = $input["Firma"];

    // Si la firma calculada coincide con la recibida se procede a finalizar el pedido
    if($respuestaFirma == $firma)
    {
        // Pago correcto
        .
        .
        .
    }

?>
```


## Compatibilidad con versiones de Laravel

El paquete mantiene **una única base de código** compatible con Laravel 6 a 13: solo utiliza APIs estables (`ServiceProvider`, `publishes`, `config_path`, `Facade`, `bind`), por lo que no son necesarias ramas ni versiones de código distintas por versión de Laravel (el mismo enfoque de paquetes como los de Spatie). La compatibilidad se declara en `composer.json`:

```json
"laravel/framework": "^6.0|^7.0|^8.0|^9.0|^10.0|^11.0|^12.0|^13.0"
```

y se verifica en cada `push` con una **matriz de tests PHP × Laravel** en GitHub Actions (`.github/workflows/run-tests.yml`), usando `orchestra/testbench`. Solo si un paquete dependiera de APIs que cambian entre majors sería necesario publicar ramas/etiquetas distintas por versión.


## Testing

``` bash
composer install
vendor/bin/phpunit
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


## Security

If you discover any security related issues, please email :author_email instead of using the issue tracker.

## Credits

- [Juan Antonio][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/azulae/ceca-laravel.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/azulae/ceca-laravel.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/azulae/ceca-laravel
[link-downloads]: https://packagist.org/packages/azulae/ceca-laravel
[link-author]: https://github.com/azulae
[link-contributors]: ../../contributors
