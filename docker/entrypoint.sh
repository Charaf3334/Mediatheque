#!/bin/sh
set -e
cd /var/www/html

# fresh clone: install the dependencies
[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist

# safety net: wait until the database really accepts connections
until php bin/console dbal:run-sql 'SELECT 1' >/dev/null 2>&1; do
    echo "waiting for the database..."
    sleep 2
done

# schema, then demo data only when the database is empty
php bin/console doctrine:migrations:migrate -n --allow-no-migration
USERS=$(php bin/console dbal:run-sql 'SELECT COUNT(*) FROM user' | grep -Eo '[0-9]+' | tail -1)
if [ "$USERS" = "0" ]; then
    php bin/console doctrine:fixtures:load -n
fi

mkdir -p var && chown -R www-data:www-data var || true
echo "setup done, starting Apache"
exec "$@"
