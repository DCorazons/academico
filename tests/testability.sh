#!/bin/sh
# Testability measurement (thesis proposal, section 3.2.4), run inside the dev app container:
#   docker compose exec app sh tests/testability.sh
# Runs the Service unit test suite 10 times and records the time of each run, then
# runs it once more with PCOV to measure the code coverage of the four Services.
set -e

RUNS=10
OUTPUT=storage/app/testability
MYSQL="mysql -h mariadb -uroot -psecret --skip-ssl"

mkdir -p "$OUTPUT"
$MYSQL -e "CREATE DATABASE IF NOT EXISTS academico_testing; GRANT ALL ON academico_testing.* TO 'academico'@'%';"

echo "run,seconds,result" > "$OUTPUT/times.csv"
for run in $(seq 1 $RUNS); do
    start=$(date +%s.%N)
    if php -d pcov.enabled=0 vendor/bin/phpunit -c phpunit.testability.xml --no-coverage > "$OUTPUT/run-$run.log" 2>&1; then
        result=passed
    else
        result=failed
    fi
    end=$(date +%s.%N)
    seconds=$(awk "BEGIN { printf \"%.3f\", $end - $start }")
    echo "$run,$seconds,$result" >> "$OUTPUT/times.csv"
    echo "run $run: $seconds s ($result)"
done

php -d pcov.enabled=1 -d pcov.directory=app vendor/bin/phpunit -c phpunit.testability.xml \
    --coverage-text="$OUTPUT/coverage.txt" > "$OUTPUT/coverage-run.log" 2>&1
cat "$OUTPUT/coverage.txt"

echo "Results in $OUTPUT"
