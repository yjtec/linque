<?php

namespace Yjtec\Linque\App;

use Yjtec\Linque\Lib\AppInterface;

/**
 * Description of UserApp
 *
 * @author Administrator
 */
class Monitor implements AppInterface
{

    public function __construct()
    {
        echo 'Monitor' . PHP_EOL;
    }

    public function before()
    {
        echo 'Monitor before' . PHP_EOL;
    }

    public function run()
    {
        echo 'Monitor run' . PHP_EOL;
    }

    public function after()
    {
        echo 'Monitor after' . PHP_EOL;
    }
}
