<?php
namespace common\service;

use Yii;

/**
 * 邮件发送服务
 * Class MailService
 * @package common\service
 */
class MailService
{
    /**
     * 队列中邮件发送
     * @param $msg
     * @return bool
     * @throws \Exception
     */
    public static function sendMailOfQueue($msg)
    {
        if (!self::sendMail($msg['send_to'], $msg['cc_to'], $msg['text'], $msg['title'], $msg['file'])) {
            $exception['type'] = 'sendMail';
            $exception['msg'] = $msg;
            $exception = json_encode($exception);

            throw new \Exception($exception);
        }

        return true;
    }

    /**
     * 邮件发送
     * @param $sendTo
     * @param $ccTo
     * @param $text
     * @param $title
     * @param $file
     * @return bool
     */
    public static function sendMail($sendTo, $ccTo, $text, $title, $file)
    {
        $mail = Yii::$app->mailer->compose();
        $mail->setTo($sendTo);
        $mail->setCc($ccTo);
        $mail->setTextBody($text);
        $mail->setSubject($title);

        if (!empty($file)) {
            $mail->attach($file['name']);
        }

        if ($mail->send()) {
            $mail = null;

            return true;
        } else {
            $mail = null;

            return false;
        }
    }
}