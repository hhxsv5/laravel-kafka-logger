<?php

namespace Hhxsv5\LKL;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\Topic;
use RdKafka\TopicConf;

class KafkaLogHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    protected $applicationName;

    /**
     * @var Topic
     */
    private $topic;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var string
     */
    private $fallback;

    /**
     * @var int
     */
    private $flushTimeout;

    /**
     * KafkaLogHandler constructor.
     * @see https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/index.html
     * @param string $applicationName the application that sends the data, used as the "type" field of logstash
     * @param Conf $producerConf Producer config
     * @param string $topicName Kafka topic name (if it doesn't exist yet, will be created)
     * @param TopicConf $topicConf Kafka topic config (optional)
     * @param string $fallback The fallback channel
     * @param int $flushTimeout The timeout(ms) to flush
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        $applicationName,
        Conf $producerConf,
        $topicName,
        TopicConf $topicConf = null,
        $fallback = 'daily',
        $flushTimeout = 60000,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->applicationName = $applicationName;
        $this->producer = new Producer($producerConf);
        $this->topic = $this->producer->newTopic($topicName, $topicConf);
        $this->fallback = $fallback;
        $this->flushTimeout = $flushTimeout;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param array $record
     *
     * @return void
     */
    protected function write(array $record): void
    {
        $data = (string)$record['formatted'];
        try {
            $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $data);
            $this->producer->poll(0);
        } catch (\Exception $e) {
            // TODO: Unable to detect Kafka connection status, so Fallback is not useful
            $method = strtolower($record['level_name']);
            app('log')->channel($this->fallback)->$method(sprintf('%s (%s fallback: %s)', $data, $record['channel'], $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LogstashFormatter($this->applicationName, null, 'ext_', 'ctx_');
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();
        $result = $this->producer->flush($this->flushTimeout);
        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            app('log')->channel($this->fallback)->error(sprintf('Was unable to flush, messages might be lost! Reason=%s', rd_kafka_err2str($result)));
        }
    }
}