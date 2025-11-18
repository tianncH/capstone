// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using CommonJS syntax
import './commands.js'

// Handle uncaught exceptions
Cypress.on('uncaught:exception', (err, runnable) => {
  // returning false here prevents Cypress from failing the test
  if (err.message.includes('Chart is not defined')) {
    return false
  }
  // Prevent failing on known errors
  if (err.message.includes('ResizeObserver loop')) {
    return false
  }
  if (err.message.includes('Non-Error promise rejection')) {
    return false
  }
})

// Override Cypress visibility checks for Bootstrap dropdowns
Cypress.Commands.overwrite('click', (originalFn, subject, options = {}) => {
  // If clicking a dropdown item, skip the visibility check
  if (subject && subject.length && subject.hasClass('dropdown-item')) {
    options = { ...options, force: true }
  }
  return originalFn(subject, options)
})
  
// cypress/support/e2e.js
afterEach(() => {
  cy.screenshot(); // take a screenshot after every test automatically
});
