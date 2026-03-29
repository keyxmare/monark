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

elapsed() { echo "$(( $(date +%s) - START_T ))s"; }
ok()   { printf "\r\033[K    ${GREEN}✓${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; PASSED=$((PASSED + 1)); }
fail() { printf "\r\033[K    ${RED}✗${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
warn() { printf "\r\033[K    ${YELLOW}~${RESET} %-16s %-28s ${DIM}%s${RESET}\n" "$1" "$2" "$(elapsed)"; }
hint() { printf "      ${DIM}-> make %s${RESET}\n" "$1"; }

strip_ansi() { sed "s/${ESC}\[[0-9;]*[a-zA-Z]//g" | tr -d '\r'; }

start_countdown() {
  _label=$1; _est=$2
  (
    _start=$(date +%s)
    while true; do
      _elapsed=$(( $(date +%s) - _start ))
      _remain=$(( _est - _elapsed ))
      if [ "$_remain" -le 0 ]; then
        printf "\r\033[K    ${DIM}⏳ %-16s +%ds${RESET}" "$_label" "$(( -_remain ))"
      elif [ "$_remain" -ge 60 ]; then
        printf "\r\033[K    ${DIM}⏳ %-16s %dm%02ds${RESET}" "$_label" "$((_remain / 60))" "$((_remain % 60))"
      else
        printf "\r\033[K    ${DIM}⏳ %-16s %ds${RESET}" "$_label" "$_remain"
      fi
      sleep 1
    done
  ) &
  COUNTDOWN_PID=$!
}

stop_countdown() {
  kill $COUNTDOWN_PID 2>/dev/null
  wait $COUNTDOWN_PID 2>/dev/null
}

GLOBAL_START=$(date +%s)

echo ""
printf "${CYAN}  ╔═══════════════════════════════════════════╗${RESET}\n"
printf "${CYAN}  ║           MONARK CI DASHBOARD             ║${RESET}\n"
printf "${CYAN}  ╚═══════════════════════════════════════════╝${RESET}\n"

# ── BACKEND ──────────────────────────────────────

echo ""
printf "${BLUE}  BACKEND ${DIM}PHP / Symfony / Pest${RESET}\n\n"

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Lint" 2
$BACK 'php vendor/bin/php-cs-fixer fix --dry-run -q 2>&1; echo $? > /tmp/ci-rc.txt' >/dev/null 2>&1
stop_countdown
BG_RC=$($BACK 'cat /tmp/ci-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Lint" "CS Fixer OK"
else
  fail "Lint" "CS Fixer issues"
  hint "fix-backend"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Static analysis" 5
$BACK 'php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress --error-format=raw > /tmp/ci-phpstan.txt 2>&1' >/dev/null 2>&1
stop_countdown
PHPSTAN_ERRORS=$($BACK 'grep -c "^/app/" /tmp/ci-phpstan.txt 2>/dev/null || echo 0' 2>/dev/null | tr -dc '0-9')
if [ "${PHPSTAN_ERRORS:-0}" -eq 0 ]; then
  ok "Static analysis" "PHPStan 0 errors"
else
  fail "Static analysis" "PHPStan ${PHPSTAN_ERRORS} errors"
  hint "lint-backend"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Tests" 3
$BACK 'php -d memory_limit=512M vendor/bin/pest --no-coverage --log-junit /tmp/pest-ci.xml 2>/dev/null' >/dev/null 2>&1
stop_countdown
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

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Coverage" 10
$BACK 'php -d memory_limit=512M -d xdebug.mode=coverage vendor/bin/pest --no-coverage --coverage-clover /tmp/clover-ci.xml 2>/dev/null' >/dev/null 2>&1
stop_countdown
COV_RESULT=$($BACK 'php -r "
\$xml = @simplexml_load_file(\"/tmp/clover-ci.xml\");
if (!\$xml) { echo \"statements=\\\"0\\\" coveredstatements=\\\"0\\\"\"; exit; }
\$m = \$xml->project->metrics;
echo \"statements=\\\"\" . (int)\$m[\"statements\"] . \"\\\" coveredstatements=\\\"\" . (int)\$m[\"coveredstatements\"] . \"\\\"\";
" 2>/dev/null' 2>/dev/null | tr -d '\r')
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

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Mutation" 150
$BACK 'php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/pest --mutate --parallel --everything --covered-only --min=0 > /tmp/ci-mutate.txt 2>&1' >/dev/null 2>&1
stop_countdown
MSI_SCORE=$($BACK 'sed "s/\x1b\[[0-9;]*[a-zA-Z]//g" /tmp/ci-mutate.txt | grep -oE "Score:[[:space:]]+[0-9]+\.[0-9]+" | grep -oE "[0-9]+\.[0-9]+" | head -1' 2>/dev/null | tr -dc '0-9.')
MSI_TESTED=$($BACK 'sed "s/\x1b\[[0-9;]*[a-zA-Z]//g" /tmp/ci-mutate.txt | grep -oE "[0-9]+ tested" | grep -oE "[0-9]+" | head -1' 2>/dev/null | tr -dc '0-9')
MSI_UNTESTED=$($BACK 'sed "s/\x1b\[[0-9;]*[a-zA-Z]//g" /tmp/ci-mutate.txt | grep -oE "[0-9]+ untested" | grep -oE "[0-9]+" | head -1' 2>/dev/null | tr -dc '0-9')
if [ -n "$MSI_SCORE" ]; then
  MSI_TOTAL=$((${MSI_TESTED:-0} + ${MSI_UNTESTED:-0}))
  ok "Mutation" "MSI: ${MSI_SCORE}% (${MSI_TESTED:-0}/${MSI_TOTAL})"
else
  warn "Mutation" "N/A"
fi

# ── FRONTEND ─────────────────────────────────────

echo ""
printf "${BLUE}  FRONTEND ${DIM}TypeScript / Vue / Vitest${RESET}\n\n"

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Lint" 5
$FRONT 'pnpm lint 2>&1; echo $? > /tmp/ci-rc.txt' >/dev/null 2>&1
stop_countdown
BG_RC=$($FRONT 'cat /tmp/ci-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Lint" "ESLint OK"
else
  fail "Lint" "ESLint issues"
  hint "lint-frontend"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Format" 10
$FRONT 'pnpm format:check  2>&1; echo $? > /tmp/ci-rc.txt' >/dev/null 2>&1
stop_countdown
BG_RC=$($FRONT 'cat /tmp/ci-rc.txt' 2>/dev/null | tr -dc '0-9')
if [ "${BG_RC:-1}" -eq 0 ]; then
  ok "Format" "Prettier OK"
else
  fail "Format" "Prettier issues"
  hint "lint-frontend"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Tests" 5
$FRONT 'pnpm vitest run > /tmp/ci-vitest.txt 2>&1' >/dev/null 2>&1
stop_countdown
VITEST_OUT=$($FRONT 'cat /tmp/ci-vitest.txt' 2>/dev/null)
VITEST_TESTS=$(echo "$VITEST_OUT" | grep "Tests" | grep -oE "[0-9]+ passed" | head -1)
if [ -n "$VITEST_TESTS" ]; then
  ok "Tests" "Vitest ${VITEST_TESTS}"
else
  fail "Tests" "Vitest failures"
  hint "test-frontend"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Coverage" 10
$FRONT 'pnpm vitest run --coverage --reporter=dot > /tmp/ci-fcov.txt 2>&1' >/dev/null 2>&1
stop_countdown
FCOV_OUT=$($FRONT 'cat /tmp/ci-fcov.txt' 2>/dev/null)
FCOV=$(echo "$FCOV_OUT" | grep "All files" | grep -oE "[0-9]+\.[0-9]+" | head -1)
if [ -n "$FCOV" ]; then
  ok "Coverage" "${FCOV}%"
else
  warn "Coverage" "N/A"
fi

TOTAL=$((TOTAL + 1)); START_T=$(date +%s); start_countdown "Mutation" 120
$FRONT 'pnpm mutation > /tmp/ci-stryker.txt 2>&1' >/dev/null 2>&1
stop_countdown
FMSI_LINE=$($FRONT 'sed "s/\x1b\[[0-9;]*[a-zA-Z]//g" /tmp/ci-stryker.txt | grep "All files"' 2>/dev/null | tr -d '\r')
FMSI_SCORE=$(echo "$FMSI_LINE" | awk -F'|' '{gsub(/ /,"",$2); print $2}')
FMSI_KILLED=$(echo "$FMSI_LINE" | awk -F'|' '{gsub(/ /,"",$4); print $4}')
FMSI_SURVIVED=$(echo "$FMSI_LINE" | awk -F'|' '{gsub(/ /,"",$6); print $6}')
if [ -n "$FMSI_SCORE" ] && [ "$FMSI_SCORE" != "" ]; then
  FMSI_TOTAL=$(( ${FMSI_KILLED:-0} + ${FMSI_SURVIVED:-0} ))
  ok "Mutation" "MSI: ${FMSI_SCORE}% (${FMSI_KILLED:-0}/${FMSI_TOTAL})"
else
  warn "Mutation" "N/A"
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
