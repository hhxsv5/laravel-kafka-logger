# laravel-kafka-logger
The Kafka logger for Laravel.

[![Latest Version](https://img.shields.io/github/release/hhxsv5/laravel-kafka-logger.svg)](https://github.com/hhxsv5/laravel-kafka-logger/releases)
[![PHP Version](https://img.shields.io/packagist/php-v/hhxsv5/laravel-kafka-logger.svg?color=green)](https://secure.php.net)
[![Total Downloads](https://poser.pugx.org/hhxsv5/laravel-kafka-logger/downloads)](https://packagist.org/packages/hhxsv5/laravel-kafka-logger)
[![License](https://poser.pugx.org/hhxsv5/laravel-kafka-logger/license)](LICENSE)

## Requirements

| Dependency | Requirement |
| -------- | -------- |
| [php-rdkafka](https://github.com/arnaud-lb/php-rdkafka) | `>=4.0.0` |

## Install

1.Install `rdkafka`.
```bash
git clone --depth 1 https://github.com/edenhill/librdkafka.git /tmp/librdkafka && cd /tmp/librdkafka && ./configure && make -j$(nproc) && make install && rm -rf /tmp/librdkafka
pecl install rdkafka
```

2.Install `laravel-kafka-logger`.
```shell
# Laravel 5.x
composer require "hhxsv5/laravel-kafka-logger:~1.0.0"
# Laravel 6.x & 7.x
composer require "hhxsv5/laravel-kafka-logger:~2.0.0"
```

## Get Started

1.Modify `config/logging.php`.
```php
return [
    'channels' => [
        // ...
        'kafka' => Hhxsv5\LKL\KafkaLogger::getDefinition(['topic' => env('LOG_KAFKA_TOPIC', 'laravel-logs')]),
    ],
];
```

2.Modify `.env`.
```
LOG_CHANNEL=kafka
LOG_KAFKA_BROKER_LIST=kafka:9092
LOG_KAFKA_TOPIC=laravel-logs
```

## License

[MIT](LICENSE)