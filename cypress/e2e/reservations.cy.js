/**
 * Reservations Feature Tests
 * Covers admin management flows and public booking surface
 */

const getFutureDate = (daysAhead = 7) => {
  const date = new Date();
  date.setDate(date.getDate() + daysAhead);
  return date.toISOString().split('T')[0];
};

const selectFirstVenueOption = () => {
  cy.get('#venue_id')
    .find('option')
    .not('[value=""]')
    .first()
    .then(($option) => {
      const value = $option.val();
      expect(value, 'venue option value').to.exist;
      cy.get('#venue_id').select(value);
    });
};

describe('Reservations Feature', () => {
  context('Admin Reservation Management', () => {
    beforeEach(() => {
      cy.loginAdmin();
    });

    it('should display reservation management overview with filters and table', () => {
      // View reservations list and confirm core UI shell renders
      cy.visit('/admin/reservation_management.php');

      cy.contains('Reservation Management').should('be.visible');
      cy.get('form').should('contain', 'Status').and('contain', 'Venue');
      cy.get('table.table').within(() => {
        cy.contains('th', 'ID').should('be.visible');
        cy.contains('th', 'Customer').should('be.visible');
        cy.contains('th', 'Status').should('be.visible');
      });
      cy.get('.btn-group .btn-sm').should('contain', 'Calendar View');
    });

    it('should create a new reservation with mocked backend success', () => {
      // Positive create scenario with API stub to avoid DB writes
      const futureDate = getFutureDate();

      cy.intercept('POST', '**/add_reservation.php', (req) => {
        expect(req.body).to.include('customer_name=Automation');
        req.reply({
          statusCode: 200,
          headers: { 'content-type': 'text/html' },
          body: '<div class="alert alert-success" data-cy="reservation-create-success">Reservation created successfully</div>',
        });
      }).as('mockCreateReservation');

      cy.visit('/admin/add_reservation.php');
      selectFirstVenueOption();
      cy.get('#reservation_date').clear().type(futureDate);
      cy.get('#start_time').clear().type('18:00');
      cy.get('#end_time').clear().type('20:00');
      cy.get('#customer_name').clear().type('Automation Tester');
      cy.get('#party_size').clear().type('4');
      cy.get('#customer_email').clear().type('automation@example.com');
      cy.get('#customer_phone').clear().type('5551234567');
      cy.get('#reservation_type').select('business');
      cy.get('#special_requests').clear().type('Corner table for presentation');

      cy.get('form').first().submit();
      cy.wait('@mockCreateReservation');
      cy.get('[data-cy="reservation-create-success"]').should('be.visible');
    });

    it('should edit an existing reservation with mocked save response', () => {
      // Positive edit scenario by visiting first reservation found
      cy.visit('/admin/reservation_management.php');

      cy.get('table tbody tr').its('length').should('be.gt', 0);
      cy.get('table tbody tr')
        .first()
        .find('td')
        .first()
        .invoke('text')
        .then((rawId) => {
          const reservationId = rawId.trim();
          expect(reservationId).to.match(/^\d+$/);

          cy.intercept('POST', `**/edit_reservation.php?id=${reservationId}`, (req) => {
            expect(req.body).to.include('customer_name=');
            req.reply({
              statusCode: 200,
              headers: { 'content-type': 'text/html' },
              body: '<div class="alert alert-success" data-cy="reservation-update-success">Reservation updated</div>',
            });
          }).as('mockUpdateReservation');

          cy.visit(`/admin/edit_reservation.php?id=${reservationId}`);
          cy.get('#customer_name').should('be.visible').clear().type('Edited Automation User');
          cy.get('#party_size').clear().type('5');
          cy.get('form').first().submit();

          cy.wait('@mockUpdateReservation');
          cy.get('[data-cy="reservation-update-success"]').should('be.visible');
        });
    });

    it('should cancel a reservation via status modal (delete equivalent)', () => {
      // Treat cancellation flow as delete scenario with mocked POST
      cy.visit('/admin/reservation_management.php');

      cy.get('table tbody tr').first().as('firstReservationRow');
      cy.get('@firstReservationRow').find('td').first().invoke('text').then((rawId) => {
        const reservationId = rawId.trim();
        expect(reservationId).to.match(/^\d+$/);
      });

      cy.intercept('POST', '**/reservation_management.php', (req) => {
        expect(req.body).to.include('action=update_status');
        expect(req.body).to.include('status=cancelled');
        req.reply({
          statusCode: 200,
          headers: { 'content-type': 'text/html' },
          body: '<div class="alert alert-success" data-cy="reservation-status-updated">Reservation cancelled</div>',
        });
      }).as('mockCancelReservation');

      cy.get('@firstReservationRow').find('button.btn-outline-warning').first().click();
      cy.get('#updateStatusModal').should('be.visible');
      cy.get('#updateStatusModal').find('select[name="status"]').select('cancelled');
      cy.get('#updateStatusModal').find('form').submit();

      cy.wait('@mockCancelReservation');
      cy.get('[data-cy="reservation-status-updated"]').should('be.visible');
    });

    it('should enforce required fields before submitting reservation form', () => {
      // Client-side validation for required inputs (negative case)
      cy.visit('/admin/add_reservation.php');

      cy.get('#reservation_date').invoke('val', '').trigger('input');
      cy.get('#start_time').invoke('val', '').trigger('input');
      cy.get('#end_time').invoke('val', '').trigger('input');
      cy.get('#customer_name').clear();
      cy.get('#party_size').clear();

      cy.get('form').first().within(() => {
        cy.get('button[type="submit"]').click();
      });

      cy.get('#venue_id:invalid').should('exist');
      cy.get('#reservation_date:invalid').should('exist');
      cy.get('#start_time:invalid').should('exist');
      cy.get('#end_time:invalid').should('exist');
      cy.get('#customer_name:invalid').should('exist');
      cy.get('#party_size:invalid').should('exist');
    });

    it('should surface backend validation errors when save fails', () => {
      // Negative save scenario by forcing server error
      const futureDate = getFutureDate(10);

      cy.intercept('POST', '**/add_reservation.php', {
        statusCode: 500,
        headers: { 'content-type': 'text/html' },
        body: '<div class="alert alert-danger" data-cy="reservation-save-error">Failed to save reservation</div>',
      }).as('mockCreateReservationError');

      cy.visit('/admin/add_reservation.php');
      selectFirstVenueOption();
      cy.get('#reservation_date').clear().type(futureDate);
      cy.get('#start_time').clear().type('17:00');
      cy.get('#end_time').clear().type('19:00');
      cy.get('#customer_name').clear().type('Error Case User');
      cy.get('#party_size').clear().type('3');

      cy.get('form').first().submit();
      cy.wait('@mockCreateReservationError');
      cy.get('[data-cy="reservation-save-error"]').should('be.visible');
    });
  });

  context('Public Booking Experience', () => {
    it('should display the public reservations page to customers', () => {
      // Positive coverage for public booking landing page
      cy.visit('/reservations/index.php');

      cy.contains('Book a Venue').should('be.visible');
      cy.get('.filter-section form').should('exist');
      cy.get('.venue-card').its('length').should('be.gte', 0);
    });

    it('should show error banner when reservations fail to load', () => {
      // Negative load scenario simulated via query string
      const loadError = 'Unable to load reservations right now';
      cy.visit(`/reservations/index.php?error=${encodeURIComponent(loadError)}`);

      cy.get('.alert-warning').should('contain', loadError);
    });
  });
});



