<?php

namespace common\lib;

use Yii;
use yii\log\Logger;
use yii\log\FileTarget;
use yii\helpers\VarDumper;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * Class LFileTarget
 * @package common\lib
 */
class LFileTarget extends FileTarget
{
    /**
     * @var array
     */
    public $levels_arr;

    /**
     * @param array|int $levels
     * @throws \yii\base\InvalidConfigException
     */
    public function setLevels($levels)
    {
        $this->levels_arr = $levels;

        parent::setLevels($levels);
    }

    /**
     * Writes log messages to a file.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws InvalidConfigException if unable to open the log file for writing
     * @throws LogRuntimeException if unable to write complete log to file
     */
    public function export()
    {
        if (strpos($this->logFile, '://') === false || strncmp($this->logFile, 'file://', 7) === 0) {
            $logPath = dirname($this->logFile);
            FileHelper::createDirectory($logPath, $this->dirMode, true);
        }

        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        if (!empty($text)) {
            if (($fp = @fopen($this->logFile, 'a')) === false) {
                throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
            }
            @flock($fp, LOCK_EX);
            if ($this->enableRotation) {
                // clear stat cache to ensure getting the real current file size and not a cached one
                // this may result in rotating twice when cached file size is used on subsequent calls
                clearstatcache();
            }
            if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
                $this->rotateFiles();
                $writeResult = @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
                if ($writeResult === false) {
                    $error = error_get_last();
                    throw new LogRuntimeException("Unable to export log through file!: {$error['message']}");
                }
                $textSize = strlen($text);
                if ($writeResult < $textSize) {
                    throw new LogRuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
                }
            } else {
                $writeResult = @fwrite($fp, $text);
                if ($writeResult === false) {
                    $error = error_get_last();
                    throw new LogRuntimeException("Unable to export log through file!: {$error['message']}");
                }
                $textSize = strlen($text);
                if ($writeResult < $textSize) {
                    throw new LogRuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
                }
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
            if ($this->fileMode !== null) {
                @chmod($this->logFile, $this->fileMode);
            }
        }
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!in_array($level, $this->levels_arr)) {
            return '';
        }

        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string) $text;
            } else {
                $text = VarDumper::export($text);
            }
        }
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        $prefix = $this->getMessagePrefix($message);
        return $this->getTime($timestamp) . " {$prefix}[$level][$category] $text"
            . (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
    }
}