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
STEP_LABEL=""
STEP_ESTIMATE=0

step() {
  TOTAL=$((TOTAL + 1))
  START_T=$(date +%s)
  STEP_LABEL="$1"
  STEP_ESTIMATE="$2"
}
elapsed() { END_T=$(date +%s); echo "$((END_T - START_T))s"; }

ok()   { printf "\r\033[K    ${GREEN}✓${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; PASSED=$((PASSED + 1)); }
fail() { printf "\r\033[K    ${RED}✗${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
warn() { printf "\r\033[K    ${YELLOW}~${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
hint() { printf "      ${DIM}-> make %s${RESET}\n" "$1"; }

format_countdown() {
  _remaining=$1
  if [ "$_remaining" -le 0 ]; then
    _el=$(($(date +%s) - START_T))
    printf "+%ds" "$_el"
  elif [ "$_remaining" -ge 60 ]; then
    printf "%dm%02ds" "$((_remaining / 60))" "$((_remaining % 60))"
  else
    printf "%ds" "$_remaining"
  fi
}

countdown_loop() {
  _pid=$1
  _label=$2
  _est=$3
  while kill -0 "$_pid" 2>/dev/null; do
    _elapsed=$(($(date +%s) - START_T))
    _remain=$((_est - _elapsed))
    _display=$(format_countdown "$_remain")
    printf "\r\033[K    ${DIM}⏳ %-16s %s remaining${RESET}" "$_label" "$_display"
    sleep 1
  done
}

strip_ansi() { sed "s/${ESC}\[[0-9;]*[a-zA-Z]//g" | tr -d '\r'; }

GLOBAL_START=$(date +%s)

echo ""
printf "${CYAN}  ╔═══════════════════════════════════════════╗${RESET}\n"
printf "${CYAN}  ║           MONARK CI DASHBOARD             ║${RESET}\n"
printf "${CYAN}  ╚═══════════════════════════════════════════╝${RESET}\n"

# ── BACKEND ──────────────────────────────────────

echo ""
printf "${BLUE}  BACKEND ${DIM}PHP / Symfony / Pest${RESET}\n\n"

step "Lint" 2
$BACK 'php vendor/bin/php-cs-fixer fix --dry-run -q > /tmp/ci-lint.txt 2>&1; echo $? > /tmp/ci-lint-rc.txt' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Lint" "$STEP_ESTIMATE"
wait $BG_PID
BG_RC=$($BACK 'cat /tmp/ci-lint-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Lint" "CS Fixer OK"
else
  fail "Lint" "CS Fixer issues"
  hint "lint-backend"
fi

step "Static analysis" 5
$BACK 'php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress --error-format=raw > /tmp/ci-phpstan.txt 2>&1' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Static analysis" "$STEP_ESTIMATE"
wait $BG_PID
PHPSTAN_ERRORS=$($BACK 'grep "^/app/" /tmp/ci-phpstan.txt | wc -l' 2>/dev/null | tr -d ' \r')
if [ "$PHPSTAN_ERRORS" = "0" ]; then
  ok "Static analysis" "PHPStan 0 errors"
else
  fail "Static analysis" "PHPStan ${PHPSTAN_ERRORS} errors"
  hint "lint-backend"
fi

step "Tests" 3
$BACK 'php -d memory_limit=512M vendor/bin/pest --no-coverage --log-junit /tmp/pest-ci.xml 2>/dev/null' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Tests" "$STEP_ESTIMATE"
wait $BG_PID
T=$($BACK "grep -o 'tests=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
F=$($BACK "grep -o 'failures=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
E=$($BACK "grep -o 'errors=\"[0-9]*\"' /tmp/pest-ci.xml 2>/dev/null | head -1 | grep -oE '[0-9]+'" 2>/dev/null | tr -dc '0-9')
PEST_PASS=$((${T:-0} - ${F:-0} - ${E:-0}))
PEST_FAIL=$((${F:-0} + ${E:-0}))
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

step "Coverage" 10
$BACK 'php -d memory_limit=512M -d xdebug.mode=coverage vendor/bin/pest --no-coverage --coverage-clover /tmp/clover-ci.xml 2>/dev/null' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Coverage" "$STEP_ESTIMATE"
wait $BG_PID
COV_RESULT=$($BACK 'php -r "
\$xml = @simplexml_load_file(\"/tmp/clover-ci.xml\");
if (!\$xml) { echo \"statements=\\\"0\\\" coveredstatements=\\\"0\\\"\"; exit; }
\$m = \$xml->project->metrics;
echo \"statements=\\\"\" . (int)\$m[\"statements\"] . \"\\\" coveredstatements=\\\"\" . (int)\$m[\"coveredstatements\"] . \"\\\"\";
" 2>/dev/null' 2>/dev/null | cat | tr -d '\r')
S=$(echo "$COV_RESULT" | grep -oE 'statements="[0-9]+"' | head -1 | grep -oE '[0-9]+')
C=$(echo "$COV_RESULT" | grep -oE 'coveredstatements="[0-9]+"' | head -1 | grep -oE '[0-9]+')
COV_STMTS=${S:-0}
COV_COVERED=${C:-0}
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

step "Mutation" 150
$BACK 'php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/pest --mutate --parallel --everything --covered-only --min=0 > /tmp/ci-mutate.txt 2>&1' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Mutation" "$STEP_ESTIMATE"
wait $BG_PID
MUTATE_OUT=$($BACK 'cat /tmp/ci-mutate.txt' 2>/dev/null | strip_ansi)
MSI_SCORE=$(echo "$MUTATE_OUT" | grep -oE 'Score:[[:space:]]+[0-9]+\.[0-9]+' | grep -oE '[0-9]+\.[0-9]+' | head -1)
MSI_TESTED=$(echo "$MUTATE_OUT" | grep -oE '[0-9]+ tested' | grep -oE '[0-9]+' | head -1)
MSI_UNTESTED=$(echo "$MUTATE_OUT" | grep -oE '[0-9]+ untested' | grep -oE '[0-9]+' | head -1)
if [ -n "$MSI_SCORE" ]; then
  MSI_TOTAL=$((${MSI_TESTED:-0} + ${MSI_UNTESTED:-0}))
  ok "Mutation" "MSI: ${MSI_SCORE}% (${MSI_TESTED:-0}/${MSI_TOTAL})"
else
  warn "Mutation" "N/A"
fi

# ── FRONTEND ─────────────────────────────────────

echo ""
printf "${BLUE}  FRONTEND ${DIM}TypeScript / Vue / Vitest${RESET}\n\n"

step "Lint" 5
$FRONT 'pnpm lint -q > /tmp/ci-eslint.txt 2>&1; echo $? > /tmp/ci-eslint-rc.txt' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Lint" "$STEP_ESTIMATE"
wait $BG_PID
BG_RC=$($FRONT 'cat /tmp/ci-eslint-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Lint" "ESLint OK"
else
  fail "Lint" "ESLint issues"
  hint "lint-frontend"
fi

step "Format" 10
$FRONT 'pnpm format:check > /tmp/ci-prettier.txt 2>&1; echo $? > /tmp/ci-prettier-rc.txt' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Format" "$STEP_ESTIMATE"
wait $BG_PID
BG_RC=$($FRONT 'cat /tmp/ci-prettier-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Format" "Prettier OK"
else
  fail "Format" "Prettier issues"
  hint "lint-frontend"
fi

step "Tests" 5
$FRONT 'pnpm vitest run > /tmp/ci-vitest.txt 2>&1' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Tests" "$STEP_ESTIMATE"
wait $BG_PID
VITEST_OUT=$($FRONT 'cat /tmp/ci-vitest.txt' 2>/dev/null)
VITEST_TESTS=$(echo "$VITEST_OUT" | grep "Tests" | grep -oE "[0-9]+ passed" | head -1)
if [ -n "$VITEST_TESTS" ]; then
  ok "Tests" "Vitest ${VITEST_TESTS}"
else
  fail "Tests" "Vitest failures"
  hint "test-frontend"
fi

step "Coverage" 10
$FRONT 'pnpm vitest run --coverage --reporter=dot > /tmp/ci-fcov.txt 2>&1' >/dev/null 2>&1 &
BG_PID=$!
countdown_loop $BG_PID "Coverage" "$STEP_ESTIMATE"
wait $BG_PID
FCOV_OUT=$($FRONT 'cat /tmp/ci-fcov.txt' 2>/dev/null)
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
