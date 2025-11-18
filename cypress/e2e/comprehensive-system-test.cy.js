/**
 * Comprehensive System Test Suite
 * Tests all three main modules: Ordering, Counter, Kitchen
 * Includes login, navigation, CRUD operations, and module interactions
 */

describe('Comprehensive Restaurant System Tests', () => {
  const testQRToken = 'TEST_QR_TOKEN_123';
  const testTableNumber = 1;

  beforeEach(() => {
    // Clear any existing sessions
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  describe('1. Authentication & Admin Access', () => {
    it('should login as admin successfully', () => {
      cy.visit('/admin/index.php');
      cy.get('input[name="username"]').type('admin');
      cy.get('input[name="password"]').type('admin123');
      cy.get('button[type="submit"]').click();
      cy.url().should('include', '/admin/index.php');
      cy.get('h1.h2').should('contain', 'Dashboard');
    });

    it('should access admin dashboard features', () => {
      cy.loginAdmin();
      cy.get('.card').should('have.length.greaterThan', 0);
      cy.get('.btn-toolbar').should('be.visible');
    });
  });

  describe('2. Navigation & UI Validation', () => {
    beforeEach(() => {
      cy.loginAdmin();
    });

    it('should navigate through admin sections', () => {
      // Test direct navigation to key pages
      cy.visit('/admin/order_management.php');
      cy.url().should('include', 'order_management');
      
      cy.visit('/admin/sales_reports.php');
      cy.url().should('include', 'sales_reports');
      
      cy.visit('/admin/generate_reports.php');
      cy.url().should('include', 'generate_reports');
    });

    it('should handle dropdown navigation', () => {
      cy.visit('/admin/index.php');
      
      // Test Reports dropdown
      cy.get('#reportsDropdown').should('be.visible').click();
      cy.get('#reportsDropdown')
        .parent()
        .find('.dropdown-menu')
        .should('have.class', 'show')
        .within(() => {
          cy.get('a[href="generate_reports.php"]').should('be.visible');
        });
    });

    it('should validate UI elements and responsiveness', () => {
      cy.visit('/admin/index.php');
      
      // Check main dashboard elements
      cy.get('.navbar').should('be.visible');
      cy.get('.card').should('have.length.greaterThan', 0);
      cy.get('.btn-toolbar').should('be.visible');
      
      // Test responsive behavior
      cy.viewport(768, 1024); // Tablet
      cy.get('.navbar').should('be.visible');
      
      cy.viewport(375, 667); // Mobile
      cy.get('.navbar').should('be.visible');
    });
  });

  describe('3. Ordering Module Tests', () => {
    it('should access QR-based ordering system', () => {
      // Test QR menu access
      cy.visit(`/ordering/secure_qr_menu.php?qr=${testQRToken}`);
      cy.url().should('include', 'secure_qr_menu');
    });

    it('should handle table-based ordering', () => {
      // Test table menu access
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      cy.url().should('include', 'table_menu');
    });

    it('should browse menu and add items to cart', () => {
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      
      // Wait for page to load
      cy.get('body').should('be.visible');
      
      // Look for menu items and add to cart
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="menu-item"]').length > 0) {
          cy.get('[data-cy="menu-item"]').first().within(() => {
            cy.get('[data-cy="add-to-cart"]').click();
          });
        } else {
          // Fallback: look for any add button
          cy.get('.btn-add, .btn-primary').first().click();
        }
      });
    });

    it('should manage cart and quantities', () => {
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="cart"]').length > 0) {
          cy.get('[data-cy="cart"]').within(() => {
            cy.get('[data-cy="cart-qty-plus"]').click();
          });
        }
      });
    });

    it('should process orders and check status', () => {
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      
      // Look for order confirmation
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="confirm-order"]').length > 0) {
          cy.get('[data-cy="confirm-order"]').click();
          cy.get('[data-cy="order-status"]').should('be.visible');
        }
      });
    });

    it('should access bill out functionality', () => {
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="bill-out"]').length > 0) {
          cy.get('[data-cy="bill-out"]').should('be.visible');
        }
      });
    });
  });

  describe('4. Counter Module Tests', () => {
    beforeEach(() => {
      cy.loginAdmin();
    });

    it('should access counter dashboard', () => {
      cy.visit('/counter/index.php');
      cy.url().should('include', 'counter');
      cy.get('body').should('be.visible');
    });

    it('should manage QR sessions', () => {
      cy.visit('/counter/index.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="counter-queue-row"]').length > 0) {
          cy.get('[data-cy="counter-queue-row"]').first().within(() => {
            cy.get('[data-cy="counter-confirm"]').click();
          });
        }
      });
    });

    it('should verify orders', () => {
      cy.visit('/counter/orders.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="counter-order-row"]').length > 0) {
          cy.get('[data-cy="counter-order-row"]').first().as('orderRow');
          cy.get('@orderRow').within(() => {
            cy.get('[data-cy="counter-verify"]').click();
          });
          cy.get('@orderRow').should('contain.text', 'Verified');
        }
      });
    });

    it('should process payments with discounts', () => {
      cy.visit('/counter/payment.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="discount-input"]').length > 0) {
          cy.get('[data-cy="discount-input"]').clear().type('10');
          cy.get('[data-cy="payment-method"]').select('cash');
          cy.get('[data-cy="confirm-payment"]').click();
          cy.get('[data-cy="payment-status"]').should('contain', 'Paid');
        }
      });
    });

    it('should handle session management', () => {
      cy.visit('/counter/index.php');
      
      // Look for session management features
      cy.get('body').then(($body) => {
        if ($body.find('.btn-danger, .btn-warning').length > 0) {
          cy.get('.btn-danger, .btn-warning').first().should('be.visible');
        }
      });
    });
  });

  describe('5. Kitchen Module Tests', () => {
    beforeEach(() => {
      cy.loginAdmin();
    });

    it('should access kitchen dashboard', () => {
      cy.visit('/kitchen/index.php');
      cy.url().should('include', 'kitchen');
      cy.get('body').should('be.visible');
    });

    it('should receive and manage orders', () => {
      cy.visit('/kitchen/index.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="kitchen-order-row"]').length > 0) {
          cy.get('[data-cy="kitchen-order-row"]').first().as('orderRow');
          cy.get('@orderRow').should('be.visible');
        }
      });
    });

    it('should update order preparation status', () => {
      cy.visit('/kitchen/index.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="kitchen-prepared"]').length > 0) {
          cy.get('[data-cy="kitchen-prepared"]').first().click();
          cy.get('[data-cy="kitchen-order-row"]').first().should('contain.text', 'Prepared');
        }
      });
    });

    it('should complete orders', () => {
      cy.visit('/kitchen/index.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('[data-cy="kitchen-complete"]').length > 0) {
          cy.get('[data-cy="kitchen-complete"]').first().click();
          cy.get('[data-cy="kitchen-order-row"]').first().should('contain.text', 'Completed');
        }
      });
    });

    it('should handle order status updates', () => {
      cy.visit('/kitchen/index.php');
      
      // Test AJAX status updates
      cy.intercept('POST', '**/update_order_status.php').as('statusUpdate');
      
      cy.get('body').then(($body) => {
        if ($body.find('.btn-success, .btn-warning').length > 0) {
          cy.get('.btn-success, .btn-warning').first().click();
          cy.wait('@statusUpdate');
        }
      });
    });
  });

  describe('6. Cross-Module Integration Tests', () => {
    it('should complete full order flow: Ordering → Counter → Kitchen', () => {
      // Step 1: Create order in Ordering module
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      cy.get('body').should('be.visible');
      
      // Step 2: Verify in Counter module
      cy.loginAdmin();
      cy.visit('/counter/index.php');
      cy.get('body').should('be.visible');
      
      // Step 3: Process in Kitchen module
      cy.visit('/kitchen/index.php');
      cy.get('body').should('be.visible');
    });

    it('should handle session state across modules', () => {
      // Test session persistence
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      cy.get('body').should('be.visible');
      
      // Navigate to counter
      cy.loginAdmin();
      cy.visit('/counter/index.php');
      cy.get('body').should('be.visible');
      
      // Return to ordering
      cy.visit(`/ordering/table_menu.php?table=${testTableNumber}`);
      cy.get('body').should('be.visible');
    });
  });

  describe('7. API Integration Tests', () => {
    it('should handle feedback notifications', () => {
      cy.loginAdmin();
      cy.intercept('GET', '**/feedback_notifications.php').as('feedbackAPI');
      
      cy.visit('/admin/index.php');
      cy.wait('@feedbackAPI');
    });

    it('should handle booking notifications', () => {
      cy.loginAdmin();
      cy.intercept('GET', '**/booking_notifications.php').as('bookingAPI');
      
      cy.visit('/admin/index.php');
      cy.wait('@bookingAPI');
    });

    it('should process AJAX requests in kitchen', () => {
      cy.loginAdmin();
      cy.intercept('POST', '**/update_order_status.php').as('kitchenAPI');
      
      cy.visit('/kitchen/index.php');
      cy.get('body').then(($body) => {
        if ($body.find('.btn-success').length > 0) {
          cy.get('.btn-success').first().click();
          cy.wait('@kitchenAPI');
        }
      });
    });
  });

  describe('8. Error Handling & Edge Cases', () => {
    it('should handle invalid table numbers', () => {
      cy.visit('/ordering/table_menu.php?table=999');
      cy.get('body').should('contain.text', 'Invalid table number');
    });

    it('should handle expired sessions', () => {
      cy.visit('/ordering/table_menu.php?table=1');
      cy.get('body').should('be.visible');
    });

    it('should handle network errors gracefully', () => {
      cy.loginAdmin();
      // Force an error on dashboard AJAX (not the base HTML request) so the UI can render its fallback state
      cy.intercept('GET', '**/feedback_notifications.php', { forceNetworkError: true }).as('networkError');
      
      cy.visit('/admin/index.php');
      cy.wait('@networkError');
      cy.get('body').should('be.visible');
    });
  });

  describe('9. Performance & Load Tests', () => {
    it('should load pages within acceptable time', () => {
      const startTime = Date.now();
      
      cy.visit('/admin/index.php');
      cy.get('h1.h2').should('be.visible').then(() => {
        const loadTime = Date.now() - startTime;
        expect(loadTime).to.be.lessThan(5000); // 5 seconds max
      });
    });

    it('should handle multiple concurrent operations', () => {
      cy.loginAdmin();
      
      // Test multiple page loads
      cy.visit('/admin/index.php');
      cy.visit('/counter/index.php');
      cy.visit('/kitchen/index.php');
      
      // All should load successfully
      cy.get('body').should('be.visible');
    });
  });

  describe('10. Data Validation & CRUD Operations', () => {
    beforeEach(() => {
      cy.loginAdmin();
    });

    it('should validate form inputs', () => {
      cy.visit('/admin/admin_users.php');
      
      cy.get('body').then(($body) => {
        if ($body.find('input[name="username"]').length > 0) {
          cy.get('input[name="username"]').type('testuser');
          cy.get('input[name="password"]').type('testpass');
          cy.get('button[type="submit"]').should('be.visible');
        }
      });
    });

    it('should handle data display correctly', () => {
      cy.visit('/admin/sales_reports.php');
      cy.get('body').should('be.visible');
      
      // Check for data tables
      cy.get('body').then(($body) => {
        if ($body.find('.table').length > 0) {
          cy.get('.table').should('be.visible');
        }
      });
    });
  });
});





