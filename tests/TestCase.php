<?php

namespace Azulae\Ceca\Tests;

use Azulae\Ceca\CecaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [CecaServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        // Carga la configuración del paquete como si estuviera publicada.
        $app['config']->set('ceca', require __DIR__ . '/../src/config/config.php');
    }
}
