# White-Box Testing Suite

## Overview

This directory contains comprehensive white-box tests for the restaurant ordering system. White-box testing focuses on testing internal functions, logic branches, conditions, and edge cases that are not covered by black-box E2E tests.

## Test Structure

### 1. PHP Unit Tests (`php_unit_tests.php`)

Tests internal PHP functions and classes:

- **Currency Functions**: `formatPeso()`, `formatPesoWhole()`, `parsePeso()`, `isValidPeso()`
- **Discount Functions**: `DiscountManager` class methods, helper functions
- **Cash Float Functions**: `setCashFloat()`, `getCashFloat()`, `calculateCashVariance()`

**Usage:**
```bash
# Run all tests
curl http://localhost/capstone/tests/whitebox/php_unit_tests.php?test=all

# Run specific test
curl http://localhost/capstone/tests/whitebox/php_unit_tests.php?test=formatPeso_basic
```

### 2. JavaScript Unit Tests (`js_unit_tests.html`)

Tests frontend JavaScript functions:

- Currency formatting (`formatCurrencyInput()`, `parseCurrencyInput()`)
- Date/time validation
- Email validation
- Number validation
- String validation

**Usage:**
Open `js_unit_tests.html` in a browser. Tests run automatically on page load.

### 3. Cypress White-Box Tests (`whitebox-tests.cy.js`)

Integration tests that verify internal logic through the UI:

- Reservation validation logic
- Discount calculation
- Cash float management
- Order processing
- Data validation edge cases
- Error handling paths
- Branch coverage

**Usage:**
```bash
npx cypress run --spec cypress/e2e/whitebox-tests.cy.js
```

## Test Coverage

### Functions Tested

#### Currency Functions
- ✅ `formatPeso()` - All branches (with/without symbol, zero, negative, large numbers)
- ✅ `formatPesoWhole()` - Whole number formatting, decimal rounding
- ✅ `parsePeso()` - String parsing with/without symbol
- ✅ `isValidPeso()` - Validation logic, negative numbers

#### Discount Functions
- ✅ `DiscountManager::calculateDiscount()` - Success, minimum amount, invalid type, maximum limit
- ✅ `formatDiscountDisplay()` - All discount types
- ✅ `getDiscountBadgeClass()` - CSS class mapping

#### Cash Float Functions
- ✅ `setCashFloat()` - Success, duplicate prevention
- ✅ `getCashFloat()` - Found, not found
- ✅ `calculateCashVariance()` - Error handling

### Branch Coverage

All conditional branches in tested functions are covered:
- Success paths
- Error paths
- Edge cases
- Boundary conditions

### Edge Cases Tested

- Zero values
- Negative numbers
- Large numbers
- Invalid inputs
- Empty strings
- Null values
- Boundary values
- Duplicate operations

## Running Tests

### Run All Tests

1. **PHP Tests:**
   ```bash
   # Via browser
   http://localhost/capstone/tests/whitebox/php_unit_tests.php?test=all
   
   # Via curl
   curl http://localhost/capstone/tests/whitebox/php_unit_tests.php?test=all
   ```

2. **JavaScript Tests:**
   ```bash
   # Open in browser
   http://localhost/capstone/tests/whitebox/js_unit_tests.html
   ```

3. **Cypress Tests:**
   ```bash
   npx cypress run --spec cypress/e2e/whitebox-tests.cy.js
   ```

### Generate Report

```bash
# Open in browser
http://localhost/capstone/tests/whitebox/generate_report.php
```

## Test Results

### Expected Pass Rate

- **Target:** 80%+ pass rate
- **Currency Functions:** 100% (all deterministic)
- **Discount Functions:** Depends on database state
- **Cash Float Functions:** Depends on database state

### Interpreting Results

- **Passed Tests:** Functionality works correctly
- **Failed Tests:** Issues that need attention
  - Check expected vs actual values
  - Review error messages
  - Verify database state for database-dependent tests

## Adding New Tests

### PHP Tests

Add test function to `php_unit_tests.php`:

```php
function test_myFunction_basic() {
    $result = myFunction('input');
    return [
        'passed' => $result === 'expected',
        'message' => 'Description of what is tested',
        'expected' => 'expected',
        'actual' => $result
    ];
}
```

Then add to test execution list.

### JavaScript Tests

Add test function to `js_unit_tests.html`:

```javascript
function test_myFunction_basic() {
    const result = myFunction('input');
    return assert(
        result === 'expected',
        'Description of what is tested',
        'expected',
        result
    );
}
```

Add to `testFunctions` array in `runAllTests()`.

### Cypress Tests

Add test to `whitebox-tests.cy.js`:

```javascript
it('should test my function logic', () => {
    // Test implementation
});
```

## Best Practices

1. **Isolation:** Each test should be independent
2. **Determinism:** Tests should produce consistent results
3. **Coverage:** Test all branches and edge cases
4. **Documentation:** Comment what each test covers and why
5. **Cleanup:** Tests should clean up after themselves

## Troubleshooting

### PHP Tests Failing

- Check database connection
- Verify test data exists (discount_config, etc.)
- Check function dependencies

### JavaScript Tests Failing

- Open browser console for errors
- Verify functions are accessible
- Check for syntax errors

### Cypress Tests Failing

- Verify server is running
- Check authentication (loginAdmin)
- Verify test data exists

## Maintenance

- Run tests regularly (before commits)
- Update tests when functions change
- Add tests for new functions
- Review and fix failing tests promptly

## Notes

- Tests do NOT modify existing code
- Tests are isolated and don't interfere with each other
- Database-dependent tests may require setup
- Some tests may fail if database state is unexpected (this is expected behavior)


