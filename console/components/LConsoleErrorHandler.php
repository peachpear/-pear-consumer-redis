<?php
namespace console\components;

use common\components\LException;
use Yii;
use yii\console\ErrorHandler;

/**
 * cli命令行错误处理
 * Class LConsoleErrorHandler
 * @package console\components
 */
class LConsoleErrorHandler extends ErrorHandler
{
    /**
     * 处理异常
     * 覆盖父类定义 yii\base\ErrorHandler->handleException()
     * set_exception_handler([$this, 'handleException']);
     * @param \Exception $exception
     * @throws \Exception
     */
    public function handleException($exception)
    {
        // 日志记录错误异常
        $this->logException($exception);

        // 渲染输出错误异常
        $this->renderException($exception);
    }

    /**
     * 处理错误
     * 覆盖父类定义 yii\base\ErrorHandler->handleError()
     * set_error_handler([$this, 'handleError']);
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool|void
     * @throws \Exception
     */
    public function handleError($code, $message, $file, $line)
    {
        $exception =  new \ErrorException($message, $code, 1, $file, $line);
        $this->handleException($exception);
    }

    /**
     * 处理致命错误
     * 覆盖父类定义 yii\base\ErrorHandler->handleFatalError()
     * register_shutdown_function([$this, 'handleFatalError']);
     * @throws \Exception
     */
    public function handleFatalError()
    {
        $error = error_get_last();
        // isFatalError() 在 yii\base\ErrorException 中也有定义
        if (LException::isFatalError($error)) {
            $exception = new \ErrorException($error['message'], 500, $error['type'], $error['file'], $error['line']);
            $this->exception = $exception;

            $this->logException($exception);

            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);

            // need to explicitly flush logs because exit() next will terminate the app immediately
            Yii::getLogger()->flush(true);die;
        }
    }

    /**
     * 渲染输出错误异常
     * 覆盖父类定义 yii\console\ErrorHandler->renderException()
     * @param $exception
     */
    public function renderException($exception)
    {
        $data = $this->formatException($exception);

        // 测试环境抛出异常
        if (YII_DEBUG) {
            throw $exception;
        }
    }

    /**
     * 格式化错误信息
     * @param $exception
     * @return array
     */
    protected function formatException($exception)
    {
        $fileName = $exception->getFile();
        $errorLine = $exception->getLine();

        $trace = $exception->getTrace();

        foreach ($trace as $i => $t)
        {
            if (!isset($t['file'])) {
                $trace[$i]['file'] = 'unknown';
            }

            if (!isset($t['line'])) {
                $trace[$i]['line'] = 0;
            }

            if (!isset($t['function'])) {
                $trace[$i]['function'] = 'unknown';
            }

            unset($trace[$i]['object']);
        }

        return array(
            'type' => get_class($exception),
            'errorCode' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $fileName,
            'line' => $errorLine,
            'trace' => $exception->getTraceAsString(),
//            'traces' => $trace,
        );
    }
}