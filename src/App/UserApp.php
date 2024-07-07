<?php

namespace Yjtec\Linque\App;

use Yjtec\Linque\Lib\AppInterface;

/**
 * Description of UserApp
 *
 * @author Administrator
 */
class UserApp implements AppInterface {

    private $job;

    public function __construct($job) {
        $this->job = $job;
        var_dump($this->job);
    }

    public function before() {
        echo 'que before' . PHP_EOL;
    }

    public function run() {
        echo 'que run' . PHP_EOL;
    }

    public function after() {
        echo 'que after' . PHP_EOL;
    }

}
