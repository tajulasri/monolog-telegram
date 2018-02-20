<?php
namespace Cet\TelegramHandler;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Telegram Handler For Monolog
 *
 * This class helps you in logging your application events
 * into telegram using it's API.
 *
 * @author Moein Rahimi <m.rahimi2150@gmail.com>
 * @author M tajul Asri <mtajulasri@gmai.com>
 */

class TelegramHandler extends AbstractProcessingHandler
{

    /**
     * @var mixed
     */
    private $token;
    /**
     * @var mixed
     */
    private $channel;
    /**
     * @var mixed
     */
    private $dateFormat;

    /**
     * @var mixed
     */
    private $timeZone;

    /**
     * @var mixed
     */
    private $response;

    /**
     * @var int
     */
    private $messageLength = 4096;

    /**
     * @var array
     */
    private $emojiMapping = [
        Logger::DEBUG     => '🚧',
        Logger::INFO      => '‍🗨',
        Logger::NOTICE    => '🕵',
        Logger::WARNING   => '⚡️',
        Logger::ERROR     => '🚨',
        Logger::CRITICAL  => '🤒',
        Logger::ALERT     => '👀',
        Logger::EMERGENCY => '🤕',
    ];

    /**
     * getting token a channel name from Telegram Handler Object.
     *
     * @param string     $token   Telegram Bot Access Token Provided by BotFather
     * @param string     $channel Telegram Channel userName
     * @param string|int @level   Debug level of Logged Event
     */

    public function __construct($token, $channel, $timeZone = 'UTC', $dateFormat = 'F j, Y, g:i a')
    {

        $this->token = $token;
        $this->channel = $channel;
        $this->dateFormat = $dateFormat;

        $this->getTimezone();
        $this->curlExtensionInstalled();
    }

    /**
     * format the log to send
     * @param  $record[] log data
     * @return void
     */
    public function write(array $record)
    {
        $format = new LineFormatter;
        $context = $record['context'] ? $format->stringify($record['context']) : '';
        $date = date($this->dateFormat);
        $truncatedLongMessage = substr($record['formatted'], 0, $this->messageLength);
        $message = $date . PHP_EOL . $this->emojiMapping[$record['level']] . ' ' . $truncatedLongMessage . $context;

        $this->send($message);

    }

    /**
     *    send log to telegram channel
     * @param  string $message Text Message
     * @return void
     */
    public function send($message, array $options = [])
    {
        try {
            $ch = curl_init();
            $url = $this->getEndpoint() . $this->token . "/SendMessage";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'text'    => $message,
                'chat_id' => $this->channel,
                $options,
            )));
            $result = curl_exec($ch);
            $result = json_decode($result, 1);

            $this->response = $result;

            if ($result['ok'] === false) {
                echo 'telegram api response : ' . $result['description'];
            }
        } catch (Exception $error) {
            echo $error;
        }
    }

    /**
     * set timezone
     * @param $timeZone
     */
    public function setTimezone($timeZone = null)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * get timezone
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timeZone;
    }

    /**
     * set date format
     * @param $format
     */
    public function setDateFormat($format = null)
    {
        $this->dateFormat = $format;
    }

    /**
     * @return mixed
     */
    public function getDateformat()
    {
        return $this->dateFormat;
    }

    /**
     * set default timezone
     */
    protected function setDefaultTimezoneFormat()
    {
        return date_default_timezone_set($this->timeZone);
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * check for curl extension
     * @return Exception
     */
    protected function curlExtensionInstalled()
    {
        if (!extension_loaded('curl')) {
            throw new Exception('curl is needed to use this library');
        }
    }

    /**
     * telegram endpoint
     * @return string
     */
    protected function getEndpoint()
    {
        return 'https://api.telegram.org/bot';
    }

}
