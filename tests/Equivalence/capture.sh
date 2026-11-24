#!/bin/sh
# Equivalence check, run inside the dev app container on each implementation branch:
#   docker compose exec app sh tests/Equivalence/capture.sh ar     (impl/active-record)
#   docker compose exec app sh tests/Equivalence/capture.sh repo   (impl/repository)
#   docker compose exec app sh tests/Equivalence/capture.sh qb     (impl/query-builder)
# Seeds a temporary database with ResearchSeeder and records status, SQL with bindings,
# and body of every request: the API endpoints to storage/app/equivalence/<label>.txt,
# then, on a freshly seeded database, the server-rendered pages to <label>-web.txt.
# The database is dropped at the end. Compare with tests/Equivalence/compare.sh, for
# example compare.sh ar repo and compare.sh ar-web repo-web.
set -e

LABEL=${1:?Give a label, for example ar, repo or qb}
OUTPUT=storage/app/equivalence
MYSQL="mysql -h mariadb -uroot -psecret --skip-ssl"

export DB_DATABASE=research_equivalence APP_DEBUG=false ACTIVITYLOG_ENABLED=false \
    CACHE_STORE=null SESSION_DRIVER=database QUEUE_CONNECTION=sync BCRYPT_ROUNDS=10

seed() {
    $MYSQL -e "DROP DATABASE IF EXISTS research_equivalence; CREATE DATABASE research_equivalence; GRANT ALL ON research_equivalence.* TO 'academico'@'%';"
    php artisan migrate:fresh --force --seed --seeder='Database\Seeders\ResearchSeeder' > /dev/null
}

mkdir -p "$OUTPUT"
seed
php tests/Equivalence/capture.php > "$OUTPUT/$LABEL.txt"
seed
php tests/Equivalence/capture-web.php > "$OUTPUT/$LABEL-web.txt"
$MYSQL -e "DROP DATABASE research_equivalence;"

grep '^###' "$OUTPUT/$LABEL.txt" "$OUTPUT/$LABEL-web.txt"
echo "Saved to $OUTPUT/$LABEL.txt and $OUTPUT/$LABEL-web.txt"
