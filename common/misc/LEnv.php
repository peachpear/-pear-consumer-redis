<?php
namespace common\misc;

/**
 * 环境变量工具
 * Class LEnv
 * @package common\misc
 */
class LEnv
{
    /**
     * @var array
     */
    private static $env = [];

    /**
     * @param $file_dir
     */
    public static function init($file_dir)
    {
        if (file_exists($file_dir)) {
            $config = parse_ini_file($file_dir,true);

            foreach ($config as $one_key => $one_value) {
                foreach ($one_value as $two_key => $two_value) {
                    static::$env[$one_key .'.' .$two_key] = $two_value;
                }
            }
        }
    }

    /**
     * @param $name
     * @param null $value
     */
    public static function put($name = null, $value = null)
    {
        if ($name === null) {
            static::$env = [];
        } else {
            if ($value === null) {
                if (isset(static::$env[$name])) {
                    unset(static::$env[$name]);
                }
            }

            static::$env[$name] = $value;
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public static function get($name = null, $default = null)
    {
        if ($name === null) {
            return static::$env;
        }

        if (isset(static::$env[$name])) {
            return static::$env[$name];
        }

        return $default;
    }
}