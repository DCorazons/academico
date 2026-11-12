#!/bin/sh
# Equivalence check, run inside the dev app container on each implementation branch:
#   docker compose exec app sh tests/Equivalence/capture.sh ar
# Seeds a temporary database with ResearchSeeder, records SQL and responses of every
# endpoint to storage/app/equivalence/<label>.txt, then drops the database.
# The authentication query appears only for the first request, because the requests
# share one process.
set -e

LABEL=${1:?Give a label, for example ar, repo or qb}
OUTPUT=storage/app/equivalence
MYSQL="mysql -h mariadb -uroot -psecret --skip-ssl"

export DB_DATABASE=research_equivalence APP_DEBUG=false ACTIVITYLOG_ENABLED=false \
    CACHE_STORE=null SESSION_DRIVER=array QUEUE_CONNECTION=sync

mkdir -p "$OUTPUT"
$MYSQL -e "DROP DATABASE IF EXISTS research_equivalence; CREATE DATABASE research_equivalence; GRANT ALL ON research_equivalence.* TO 'academico'@'%';"
php artisan migrate:fresh --force --seed --seeder=ResearchSeeder > /dev/null
php tests/Equivalence/capture.php > "$OUTPUT/$LABEL.txt"
$MYSQL -e "DROP DATABASE research_equivalence;"

grep '^###' "$OUTPUT/$LABEL.txt"
echo "Saved to $OUTPUT/$LABEL.txt"
