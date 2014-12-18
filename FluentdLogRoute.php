<?php

/**
 * Fluentd Log Route class file.
 * 
 * @author Adinata <mail.dieend@gmail.com>
 * @since 2014.12.09
 */

namespace Urbanindo\Yii\Component\Logger;

use Fluent\Logger\FluentLogger;

/**
 * Log route using fluentd.
 *
 * @author Adinata <mail.dieend@gmail.com>
 * @since 2014.12.09
 */
class FluentdLogRoute extends \CLogRoute
{
    /* @var string host name */
    public $host = FluentLogger::DEFAULT_ADDRESS;

    /* @var int port number. when you wanna use unix domain socket. set port to 0 */
    public $port = FluentLogger::DEFAULT_LISTEN_PORT;

    /* @var string Various style transport: `tcp://localhost:port` */
    public $transport;

    /* @var resource */
    public $socket;

    /* @var PackerInterface */
    public $packer = null;

    public $tagFormat = 'yii.%l.%c';
    
    public $level_key = 'level';

    public $timestamp_key = 'timestamp';

    public $content_key = 'content';

    public $message_key = 'message';
    
    public function setHost($host) {
        $this->host= $host;
    }
    public function setPort($port) {
        $this->port= $port;
    }

    protected $options = array(
        "socket_timeout"     => FluentLogger::SOCKET_TIMEOUT,
        "connection_timeout" => FluentLogger::CONNECTION_TIMEOUT,
        "backoff_mode"       => FluentLogger::BACKOFF_TYPE_USLEEP,
        "backoff_base"       => 3,
        "usleep_wait"        => FluentLogger::USLEEP_WAIT,
        "persistent"         => false,
        "retry_socket"       => true,
        "max_write_retry"    => FluentLogger::MAX_WRITE_RETRY,
    );

    private $_logger;

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        if ($this->packer == null) {
            $this->packer = new MsgpackOrJsonPacker();
        } else if (!($this->packer instanceof \Fluent\Logger\PackerInterface)) {
            $this->packer = Yii::createComponent($this->packer);
        }
        $this->_logger = new FluentLogger($this->host, $this->port, $this->options, $this->packer);
    }

    /**
     * Processes log messages and sends them to specific destination.
     * Derived child classes must implement this method.
     * @param array $logs list of messages. Each array element represents one message
     * with the following structure:
     * array(
     *   [0] => message (string)
     *   [1] => level (string)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true));
     */
    protected function processLogs($logs) {
        foreach ($logs as $log) {
            $tag = $this->createTag($log);
            $data = [
                $this->level_key => $log[1],
                $this->timestamp_key => $log[3],
                ];
            if (is_array($log[0])) {
                $data[$this->content_key] = $log[0];
            } else {
                $data[$this->content_key] = [
                    $this->message_key => $log[0],
                ];
            }
            $this->_logger->post($tag,$data);
        }
    }

    private function createTag($log) {
        $ret = $this->tagFormat;
        $ret = str_replace("%c", $log[2], $ret);
        $ret = str_replace("%l", $log[1], $ret);
        return $ret;
    }
}
