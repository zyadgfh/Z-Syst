#!/bin/bash

# Test Coverage Script for Z-Syst Pharmacy Management System

echo "🧪 Running Test Coverage Analysis..."
echo "===================================="

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Run tests with coverage
echo -e "${YELLOW}Running PHPUnit with coverage...${NC}"
vendor/bin/phpunit --coverage-html=coverage/html --coverage-text=coverage.txt

# Check if tests passed
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed!${NC}"
    exit 1
fi

# Generate coverage report
echo -e "${YELLOW}Generating coverage report...${NC}"
if [ -f "coverage.txt" ]; then
    echo "Coverage Summary:"
    cat coverage.txt
fi

# Open coverage report in browser (optional)
if [ "$1" == "--open" ]; then
    echo -e "${YELLOW}Opening coverage report in browser...${NC}"
    start coverage/html/index.html
fi

echo -e "${GREEN}✓ Coverage analysis complete!${NC}"
echo "Coverage report available at: coverage/html/index.html"
