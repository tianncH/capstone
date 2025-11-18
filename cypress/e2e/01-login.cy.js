/**
 * Login Tests
 * Tests authentication flow with valid and invalid credentials
 */

describe('Admin Login Tests', () => {
  beforeEach(() => {
    // Visit admin login page before each test
    cy.visit('/admin/login.php');
  });

  it('should display login form elements', () => {
    // Verify login form is visible
    cy.get('[name="username"]').should('be.visible');
    cy.get('[name="password"]').should('be.visible');
    cy.get('button.w-100').should('be.visible');
  });

  it('should successfully login with valid credentials', () => {
    // Login using custom command
    cy.login();
    
    // Verify redirect to dashboard
    cy.url().should('include', '/admin/index.php');
    cy.get('h1.h2').should('contain', 'Dashboard');
  });

  it('should login with valid credentials and wait for dashboard', () => {
    // Use the enhanced login command
    cy.loginAdmin();
    
    // Additional verifications
    cy.get('.btn-toolbar').should('be.visible');
    cy.get('a[href="sales_reports.php"]').should('be.visible');
    cy.get('a[href="generate_reports.php"]').should('be.visible');
  });

  it('should fail login with invalid credentials', () => {
    // Attempt login with invalid credentials
    cy.get('[name="username"]').clear().type('invalid_user');
    cy.get('[name="password"]').clear().type('wrong_password');
    cy.get('[name="password"]').type('{enter}');
    
    // Should stay on login page
    cy.url().should('include', '/admin/login.php');
    
    // Check for error message (if displayed)
    // cy.get('.alert-danger').should('be.visible');
  });

  it('should fail login with empty fields', () => {
    // Try to submit empty form
    cy.get('[name="password"]').type('{enter}');
    
    // Should stay on login page
    cy.url().should('include', '/admin/login.php');
  });

  it('should logout successfully', () => {
    // Login first
    cy.login();
    
    // Perform logout
    cy.logout();
    
    // Should redirect to login page
    cy.url().should('include', 'login');
  });
});



