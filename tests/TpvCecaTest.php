<?php

namespace Azulae\Ceca\Tests;

use Azulae\Ceca\TpvCeca;
use Exception;

class TpvCecaTest extends TestCase
{
    /**
     * Clave de prueba: Base64 de 24 bytes (32 caracteres), equivalente a la
     * "Nueva clave de encripción" del Portal de Administración de Cecabank.
     */
    protected function claveHmac(): string
    {
        return base64_encode('012345678901234567890123'); // 24 bytes -> 32 caracteres Base64
    }

    protected function tpvCompleto(string $cifrado = null, string $clave = 'clave-de-pruebas'): TpvCeca
    {
        $tpv = new TpvCeca();
        $tpv->setMerchantID('086401247');
        $tpv->setAcquirerBIN('0000554027');
        $tpv->setTerminalID('00000003');
        $tpv->setNumOperacion('100000003752');
        $tpv->setImporte('10,67');
        $tpv->setUrlOk('https://comercio.test/ok');
        $tpv->setUrlNok('https://comercio.test/nok');
        $tpv->setClaveEncriptacion($clave);

        if ($cifrado !== null) {
            $tpv->setCifrado($cifrado);
        }

        return $tpv;
    }

    /**
     * Implementación de referencia del algoritmo oficial de Cecabank
     * (plugins oficiales PHP, org GitHub Cecabank): 3DES-CBC del número de
     * operación con IV de ceros sobre la clave Base64, y HMAC-SHA256 en Base64.
     */
    protected function firmaHmacReferencia(string $cadena, string $claveBase64, string $numOperacion): string
    {
        $key3des = base64_decode($claveBase64);

        $padded = $numOperacion;
        $remainder = strlen($padded) % 8;
        if ($remainder !== 0) {
            $padded .= str_repeat("\0", 8 - $remainder);
        }

        $hmacKey = openssl_encrypt(
            $padded,
            'des-ede3-cbc',
            $key3des,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            str_repeat("\0", 8)
        );

        return base64_encode(hash_hmac('sha256', $cadena, $hmacKey, true));
    }

    public function test_el_importe_se_convierte_a_centimos_sin_errores_de_redondeo()
    {
        $casos = [
            '10,67'    => 1067, // regresión: intval(10.67*100) == 1066
            '10.67'    => 1067,
            '1.234,56' => 123456,
            '10'       => 1000,
            '10.6'     => 1060,
        ];

        foreach ($casos as $entrada => $esperado) {
            $tpv = new TpvCeca();
            $tpv->setImporte($entrada);
            $this->assertSame($esperado, $this->valorPrivado($tpv, '_importe'), "Importe '$entrada'");
        }
    }

    public function test_la_firma_sha2_se_mantiene_retrocompatible()
    {
        $tpv = $this->tpvCompleto('SHA2', 'miclave');
        $params = $tpv->getParametersArray();

        $cadenaEsperada = 'miclave08640124700005540270000000310000000375210679782'
            . 'SHA2https://comercio.test/okhttps://comercio.test/nok';

        $this->assertSame('SHA2', $params['Cifrado']);
        $this->assertSame($cadenaEsperada, $this->valorPrivado($tpv, '_cadena'));
        $this->assertSame(strtolower(hash('sha256', $cadenaEsperada)), $params['Firma']);
    }

    public function test_la_firma_hmac_coincide_con_el_algoritmo_oficial_de_cecabank()
    {
        $clave = $this->claveHmac();
        $tpv = $this->tpvCompleto('HMAC', $clave);
        $params = $tpv->getParametersArray();

        $cadena = $clave . '08640124700005540270000000310000000375210679782'
            . 'HMAChttps://comercio.test/okhttps://comercio.test/nok';

        $this->assertSame('HMAC', $params['Cifrado']);
        $this->assertSame(
            $this->firmaHmacReferencia($cadena, $clave, '100000003752'),
            $params['Firma']
        );
    }

    public function test_la_firma_hmac_exige_la_nueva_clave_base64_de_24_bytes()
    {
        $this->expectException(Exception::class);

        $tpv = $this->tpvCompleto('HMAC', 'clave-corta-no-valida');
        $tpv->getParametersArray();
    }

    public function test_la_anulacion_mantiene_el_cifrado_configurado_e_incluye_referencia()
    {
        $clave = $this->claveHmac();
        $tpv = $this->tpvCompleto('HMAC', $clave);
        $tpv->setModo('Anulacion');
        $tpv->setReferencia('12005238811803201324076007000');

        $params = $tpv->getParametersArray();

        // Antes se forzaba SHA1 en anulaciones; ahora se respeta el cifrado configurado.
        $this->assertSame('HMAC', $params['Cifrado']);
        $this->assertSame('12005238811803201324076007000', $params['Referencia']);
        $this->assertSame('anularOperacionExt', $params['modo']);

        $cadena = $clave . '0864012470000554027000000031000000037521067978'
            . '212005238811803201324076007000HMAC';

        $this->assertSame(
            $this->firmaHmacReferencia($cadena, $clave, '100000003752'),
            $params['Firma']
        );
    }

