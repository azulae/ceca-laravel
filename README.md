# Ceca Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Laravel 6/7/8/9/10 package to support payments and cancellations through the CECA online tpv.

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
CECA_MODO="desarrollo" 
```
NOTA: los parámetros de CECA_URL_OK y CECA_URL_KO en CECA están en desuso, las redirecciones se configuran en el panel de control del cliente, en https://comercios.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS

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

Comprobar la respuesta del servicio:

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
