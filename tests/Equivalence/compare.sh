#!/bin/sh
# Compares two outputs of capture.sh:
#   sh tests/Equivalence/compare.sh ar repo        status, SQL with bindings, body
#   sh tests/Equivalence/compare.sh ar qb --body   status and body only
# Active Record and Repository must match fully. The query builder implementation
# is a reference: only its responses must match.
set -e

OUTPUT=storage/app/equivalence
A=${1:?Give two labels, for example ar repo}
B=${2:?Give two labels, for example ar repo}
MODE=${3:-}

filter() {
    if [ "$MODE" = "--body" ]; then
        grep -E '^(###|BODY)' "$1" | sed -E 's/, [0-9]+ queries$//'
    else
        cat "$1"
    fi
}

filter "$OUTPUT/$A.txt" > "$OUTPUT/.compare-$A"
filter "$OUTPUT/$B.txt" > "$OUTPUT/.compare-$B"

if diff "$OUTPUT/.compare-$A" "$OUTPUT/.compare-$B"; then
    echo "Identical: $A and $B ($(grep -c '^###' "$OUTPUT/.compare-$A") requests${MODE:+, $MODE})"
    status=0
else
    echo "Different: $A and $B"
    status=1
fi

rm -f "$OUTPUT/.compare-$A" "$OUTPUT/.compare-$B"
exit $status