    public function test_las_urls_por_defecto_son_las_del_tpv_web_actual()
    {
        $tpv = new TpvCeca();
        $tpv->setMerchantID('086401247');
        $tpv->setAcquirerBIN('0000554027');
        $tpv->setTerminalID('00000003');
        $tpv->setNumOperacion('100000003752');
        $tpv->setImporte('10,67');
        $tpv->setUrlOk('https://comercio.test/ok');
        $tpv->setUrlNok('https://comercio.test/nok');
        $tpv->setClaveEncriptacion('miclave');

        // Por defecto, desarrollo
        $this->assertSame(
            'https://tpv.ceca.es/tpvweb/tpv/compra.action',
            $tpv->getParametersArray()['UrlPasarela']
        );

        $tpv->setEntorno('produccion');
        $this->assertSame(
            'https://pgw.ceca.es/tpvweb/tpv/compra.action',
            $tpv->getParametersArray()['UrlPasarela']
        );
    }

    public function test_la_configuracion_del_paquete_permite_sobrescribir_las_urls()
    {
        $this->app['config']->set('ceca.url_pasarela_produccion', 'https://pasarela.custom/tpv');

        $tpv = $this->tpvCompleto('HMAC', $this->claveHmac());
        $tpv->setEntorno('produccion');

        $this->assertSame('https://pasarela.custom/tpv', $tpv->getParametersArray()['UrlPasarela']);
    }

    public function test_el_cifrado_se_puede_configurar_por_entorno()
    {
        $this->app['config']->set('ceca.cifrado', 'HMAC');

        $tpv = $this->tpvCompleto();
        $tpv->setClaveEncriptacion($this->claveHmac());

        $this->assertSame('HMAC', $tpv->getParametersArray()['Cifrado']);
    }

    public function test_la_url_de_anulacion_usa_el_entorno_correcto()
    {
        $tpv = $this->tpvCompleto();
        $tpv->setModo('Anulacion');
        $tpv->setReferencia('12005238811803201324076007000');
        $tpv->setEntorno('produccion');

        $url = $tpv->getCancellationUrl();

        $this->assertStringStartsWith(
            'https://comercios.ceca.es/webapp/ConsTpvVirtWeb/ConsTpvVirtS?modo=anularOperacionExt&',
            $url
        );
        $this->assertStringContainsString('&Referencia=12005238811803201324076007000', $url);
        $this->assertStringContainsString('&Firma=', $url);
    }

    public function test_comprueba_la_firma_de_la_notificacion_con_hmac()
    {
        $clave = $this->claveHmac();
        $tpv = $this->tpvCompleto('HMAC', $clave);

        $post = [
            'MerchantID'    => '086401247',
            'AcquirerBIN'   => '0000554027',
            'TerminalID'    => '00000003',
            'Num_operacion' => '100000003752',
            'Importe'       => '1067',
            'TipoMoneda'    => '978',
            'Exponente'     => '2',
            'Referencia'    => '12005238811803201324076007000',
        ];

        // En la notificación la cadena NO incluye el campo Cifrado.
        $cadena = $clave . '0864012470000554027000000031000000037521067978212005238811803201324076007000';
        $post['Firma'] = $this->firmaHmacReferencia($cadena, $clave, '100000003752');

        $this->assertTrue($tpv->checkNotificationSignature($post));

        // Un importe manipulado invalida la firma.
        $post['Importe'] = '9999';
        $this->assertFalse($tpv->checkNotificationSignature($post));
    }

    public function test_comprueba_la_firma_de_la_notificacion_con_sha2()
    {
        $tpv = $this->tpvCompleto('SHA2', 'miclave');

        $post = [
            'MerchantID'    => '086401247',
            'AcquirerBIN'   => '0000554027',
            'TerminalID'    => '00000003',
            'Num_operacion' => '100000003752',
            'Importe'       => '1067',
            'TipoMoneda'    => '978',
            'Exponente'     => '2',
            'Referencia'    => '12005238811803201324076007000',
        ];

        $cadena = 'miclave0864012470000554027000000031000000037521067978212005238811803201324076007000';
        $post['Firma'] = strtolower(hash('sha256', $cadena));

        $this->assertTrue($tpv->checkNotificationSignature($post));
    }

    public function test_el_formulario_escapa_los_valores_enviados()
    {
        $tpv = new TpvCeca();
        $tpv->setMerchantID('086401247');
        $tpv->setAcquirerBIN('0000554027');
        $tpv->setTerminalID('00000003');
        $tpv->setNumOperacion('100000003752');
        $tpv->setImporte('10,67');
        $tpv->setUrlOk('https://comercio.test/ok?a=1&b=2');
        $tpv->setUrlNok('https://comercio.test/nok');
        $tpv->setClaveEncriptacion('miclave');

        $form = $tpv->create_form();

        $this->assertStringContainsString('value="https://comercio.test/ok?a=1&amp;b=2"', $form);
        $this->assertStringContainsString('name="ceca_form"', $form);
        $this->assertStringContainsString('name="Cifrado" value="SHA2"', $form);
        $this->assertStringNotContainsString('&b=2"', $form); // sin escapar no debe aparecer
    }

    public function test_setsubmit_inicializa_el_boton_correctamente()
    {
        $tpv = $this->tpvCompleto();
        $tpv->setSubmit('pagar', 'Pagar ahora');

        $form = $tpv->create_form();

        $this->assertStringContainsString('type="submit" name="pagar" id="pagar" value="Pagar ahora"', $form);
    }

    private function valorPrivado(TpvCeca $tpv, string $propiedad)
    {
        $ref = new \ReflectionProperty(TpvCeca::class, $propiedad);
        $ref->setAccessible(true);

        return $ref->getValue($tpv);
    }
}
