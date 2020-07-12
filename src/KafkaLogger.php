<?php

namespace Hhxsv5\LKL;

use Monolog\Logger;
use RdKafka\Conf;

class KafkaLogger
{
    public static function getDefinition(array $custom = [])
    {
        $default = [
            'driver'           => 'custom',
            'via'              => static::class,
            'name'             => config('app.name'),
            'topic'            => env('LOG_KAFKA_TOPIC', 'php-logs'),
            'fallback'         => 'daily',
            'flush_timeout_ms' => 5000,
            'settings'         => [ // see https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
                'metadata.broker.list'    => env('LOG_KAFKA_BROKER_LIST', '127.0.0.1:9092'),
                'queue.buffering.max.ms'  => 3000, // < flush_timeout_ms, send messages ASAP
                'socket.keepalive.enable' => true,
                'log_level'               => LOG_WARNING,
            ],
        ];
        return array_merge($default, $custom);
    }

    public function __invoke(array $config)
    {
        if (!isset($config['fallback'])) {
            $config['fallback'] = 'daily';
        }
        if (!isset($config['flush_timeout_ms'])) {
            $config['flush_timeout_ms'] = 60000;
        }
        if (!isset($config['level'])) {
            $config['level'] = Logger::DEBUG;
        }
        if (!isset($config['bubble'])) {
            $config['bubble'] = true;
        }

        $logger = new Logger('kafka');

        $producerConf = new Conf();
        foreach ($config['settings'] as $key => $value) {
            $producerConf->set($key, $value);
        }
        $producerConf->setLogCb(function ($kafka, $level, $facility, $message) use ($config) {
            app('log')->channel($config['fallback'])->info(sprintf('KafkaLogger %s: %s (level: %d)', $facility, $message, $level));
        });
        $producerConf->setErrorCb(function ($kafka, $err, $reason) use ($config) {
            app('log')->channel($config['fallback'])->error(sprintf('KafkaLogger error: %s (reason: %s)', rd_kafka_err2str($err), $reason));
        });
        $producerConf->setDrMsgCb(function ($kafka, $message) use ($config) {
            if ($message->err) {
                app('log')->channel($config['fallback'])->error(sprintf('KafkaLogger delivery fail: %s', $message->errstr()));
            }
        });
        $handler = new KafkaLogHandler($config['name'], $producerConf, $config['topic'], null, $config['fallback'], $config['flush_timeout_ms'], $config['level'], $config['bubble']);
        $logger->pushHandler($handler);
        return $logger;
    }
}