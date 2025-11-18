# Cypress E2E Testing Suite

Complete end-to-end testing setup for the Restaurant Ordering System using Cypress.

## 📁 Folder Structure

```
cypress/
├── e2e/                          # E2E test specs
│   ├── 01-login.cy.js            # Login authentication tests
│   ├── 02-navigation.cy.js        # Navigation flow tests
│   ├── 03-dashboard.cy.js        # Dashboard functionality tests
│   ├── 04-forms.cy.js             # Form interaction tests
│   ├── 05-crud-operations.cy.js   # CRUD operation tests
│   ├── 06-api-integration.cy.js   # API integration tests
│   └── 07-ui-validation.cy.js    # UI validation tests
├── fixtures/                     # Test data fixtures
│   └── example.json
├── screenshots/                   # Test failure screenshots
├── videos/                        # Test run videos
├── support/                       # Custom commands and utilities
│   ├── commands.js               # Custom Cypress commands
│   └── e2e.js                    # Setup and configuration
└── README.md                     # This file
```

## 🚀 Getting Started

### Prerequisites

- Node.js 18+ installed
- XAMPP or equivalent LAMP stack running
- MySQL database configured
- Application running on `http://localhost/capstone`

### Installation

```bash
# Install Cypress
npm install cypress --save-dev

# Run Cypress for the first time
npx cypress open
```

## 📝 Running Tests

### Interactive Mode (Recommended for Development)

```bash
# Open Cypress Test Runner
npx cypress open

# This opens the Cypress GUI where you can select and run tests
```

### Headless Mode (CI/CD)

```bash
# Run all tests in headless mode
npx cypress run

# Run specific test file
npx cypress run --spec "cypress/e2e/01-login.cy.js"

# Run with specific browser
npx cypress run --browser chrome
npx cypress run --browser firefox
npx cypress run --browser edge
```

### Specific Test Suites

```bash
# Run only login tests
npx cypress run --spec "cypress/e2e/01-login.cy.js"

# Run navigation tests
npx cypress run --spec "cypress/e2e/02-navigation.cy.js"

# Run CRUD tests
npx cypress run --spec "cypress/e2e/05-crud-operations.cy.js"
```

## 🛠️ Custom Commands

### Available Commands

```javascript
// Login as admin
cy.login()
cy.loginAdmin()

// Logout
cy.logout()

// Navigate to page
cy.goToPage('menu_management.php')

// Wait and click
cy.waitAndClick('button.submit')

// Assert visibility
cy.assertVisible('div.content')

// Wait for API
cy.waitForAPI('GET', '/api/data')

// Assert text content
cy.assertText('h1', 'Dashboard')

// Verify URL
cy.verifyUrl('/admin/index.php')

// Login as specific role
cy.loginAsRole('manager')
```

## 🧪 Test Coverage

### Current Test Files

1. **01-login.cy.js** - Authentication testing
   - Valid login credentials
   - Invalid login credentials
   - Empty field validation
   - Logout functionality

2. **02-navigation.cy.js** - Navigation testing
   - Navbar navigation
   - Dropdown menus
   - Page routing
   - Route guards

3. **03-dashboard.cy.js** - Dashboard testing
   - Stats cards display
   - Charts rendering
   - Recent orders
   - Popular items

4. **04-forms.cy.js** - Form interaction testing
   - Date pickers
   - Select dropdowns
   - Form submissions
   - Field validation

5. **05-crud-operations.cy.js** - CRUD testing
   - Create operations
   - Read operations
   - Update operations
   - Delete operations

6. **06-api-integration.cy.js** - API testing
   - API request interception
   - Response validation
   - Error handling
   - Authentication checks

7. **07-ui-validation.cy.js** - UI testing
   - Button interactions
   - Responsive design
   - Visual elements
   - Error handling

## ⚙️ Configuration

### Environment Variables

Edit `cypress.config.js` to configure:

```javascript
env: {
  apiUrl: 'http://localhost/capstone/api',
  adminUsername: 'admin',
  adminPassword: 'admin123',
}
```

### Test Settings

- **Base URL**: `http://localhost/capstone`
- **Viewport**: 1280x720 (configurable)
- **Retries**: 2 times on failure
- **Timeout**: 10 seconds per command

## 🎯 Best Practices

### 1. Use Custom Commands
Instead of repeating code, use custom commands:

```javascript
// ❌ Don't do this
cy.visit('/admin/login.php');
cy.get('[name="username"]').type('admin');

// ✅ Do this
cy.loginAdmin();
```

### 2. Use Data Attributes
Prefer data attributes for selectors:

```javascript
cy.get('[data-test="login-button"]').click();
```

### 3. Wait for Elements
Always wait for elements to be visible:

```javascript
cy.get('.button').should('be.visible').click();
```

### 4. Avoid Hard Waits
Use intelligent waits instead:

```javascript
// ❌ Don't
cy.wait(5000);

// ✅ Do
cy.get('.loaded-content').should('be.visible');
```

## 🐛 Debugging

### Debug Mode

```bash
# Run with debug logs
DEBUG=cypress:* npx cypress open

# Run specific test with debugging
npx cypress open --spec "cypress/e2e/01-login.cy.js"
```

### Screenshots and Videos

Screenshots are automatically taken on failure and saved to `cypress/screenshots/`.

Videos are recorded for each test run and saved to `cypress/videos/`.

## 🔧 Troubleshooting

### Common Issues

**1. Tests timing out**
- Increase timeout in `cypress.config.js`
- Check if the application is running

**2. Elements not found**
- Verify element selectors
- Check if the application UI has changed

**3. Login failing**
- Verify credentials in `cypress.config.js`
- Check if the database has the admin user

**4. Cypress not installing**
```bash
# Clear npm cache
npm cache clean --force

# Reinstall
npm install cypress --save-dev
```

## 📊 CI/CD Integration

### GitHub Actions

Tests run automatically on:
- Push to `main` or `develop` branches
- Pull requests
- Manual workflow dispatch

See `.github/workflows/cypress.yml` for configuration.

### Running Tests in CI

```bash
# In CI environment
npx cypress run --headless --browser chrome
```

## 📈 Test Reports

Cypress provides detailed test reports including:
- Test execution time
- Screenshots of failures
- Videos of test runs
- Console logs

## 🤝 Contributing

When adding new tests:

1. Follow the naming convention: `##-description.cy.js`
2. Use custom commands when possible
3. Add comments to explain complex logic
4. Keep tests independent (don't rely on execution order)
5. Use data-test attributes for selectors

## 📚 Resources

- [Cypress Documentation](https://docs.cypress.io/)
- [Cypress Best Practices](https://docs.cypress.io/guides/references/best-practices)
- [Writing Your First Test](https://docs.cypress.io/guides/getting-started/writing-your-first-test)

## ✅ Checklist for New Features

Before marking a feature as complete:

- [ ] Unit tests written
- [ ] Integration tests written
- [ ] E2E tests written in Cypress
- [ ] Tests pass locally
- [ ] Tests pass in CI
- [ ] Documentation updated

---

**Happy Testing! 🚀**



