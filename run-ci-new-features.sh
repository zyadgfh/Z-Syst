#!/usr/bin/env bash
#
# run-ci-new-features.sh
# ─────────────────────────────────────────────────────────
# CI-friendly script that runs only the new feature tests:
#   - Accounting (double-entry bookkeeping API)
#   - Recall (drug recall & traceability workflow)
#
# Usage:
#   bash run-ci-new-features.sh              # Run all new feature tests
#   bash run-ci-new-features.sh --coverage   # Run with coverage report
#   bash run-ci-new-features.sh --group=accounting   # Only accounting tests
#   bash run-ci-new-features.sh --group=recall       # Only recall tests
#
# Exit codes:
#   0  All tests passed
#   1  One or more tests failed
# ─────────────────────────────────────────────────────────

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  CI Test Runner — New Features (Accounting & Recall)${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════${NC}"
echo ""

# Determine command
if [[ "${1:-}" == "--coverage" ]]; then
    CMD="php artisan test --testsuite=CI-NewFeatures --coverage --min=80"
    echo -e "${YELLOW}▶ Running with coverage (min 80%)...${NC}"
elif [[ "${1:-}" == --group=* ]]; then
    GROUP="${1#--group=}"
    CMD="php artisan test --group=${GROUP}"
    echo -e "${YELLOW}▶ Running group: ${GROUP}...${NC}"
else
    CMD="php artisan test --testsuite=CI-NewFeatures"
    echo -e "${YELLOW}▶ Running all new feature tests...${NC}"
fi

echo ""
echo -e "Command: ${CMD}"
echo ""

# Run tests
if $CMD 2>&1; then
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  ✅ All tests passed!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}═══════════════════════════════════════════════════════${NC}"
    echo -e "${RED}  ❌ Some tests failed!${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════${NC}"
    exit 1
fi
