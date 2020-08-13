<?php

namespace Yjtec\Linque\Config;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Conf
 *
 * @author Administrator
 */
class Conf {

    public static $Config;

    public static function setConfig($config) {
        if ($config) {
            if (self::$Config) {
                self::$Config = array_merge(self::$Config, $config);
            } else {
                self::$Config = $config;
            }
        }
        return self::$Config;
    }

    public static function getConfig() {
        return array(
            'DBTYPE' => 'Redis',
            'HOST' => isset(self::$Config['HOST']) && self::$Config['HOST'] ? self::$Config['HOST'] : '127.0.0.1',
            'PORT' => isset(self::$Config['PORT']) && self::$Config['PORT'] ? self::$Config['PORT'] : 6379,
            'PWD' => isset(self::$Config['PWD']) && self::$Config['PWD'] ? self::$Config['PWD'] : '',
            'DBNAME' => isset(self::$Config['DBNAME']) && self::$Config['DBNAME'] ? self::$Config['DBNAME'] : '3',
        );
    }

    public static function getSystemPlatform() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'win';
        } else {
            return 'linux';
        }
    }

}
