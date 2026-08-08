#!/bin/bash

# Z-Syst Pharmacy Management System - Automation Script
# This script automates common development, testing, and deployment tasks

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Function: Install dependencies
install_dependencies() {
    print_header "Installing Dependencies"
    
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    print_success "Composer dependencies installed"
    
    echo "Installing NPM dependencies..."
    npm install
    print_success "NPM dependencies installed"
    
    echo "Building assets..."
    npm run build
    print_success "Assets built"
}

# Function: Run tests
run_tests() {
    print_header "Running Tests"
    
    echo "Running PHPUnit tests..."
    php artisan test
    print_success "Tests completed"
}

# Function: Run tests with coverage
run_coverage() {
    print_header "Running Test Coverage"
    
    echo "Running tests with coverage..."
    ./run-coverage.sh
    print_success "Coverage report generated"
}

# Function: Run security checks
run_security_checks() {
    print_header "Running Security Checks"
    
    echo "Running composer audit..."
    composer audit
    print_success "Security audit completed"
    
    echo "Running Laravel security check..."
    if [ -f "vendor/bin/security-checker" ]; then
        vendor/bin/security-checker
        print_success "Laravel security check completed"
    else
        print_warning "Security checker not installed"
    fi
}

# Function: Run code quality checks
run_code_quality() {
    print_header "Running Code Quality Checks"
    
    echo "Running PHPStan..."
    if [ -f "vendor/bin/phpstan" ]; then
        vendor/bin/phpstan analyse --memory-limit=2G
        print_success "PHPStan completed"
    else
        print_warning "PHPStan not installed"
    fi
    
    echo "Running Laravel Pint..."
    if [ -f "vendor/bin/pint" ]; then
        vendor/bin/pint --test
        print_success "Laravel Pint completed"
    else
        print_warning "Laravel Pint not installed"
    fi
}

# Function: Clear cache
clear_cache() {
    print_header "Clearing Cache"
    
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    print_success "Cache cleared"
}

# Function: Optimize cache
optimize_cache() {
    print_header "Optimizing Cache"
    
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    print_success "Cache optimized"
}

# Function: Run migrations
run_migrations() {
    print_header "Running Migrations"
    
    php artisan migrate --force
    print_success "Migrations completed"
}

# Function: Seed database
seed_database() {
    print_header "Seeding Database"
    
    php artisan db:seed --force
    print_success "Database seeded"
}

# Function: Create backup
create_backup() {
    print_header "Creating Backup"
    
    php artisan backup:run
    print_success "Backup created"
}

# Function: Deploy to staging
deploy_staging() {
    print_header "Deploying to Staging"
    
    echo "This will deploy to staging server"
    read -p "Are you sure? (y/n) " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Add your staging deployment commands here
        # Example: rsync -avz ./ user@staging-server:/var/www/z-syst/
        print_success "Deployed to staging"
    else
        print_warning "Deployment cancelled"
    fi
}

# Function: Deploy to production
deploy_production() {
    print_header "Deploying to Production"
    
    echo "⚠️  WARNING: This will deploy to production server"
    read -p "Are you sure? (y/n) " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Add your production deployment commands here
        # Example: rsync -avz ./ user@production-server:/var/www/z-syst/
        print_success "Deployed to production"
    else
        print_warning "Deployment cancelled"
    fi
}

# Function: Generate changelog
generate_changelog() {
    print_header "Generating Changelog"
    
    if command -v conventional-changelog &> /dev/null; then
        conventional-changelog -p angular -i CHANGELOG.md -s
        print_success "Changelog generated"
    else
        print_warning "conventional-changelog not installed"
        echo "Install with: npm install -g conventional-changelog-cli"
    fi
}

# Function: Setup project
setup_project() {
    print_header "Setting Up Project"
    
    echo "Copying .env.example to .env..."
    cp .env.example .env
    
    echo "Generating application key..."
    php artisan key:generate
    
    echo "Installing dependencies..."
    install_dependencies
    
    echo "Running migrations..."
    run_migrations
    
    echo "Seeding database..."
    seed_database
    
    echo "Creating storage link..."
    php artisan storage:link
    
    echo "Optimizing cache..."
    optimize_cache
    
    print_success "Project setup completed"
}

# Function: Show help
show_help() {
    echo "Z-Syst Pharmacy Management System - Automation Script"
    echo ""
    echo "Usage: ./automation.sh [command]"
    echo ""
    echo "Commands:"
    echo "  install         Install dependencies"
    echo "  test            Run tests"
    echo "  coverage        Run tests with coverage"
    echo "  security        Run security checks"
    echo "  quality         Run code quality checks"
    echo "  cache-clear     Clear cache"
    echo "  cache-optimize  Optimize cache"
    echo "  migrate         Run migrations"
    echo "  seed            Seed database"
    echo "  backup          Create backup"
    echo "  deploy-staging  Deploy to staging"
    echo "  deploy-prod     Deploy to production"
    echo "  changelog       Generate changelog"
    echo "  setup           Setup project (first time)"
    echo "  help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./automation.sh test"
    echo "  ./automation.sh deploy-staging"
    echo "  ./automation.sh setup"
}

# Main script logic
case "$1" in
    install)
        install_dependencies
        ;;
    test)
        run_tests
        ;;
    coverage)
        run_coverage
        ;;
    security)
        run_security_checks
        ;;
    quality)
        run_code_quality
        ;;
    cache-clear)
        clear_cache
        ;;
    cache-optimize)
        optimize_cache
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        seed_database
        ;;
    backup)
        create_backup
        ;;
    deploy-staging)
        deploy_staging
        ;;
    deploy-prod)
        deploy_production
        ;;
    changelog)
        generate_changelog
        ;;
    setup)
        setup_project
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac
