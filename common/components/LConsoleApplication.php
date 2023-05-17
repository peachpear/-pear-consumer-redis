<?php
namespace common\components;

use Yii;
use yii\console\Application;

/**
 * 命令行应用
 * Class LConsoleApplication
 * @package common\components
 */
class LConsoleApplication extends Application
{
    /**
     * 应用构建
     * 配置从服务器重载
     * LConsoleApplication constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ini_set("display_errors", true);
        $this->initAliases($config);

        parent::__construct($config);
    }

    /**
     * 初始化配置别名
     * @param $config
     */
    public function initAliases(&$config)
    {
        if (isset($config['aliases'])) {
            foreach ($config['aliases'] as $key=>$value)
            {
                Yii::setAlias($key, $value);
            }
            unset($config['aliases']);
        }
        $this->enableCoreCommands = false;
    }
}