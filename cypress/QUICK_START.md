# Cypress Quick Start Guide

## 🚀 Quick Start (5 Minutes)

### 1. Prerequisites
- ✅ Node.js installed
- ✅ XAMPP running with MySQL
- ✅ Application accessible at `http://localhost/capstone`

### 2. Run Your First Test

```bash
# Open Cypress Test Runner
npx cypress open
```

### 3. Run Login Test

In the Cypress Test Runner:
1. Click on `E2E Testing`
2. Select your browser (Chrome recommended)
3. Click on `01-login.cy.js`
4. Watch the test run!

## 📋 Common Commands

```bash
# Run all tests (headless)
npx cypress run

# Run specific test
npx cypress run --spec "cypress/e2e/01-login.cy.js"

# Run in specific browser
npx cypress run --browser chrome

# Run with headed mode (see browser)
npx cypress run --headed

# Run with video recording (for debugging)
npx cypress run --video
```

## 🎯 Test Categories

| Test File | What It Tests | Run Command |
|-----------|---------------|-------------|
| `01-login.cy.js` | Login/Authentication | `--spec "**/01-login.cy.js"` |
| `02-navigation.cy.js` | Navigation flow | `--spec "**/02-navigation.cy.js"` |
| `03-dashboard.cy.js` | Dashboard features | `--spec "**/03-dashboard.cy.js"` |
| `04-forms.cy.js` | Form interactions | `--spec "**/04-forms.cy.js"` |
| `05-crud-operations.cy.js` | CRUD operations | `--spec "**/05-crud-operations.cy.js"` |
| `06-api-integration.cy.js` | API integration | `--spec "**/06-api-integration.cy.js"` |
| `07-ui-validation.cy.js` | UI validation | `--spec "**/07-ui-validation.cy.js"` |

## ⚡ Quick Testing Tips

### Debug a Failing Test
```bash
# Run with debug logs
DEBUG=cypress:* npx cypress run --spec "cypress/e2e/01-login.cy.js"

# Take screenshots on failure
npx cypress run --screenshot

# Run with video recording
npx cypress run --video
```

### Watch Mode (Development)
```bash
# Open Cypress in interactive mode
npx cypress open

# Then select E2E Testing and your browser
```

## 🐛 Troubleshooting

**Problem**: Tests fail with "Timed out"
- **Solution**: Increase timeout in `cypress.config.js` or ensure your app is running

**Problem**: Can't find elements
- **Solution**: Check if the application UI has changed, update selectors

**Problem**: Login failing
- **Solution**: Verify admin credentials in `cypress.config.js` env section

**Problem**: Tests pass locally but fail in CI
- **Solution**: Ensure CI environment has all dependencies and the database is set up

## 📝 Next Steps

1. ✅ Run your first test: `npx cypress open`
2. ✅ Read the full [README.md](./README.md)
3. ✅ Explore custom commands in `cypress/support/commands.js`
4. ✅ Write your own tests!

## 💡 Pro Tips

1. **Use custom commands** - They make tests cleaner and more maintainable
2. **Run tests before pushing** - Catch bugs early
3. **Use meaningful test names** - Makes debugging easier
4. **Keep tests independent** - Each test should be able to run alone
5. **Use data-test attributes** - Makes selectors more stable

---

**Ready to test? Run `npx cypress open` and get started! 🎉**



