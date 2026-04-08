#!/bin/sh
set -u

DC="docker compose -f docker/compose.yaml -f docker/compose.override.yaml"
BACK="$DC exec -T backend sh -c"

DIRS="tests/Unit/Catalog/Infrastructure/Scanner tests/Unit/Catalog/Infrastructure/GitProvider tests/Unit/Catalog/Application/CommandHandler tests/Unit/Catalog/Application/QueryHandler tests/Unit/Catalog/Presentation tests/Unit/Dependency tests/Unit/Identity tests/Unit/Shared"
KILLED=0
TOTAL=0

echo ""
echo "  Running mutation testing (Infection)..."
echo ""

for DIR in $DIRS; do
  DIRNAME=$(echo "$DIR" | sed 's|tests/Unit/||')
  printf "  %-40s " "$DIRNAME"

  OUT=$($BACK "rm -rf /tmp/infection-cov; php -d xdebug.mode=coverage vendor/bin/pest $DIR --no-coverage --coverage-xml /tmp/infection-cov/coverage-xml --log-junit /tmp/infection-cov/junit.xml 2>/dev/null; sed -i 's|Tests\\\\Unit|P\\\\Tests\\\\Unit|g' /tmp/infection-cov/junit.xml 2>/dev/null; vendor/bin/infection --threads=4 --coverage=/tmp/infection-cov --skip-initial-tests --min-msi=0 --min-covered-msi=0 --no-progress 2>&1" 2>/dev/null | cat)

  K=$(echo "$OUT" | grep -oE "[0-9]+ mutants were killed" | grep -oE "[0-9]+" | head -1)
  T=$(echo "$OUT" | grep -oE "[0-9]+ mutations were generated" | grep -oE "[0-9]+" | head -1)

  if [ -n "$T" ] && [ "$T" -gt 0 ]; then
    PCT=$((${K:-0} * 100 / T))
    echo "${K:-0}/${T} killed (${PCT}%)"
  else
    echo "no mutations"
  fi

  KILLED=$((KILLED + ${K:-0}))
  TOTAL=$((TOTAL + ${T:-0}))
done

echo ""
if [ "$TOTAL" -gt 0 ]; then
  MSI=$((KILLED * 1000 / TOTAL))
  MSI_INT=$((MSI / 10))
  MSI_DEC=$((MSI % 10))
  echo "  MSI: ${MSI_INT}.${MSI_DEC}% (${KILLED}/${TOTAL} mutants killed)"
else
  echo "  No mutations generated"
fi
echo ""
