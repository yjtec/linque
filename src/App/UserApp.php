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
        echo 'before' . PHP_EOL;
    }

    public function run() {
        echo 'run' . PHP_EOL;
    }

    public function after() {
        echo 'after' . PHP_EOL;
    }

}
