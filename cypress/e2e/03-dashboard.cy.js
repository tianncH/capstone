/**
 * Dashboard Tests
 * Tests admin dashboard functionality, charts, and data display
 */

describe('Admin Dashboard Tests', () => {
  beforeEach(() => {
    // Login before each test
    cy.loginAdmin();
  });

  it('should display all dashboard stats cards', () => {
    // Verify today's sales card
    cy.get('.stat-card.sales').should('be.visible');
    cy.get('.stat-card.orders').should('be.visible');
    cy.get('.stat-card.items').should('be.visible');
    cy.get('.stat-card.customers').should('be.visible');
  });

  it('should display sales information', () => {
    // Check for sales data
    cy.get('div:contains("Today\'s Sales")').should('be.visible');
    cy.get('div:contains("Monthly Sales")').should('be.visible');
    cy.get('div:contains("Yearly Sales")').should('be.visible');
  });

  it('should display sales chart', () => {
    // Verify chart canvas exists
    cy.get('#salesChart').should('exist');
    
    // Wait for chart to be fully rendered
    cy.wait(1000);
    
    // Verify chart is within the card
    cy.get('.chart-container').should('be.visible');
  });

  it('should display booking statistics', () => {
    // Check booking stats card
    cy.get('.card:contains("Booking Statistics")').should('be.visible');
    cy.contains('Total Bookings').should('be.visible');
    cy.contains('Pending').should('be.visible');
    cy.contains('Confirmed').should('be.visible');
  });

  it('should display recent orders table', () => {
    // Verify recent orders section
    cy.contains('Recent Orders').should('be.visible');
    cy.get('.table').should('be.visible');
    
    // Check for table headers
    cy.get('th:contains("Order #")').should('be.visible');
    cy.get('th:contains("Table")').should('be.visible');
    cy.get('th:contains("Date & Time")').should('be.visible');
    cy.get('th:contains("Status")').should('be.visible');
  });

  it('should display popular items', () => {
    // Check for popular items section
    cy.contains('Popular Items').should('be.visible');
    
    // Verify list group exists
    cy.get('.list-group').should('be.visible');
  });

  it('should refresh dashboard data', () => {
    // Click refresh button
    cy.get('button[onclick*="location.reload"]').click();
    
    // Wait for page reload
    cy.wait(1000);
    
    // Verify dashboard is still loaded
    cy.get('h1.h2').should('contain', 'Dashboard');
  });

  it('should navigate to detailed reports from dashboard', () => {
    // Click on detailed reports link
    cy.get('a[href="sales_reports.php"]').should('be.visible').click();
    
    // Verify navigation
    cy.url().should('include', '/admin/sales_reports.php');
  });

  it('should navigate to order history from dashboard', () => {
    // Scroll to recent orders section
    cy.contains('View All').scrollIntoView();
    
    // Click view all orders
    cy.get('a[href*="order_history"]').click();
    
    // Verify navigation
    cy.waitForPageLoad();
  });
});



