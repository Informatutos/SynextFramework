#!/bin/bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-clover=build/coverage/clover.xml --coverage-text
php ocular.phar code-coverage:upload --format=php-clover build/coverage/clover.xml
