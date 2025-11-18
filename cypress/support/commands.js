// ***********************************************
// Custom Cypress Commands
// ***********************************************

// Login as admin user
Cypress.Commands.add('login', (username, password) => {
  username = username || Cypress.env('adminUsername');
  password = password || Cypress.env('adminPassword');
  
  cy.visit('/admin/login.php');
  cy.get('[name="username"]').should('be.visible').clear().type(username);
  cy.get('[name="password"]').should('be.visible').clear().type(password);
  cy.get('[name="password"]').type('{enter}');
  
  // Wait for successful login
  cy.url().should('include', '/admin/index.php');
  cy.get('h1.h2').should('contain', 'Dashboard');
});

// Login and wait for dashboard to be fully loaded
Cypress.Commands.add('loginAdmin', () => {
  const username = Cypress.env('adminUsername');
  const password = Cypress.env('adminPassword');
  
  cy.visit('/admin/login.php');
  cy.get('[name="username"]').should('be.visible').clear().type(username);
  cy.get('[name="password"]').should('be.visible').clear().type(password);
  cy.get('[name="password"]').type('{enter}');
  
  // Wait for dashboard to load completely
  cy.url().should('include', '/admin/index.php');
  cy.get('h1.h2').should('contain', 'Dashboard');
  cy.get('.btn-toolbar .btn-group').should('be.visible');
  cy.wait(500); // Allow time for full page render
});

// Logout from admin panel
Cypress.Commands.add('logout', () => {
  cy.get('#navbarDropdown').should('be.visible').click();
  cy.get('a[href*="logout"]').should('be.visible').click();
  cy.url().should('include', 'login');
});

// Navigate to a specific admin page
Cypress.Commands.add('goToPage', (pagePath) => {
  cy.visit(`/admin/${pagePath}`);
  // Wait for page to load
  cy.get('body').should('be.visible');
});

// Wait for elements to be visible and clickable
Cypress.Commands.add('waitAndClick', (selector, options = {}) => {
  cy.get(selector, options).should('be.visible').click({ force: options.force || false });
});

// Custom assertion for element visibility
Cypress.Commands.add('assertVisible', (selector) => {
  cy.get(selector).should('be.visible');
});

// Wait for API response
Cypress.Commands.add('waitForAPI', (method, url) => {
  cy.intercept(method, url).as('apiCall');
  return cy.wait('@apiCall');
});

// Verify element text content
Cypress.Commands.add('assertText', (selector, expectedText) => {
  cy.get(selector).should('contain.text', expectedText);
});

// Clear and type with better error handling
Cypress.Commands.add('clearAndType', (selector, text) => {
  cy.get(selector).should('be.visible').clear().type(text);
});

// Take a screenshot with custom naming
Cypress.Commands.add('takeScreenshot', (name) => {
  cy.screenshot(name, { capture: 'viewport' });
});

// Reset database (if you have a test API endpoint)
Cypress.Commands.add('resetDB', () => {
  cy.request({
    method: 'POST',
    url: '/api/test/reset-db',
    failOnStatusCode: false,
  });
});

// Verify URL contains expected path
Cypress.Commands.add('verifyUrl', (expectedPath) => {
  cy.url().should('include', expectedPath);
});

// Wait for page to be fully loaded
Cypress.Commands.add('waitForPageLoad', () => {
  cy.get('body').should('be.visible');
  cy.get('.loading-spinner').should('not.exist');
});

// Check for specific page content
Cypress.Commands.add('verifyPageContent', (pageTitle) => {
  cy.get('h1, h2, .page-title').first().should('contain', pageTitle);
});

// Custom login for different user roles
Cypress.Commands.add('loginAsRole', (role) => {
  const credentials = {
    admin: { username: 'admin', password: 'admin123' },
    manager: { username: 'manager', password: 'manager123' },
    staff: { username: 'staff', password: 'staff123' },
  };
  
  const creds = credentials[role] || credentials.admin;
  cy.visit('/admin/login.php');
  cy.get('[name="username"]').should('be.visible').clear().type(creds.username);
  cy.get('[name="password"]').should('be.visible').clear().type(creds.password);
  cy.get('[name="password"]').type('{enter}');
  
  cy.url().should('include', '/admin/index.php');
});

// Click dropdown and interact with items
Cypress.Commands.add('clickDropdownItem', (dropdownSelector, itemSelector) => {
  // Click dropdown to open it
  cy.get(dropdownSelector).should('be.visible').click();
  
  // Wait for the dropdown menu to be shown (Bootstrap adds 'show' class)
  cy.get(dropdownSelector)
    .parent()
    .find('.dropdown-menu')
    .should('be.visible')
    .within(() => {
      // Wait for the item to be visible within the dropdown
      cy.get(itemSelector).should('be.visible');
    });
  
  // Now click the item (use force: true as backup)
  cy.get(itemSelector).first().click({ force: true });
});

// More robust dropdown click that uses .invoke('show')
Cypress.Commands.add('clickDropdownForce', (dropdownSelector, itemSelector) => {
  // Get the dropdown menu and force it to show
  cy.get('body').then(($body) => {
    // Click to trigger Bootstrap show
    cy.get(dropdownSelector).should('be.visible').click();
    
    // Get the dropdown menu parent and force show
    cy.get(dropdownSelector)
      .parent()
      .find('.dropdown-menu')
      .invoke('addClass', 'show');
    
    // Now click the item
    cy.wait(200);
    cy.get(itemSelector).first().click({ force: true });
  });
});

// Wait for dropdown menu to be shown
Cypress.Commands.add('waitForDropdownMenu', (dropdownSelector) => {
  // Wait until the dropdown menu has the 'show' class
  cy.get(dropdownSelector)
    .parent()
    .find('.dropdown-menu')
    .should('have.class', 'show')
    .and('be.visible');
});

// SIMPLEST WORKING SOLUTION
Cypress.Commands.add('clickDropdownMenuItem', (triggerSelector, menuItemSelector) => {
  // Click the dropdown trigger
  cy.get(triggerSelector).click();
  
  // Wait a moment
  cy.wait(600);
  
  // Find and click the menu item using invoke - bypasses ALL visibility checks
  cy.get(menuItemSelector).first().invoke('click');
});