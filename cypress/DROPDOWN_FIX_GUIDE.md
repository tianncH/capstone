# Cypress Bootstrap Dropdown Fix Guide

## Problem

When interacting with Bootstrap dropdown menus in Cypress, you get this error:

```
Timed out retrying after 10000ms: expected '<a.dropdown-item>' to be 'visible'
This element <a.dropdown-item> is not visible because its parent <ul.dropdown-menu> has CSS property: display: none
```

## Root Cause

Bootstrap dropdowns are hidden by default (`display: none`) and only become visible after:
1. The dropdown trigger is clicked
2. Bootstrap JavaScript adds the `show` class
3. CSS transitions complete

Cypress tries to interact with the menu item **before** it becomes fully visible, causing timeouts.

## Solution

### Custom Command: `clickDropdownMenuItem`

We created a reliable custom command that:

1. ✅ Clicks the dropdown trigger
2. ✅ **Waits for the dropdown menu to have the `show` class**
3. ✅ **Waits for the dropdown menu to be visible**
4. ✅ Adds a small delay for CSS animations
5. ✅ Clicks the menu item

### Usage

```javascript
// Basic usage
cy.clickDropdownMenuItem('#myDropdown', '.dropdown-item');

// With selector for specific item
cy.clickDropdownMenuItem('#managementDropdown', 'a[href*="order"]');

// With options
cy.clickDropdownMenuItem('#myDropdown', '.dropdown-item', { force: true });
```

### Implementation

```javascript
// In cypress/support/commands.js
Cypress.Commands.add('clickDropdownMenuItem', (triggerSelector, menuItemSelector, options = {}) => {
  // Step 1: Click the dropdown trigger
  cy.get(triggerSelector).should('be.visible').click({ force: options.force || false });
  
  // Step 2: Wait for Bootstrap to add 'show' class AND make it visible
  cy.get('.dropdown-menu.show', { timeout: 5000 })
    .should('be.visible')
    .should('have.class', 'show');
  
  // Step 3: Wait for CSS animations
  cy.wait(200);
  
  // Step 4: Click the menu item - now it should be visible
  cy.get(menuItemSelector)
    .first()
    .should('be.visible')
    .click({ force: options.force || false });
});
```

## Why This Works

### Key Changes:

1. **Wait for `.show` class**: Bootstrap adds this class when the dropdown is open
   ```javascript
   cy.get('.dropdown-menu.show', { timeout: 5000 })
   ```

2. **Check visibility AFTER show class**: Only interact when truly visible
   ```javascript
   .should('be.visible')
   ```

3. **Use within scope**: Ensures we're checking the right dropdown
   ```javascript
   cy.get(triggerSelector).click();
   cy.get('.dropdown-menu.show').should('be.visible');
   ```

## Alternative Solutions

### Option 1: Navigate Directly (Simple but Less Test Coverage)

```javascript
// Instead of clicking dropdown → clicking item
// Just navigate to the page
cy.visit('/admin/order_management.php');
```

**Pros**: Simple, reliable  
**Cons**: Doesn't test the dropdown functionality itself

### Option 2: Test Dropdown Opens/Closes Only

```javascript
// Test that dropdown opens and closes
cy.get('#managementDropdown').click();
cy.get('.dropdown-menu').should('exist');
cy.get('#managementDropdown').click(); // Close it
```

**Pros**: Tests basic dropdown functionality  
**Cons**: Doesn't test clicking items within the dropdown

### Option 3: Custom Command with Force Click (Not Recommended)

```javascript
// Force click without waiting for visibility
cy.get('#dropdown').click();
cy.wait(500);
cy.get('.dropdown-item').first().click({ force: true });
```

**Pros**: Quick fix  
**Cons**: Bypasses Cypress safety checks, can be flaky

## Best Practices

### ✅ DO

1. Wait for the dropdown menu to have the `show` class
2. Wait for visibility before clicking
3. Use `cy.get('.dropdown-menu.show')` to find open dropdowns
4. Add small delays for CSS animations
5. Use the custom command for consistency

### ❌ DON'T

1. Don't use `force: true` without waiting for the dropdown to open
2. Don't skip the visibility check
3. Don't assume `cy.wait()` is enough - check for actual visibility
4. Don't interact with elements before the dropdown is ready

## Example: Complete Test

```javascript
describe('Navigation Tests', () => {
  beforeEach(() => {
    cy.loginAdmin();
  });

  it('should navigate via dropdown menu', () => {
    // Use the custom command
    cy.clickDropdownMenuItem(
      '#managementDropdown',      // Dropdown trigger
      'a[href*="order"]'          // Menu item to click
    );
    
    // Verify navigation
    cy.url().should('include', '/admin/order');
    cy.get('h1').should('contain', 'Order Management');
  });

  it('should handle multiple dropdowns', () => {
    // Open dropdown 1
    cy.clickDropdownMenuItem('#dropdown1', '.item-1');
    
    // Go back
    cy.go('back');
    
    // Open dropdown 2
    cy.clickDropdownMenuItem('#dropdown2', '.item-2');
  });
});
```

## Troubleshooting

### Issue: "Expected element to be visible"

**Fix**: Make sure you're waiting for `.show` class AND visibility:
```javascript
cy.get('.dropdown-menu.show').should('be.visible');
```

### Issue: "Multiple elements found"

**Fix**: Use `.first()` or a more specific selector:
```javascript
cy.get('.dropdown-menu').first().should('be.visible');
```

### Issue: Dropdown doesn't open

**Fix**: 
1. Check if Bootstrap JavaScript is loaded
2. Verify the dropdown trigger selector is correct
3. Check browser console for errors

## Summary

The fix involves:
1. **Creating a custom command** that properly waits for the dropdown
2. **Checking for `.show` class** before interacting
3. **Waiting for visibility** before clicking
4. **Using the command consistently** across tests

This ensures reliable, maintainable tests that work with Bootstrap dropdowns! ✅



