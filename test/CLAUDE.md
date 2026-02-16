# Test Suites

PHPUnit, Selenium, and acceptance tests for Dolibarr.

## Directory Structure

| Subdirectory | Purpose |
|--------------|---------|
| phpunit/ | PHPUnit test suites |
| phpunit/functional/ | Functional tests |
| acceptance/ | Acceptance tests |

## Running Tests

```bash
# Run all functional tests
htdocs/includes/bin/phpunit test/phpunit/functional

# Run specific test file
htdocs/includes/bin/phpunit test/phpunit/functional/SomeTest.php

# Run with coverage
htdocs/includes/bin/phpunit --coverage-html coverage/ test/phpunit/functional
```

## Test Database

| Property | Value |
|----------|-------|
| Database | dolibarr_test |
| User | dolibarr |
| Password | dolibarr |

## Writing Tests

See `/dolibarr-testing` skill for complete test patterns including:

- Test class template extending CommonClassTest
- Global state management (restoring $conf, $user, $langs, $db)
- Test dependencies with `@depends`
- Module preconditions in `setUpBeforeClass()`
- REST API testing patterns

## Configuration

PHPUnit configuration is in `dev/setup/phpunit/`.
