#!/usr/bin/env bash
docker-compose exec -T php bash -c "cd /var/www/app && vendor/bin/phpunit /var/www/app/tests/$1.php --colors=always"