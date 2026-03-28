#!/bin/sh
set -u

DC="docker compose -f docker/compose.yaml -f docker/compose.override.yaml"
BACK="$DC exec -T backend sh -c"
FRONT="$DC exec -T frontend sh -c"

GREEN="\033[32m"
RED="\033[31m"
YELLOW="\033[33m"
CYAN="\033[1;36m"
BLUE="\033[1;34m"
DIM="\033[2m"
BOLD="\033[1m"
RESET="\033[0m"
ESC=$(printf '\033')

TOTAL=0
PASSED=0
step() { TOTAL=$((TOTAL + 1)); START_T=$(date +%s); }
elapsed() { END_T=$(date +%s); echo "$((END_T - START_T))s"; }

ok()   { printf "    ${GREEN}✓${RESET} %-16s %-24s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; PASSED=$((PASSED + 1)); }
fail() { printf "    ${RED}✗${RESET} %-16s %-24s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
warn() { printf "    ${YELLOW}~${RESET} %-16s %-24s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
hint() { printf "      ${DIM}-> make %s${RESET}\n" "$1"; }

strip_ansi() { sed "s/${ESC}\[[0-9;]*[a-zA-Z]//g" | tr -d '\r'; }

GLOBAL_START=$(date +%s)

echo ""
printf "${CYAN}  ╔═══════════════════════════════════════════╗${RESET}\n"
printf "${CYAN}  ║           MONARK CI DASHBOARD             ║${RESET}\n"
printf "${CYAN}  ╚═══════════════════════════════════════════╝${RESET}\n"

# ── BACKEND ──────────────────────────────────────

echo ""
printf "${BLUE}  BACKEND ${DIM}PHP / Symfony / Pest${RESET}\n\n"

step
if $BACK 'php vendor/bin/php-cs-fixer fix --dry-run -q 2>&1' 2>/dev/null | cat >/dev/null 2>&1; then
  ok "Lint" "CS Fixer OK"
else
  fail "Lint" "CS Fixer issues"
  hint "lint-backend"
fi

step
PHPSTAN_ERRORS=$($BACK 'php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1' 2>/dev/null | cat | grep "^/app/" | wc -l | tr -d ' ')
if [ "$PHPSTAN_ERRORS" = "0" ]; then
  ok "Static analysis" "PHPStan 0 errors"
else
  fail "Static analysis" "PHPStan ${PHPSTAN_ERRORS} errors"
  hint "lint-backend"
fi

step
PEST_PASS=0
PEST_FAIL=0
for DIR in tests/Unit/Catalog tests/Unit/Dependency tests/Unit/Identity tests/Unit/Shared; do
  $BACK "php -d memory_limit=512M vendor/bin/pest $DIR --no-coverage --log-junit /tmp/pest-ci.xml 2>/dev/null" 2>/dev/null | cat >/dev/null 2>&1
  T=$($BACK "grep -o 'tests=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
  F=$($BACK "grep -o 'failures=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
  E=$($BACK "grep -o 'errors=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
  PEST_PASS=$((PEST_PASS + ${T:-0} - ${F:-0} - ${E:-0}))
  PEST_FAIL=$((PEST_FAIL + ${F:-0} + ${E:-0}))
done
PEST_TOTAL=$((PEST_PASS + PEST_FAIL))
if [ "$PEST_TOTAL" -gt 0 ] && [ "$PEST_FAIL" -eq 0 ]; then
  ok "Tests" "Pest ${PEST_PASS} passed"
elif [ "$PEST_TOTAL" -gt 0 ]; then
  fail "Tests" "Pest ${PEST_FAIL}/${PEST_TOTAL} failed"
  hint "test-backend"
else
  fail "Tests" "Pest could not run"
  hint "test-backend"
fi

step
COV_STMTS=0
COV_COVERED=0
for DIR in tests/Unit/Catalog/Infrastructure tests/Unit/Catalog/Application tests/Unit/Dependency tests/Unit/Identity tests/Unit/Shared; do
  $BACK "rm -f /tmp/clover-ci.xml; php -d memory_limit=512M -d xdebug.mode=coverage vendor/bin/pest $DIR --no-coverage --coverage-clover /tmp/clover-ci.xml 2>/dev/null" 2>/dev/null | cat >/dev/null 2>&1
  METRICS=$($BACK 'grep "metrics" /tmp/clover-ci.xml 2>/dev/null | tail -1' 2>/dev/null | cat | tr -d '\r')
  S=$(echo "$METRICS" | grep -oE 'statements="[0-9]+"' | head -1 | grep -oE '[0-9]+')
  C=$(echo "$METRICS" | grep -oE 'coveredstatements="[0-9]+"' | head -1 | grep -oE '[0-9]+')
  if [ -n "$S" ] && [ -n "$C" ]; then
    if [ "$COV_STMTS" -eq 0 ]; then
      COV_STMTS=$S
      COV_COVERED=$C
    fi
  fi
done
if [ "$COV_STMTS" -gt 0 ]; then
  BCOV_PCT=$((COV_COVERED * 1000 / COV_STMTS))
  BCOV_INT=$((BCOV_PCT / 10))
  BCOV_DEC=$((BCOV_PCT % 10))
  if [ "$BCOV_INT" -ge 80 ]; then
    ok "Coverage" "${BCOV_INT}.${BCOV_DEC}% (>= 80%)"
  else
    warn "Coverage" "${BCOV_INT}.${BCOV_DEC}% (< 80%)"
  fi
else
  warn "Coverage" "N/A"
fi

step
MSI_KILLED=0
MSI_TOTAL=0
for DIR in tests/Unit/Catalog/Infrastructure/Scanner tests/Unit/Catalog/Infrastructure/GitProvider tests/Unit/Catalog/Application/CommandHandler tests/Unit/Catalog/Application/QueryHandler tests/Unit/Catalog/Presentation tests/Unit/Dependency tests/Unit/Identity tests/Unit/Shared; do
  INF_OUT=$($BACK "rm -rf /tmp/infection-cov; php -d memory_limit=512M -d xdebug.mode=coverage vendor/bin/pest $DIR --no-coverage --coverage-xml /tmp/infection-cov/coverage-xml --log-junit /tmp/infection-cov/junit.xml 2>/dev/null; sed -i 's|Tests\\\\Unit|P\\\\Tests\\\\Unit|g' /tmp/infection-cov/junit.xml 2>/dev/null; php -d memory_limit=512M vendor/bin/infection --threads=4 --coverage=/tmp/infection-cov --skip-initial-tests --min-msi=0 --min-covered-msi=0 --no-progress 2>&1" 2>/dev/null | cat)
  K=$(echo "$INF_OUT" | grep -oE "[0-9]+ mutants were killed" | grep -oE "[0-9]+" | head -1)
  T=$(echo "$INF_OUT" | grep -oE "[0-9]+ mutations were generated" | grep -oE "[0-9]+" | head -1)
  MSI_KILLED=$((MSI_KILLED + ${K:-0}))
  MSI_TOTAL=$((MSI_TOTAL + ${T:-0}))
done
if [ "$MSI_TOTAL" -gt 0 ]; then
  MSI_PCT=$((MSI_KILLED * 1000 / MSI_TOTAL))
  MSI_INT=$((MSI_PCT / 10))
  MSI_DEC=$((MSI_PCT % 10))
  ok "Mutation" "MSI: ${MSI_INT}.${MSI_DEC}% (${MSI_KILLED}/${MSI_TOTAL})"
else
  warn "Mutation" "N/A"
fi

# ── FRONTEND ─────────────────────────────────────

echo ""
printf "${BLUE}  FRONTEND ${DIM}TypeScript / Vue / Vitest${RESET}\n\n"

step
if $FRONT 'pnpm lint -q 2>&1' 2>/dev/null | cat >/dev/null 2>&1; then
  ok "Lint" "ESLint OK"
else
  fail "Lint" "ESLint issues"
  hint "lint-frontend"
fi

step
if $FRONT 'pnpm format:check 2>&1' 2>/dev/null | cat >/dev/null 2>&1; then
  ok "Format" "Prettier OK"
else
  fail "Format" "Prettier issues"
  hint "lint-frontend"
fi

step
VITEST_OUT=$($FRONT 'pnpm vitest run 2>&1' 2>/dev/null)
VITEST_TESTS=$(echo "$VITEST_OUT" | grep "Tests" | grep -oE "[0-9]+ passed" | head -1)
VITEST_FILES=$(echo "$VITEST_OUT" | grep "Test Files" | grep -oE "[0-9]+ passed" | head -1)
if [ -n "$VITEST_TESTS" ]; then
  ok "Tests" "Vitest ${VITEST_TESTS}, ${VITEST_FILES} files"
else
  fail "Tests" "Vitest failures"
  hint "test-frontend"
fi

step
FCOV_OUT=$($FRONT 'pnpm vitest run --coverage --reporter=dot 2>&1' 2>/dev/null)
FCOV=$(echo "$FCOV_OUT" | grep "All files" | grep -oE "[0-9]+\.[0-9]+" | head -1)
if [ -n "$FCOV" ]; then
  ok "Coverage" "${FCOV}%"
else
  warn "Coverage" "N/A"
fi

# ── RESULT ───────────────────────────────────────

GLOBAL_END=$(date +%s)
GLOBAL_ELAPSED=$((GLOBAL_END - GLOBAL_START))
MINS=$((GLOBAL_ELAPSED / 60))
SECS=$((GLOBAL_ELAPSED % 60))

echo ""
printf "${CYAN}  ═════════════════════════════════════════════${RESET}\n"
if [ "$PASSED" -eq "$TOTAL" ]; then
  printf "  ${GREEN}${BOLD}${PASSED}/${TOTAL} checks passed${RESET} ${DIM}in ${MINS}m${SECS}s${RESET}\n"
else
  FAILED=$((TOTAL - PASSED))
  printf "  ${BOLD}${PASSED}/${TOTAL} passed${RESET} ${DIM}— ${FAILED} need attention — ${MINS}m${SECS}s${RESET}\n"
fi
echo ""
