<?php

namespace Azulae\Ceca\Tests;

use Azulae\Ceca\CecaServiceProvider;
use Azulae\Ceca\Facades\Ceca;
use Azulae\Ceca\TpvCeca;

class ServiceProviderTest extends TestCase
{
    public function test_el_contenedor_resuelve_tpvceca()
    {
        $this->assertInstanceOf(TpvCeca::class, $this->app->make('tpvceca'));
    }

    public function test_cada_resolucion_devuelve_una_instancia_nueva()
    {
        $this->assertNotSame($this->app->make('tpvceca'), $this->app->make('tpvceca'));
    }

    public function test_el_facade_resuelve_y_firma()
    {
        Ceca::setMerchantID('086401247');
        Ceca::setAcquirerBIN('0000554027');
        Ceca::setTerminalID('00000003');
        Ceca::setNumOperacion('100000003752');
        Ceca::setImporte('10,67');
        Ceca::setUrlOk('https://comercio.test/ok');
        Ceca::setUrlNok('https://comercio.test/nok');
        Ceca::setClaveEncriptacion('miclave');

        $params = Ceca::getParametersArray();

        $this->assertSame('086401247', $params['MerchantID']);
        $this->assertSame('1067', (string) $params['Importe']);
        $this->assertNotEmpty($params['Firma']);
    }

    public function test_la_configuracion_se_puede_publicar()
    {
        $this->artisan('vendor:publish', [
            '--provider' => CecaServiceProvider::class,
            '--force'    => true,
        ])->assertExitCode(0);

        $this->assertFileExists(config_path('ceca.php'));
    }
}
