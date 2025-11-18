const { defineConfig } = require('cypress');

module.exports = defineConfig({
  // Cypress Cloud Project ID
  projectId: '1ey29x',
  
  // Viewport settings
  viewportWidth: 1280,
  viewportHeight: 720,
  
  // Retry failed tests
  retries: {
    runMode: 2, // Retry 2 times in CI
    openMode: 1, // Retry 1 time in interactive mode
  },
  
  // Default command timeout
  defaultCommandTimeout: 10000,
  requestTimeout: 10000,
  responseTimeout: 10000,
  
  // Video recording (useful for CI debugging)
  video: true,
  videosFolder: 'cypress/videos',
  
  // Screenshots
  screenshotOnRunFailure: true,
  screenshotsFolder: 'cypress/screenshots',
  
  // Environment variables
  env: {
    // API endpoint
    apiUrl: 'http://localhost/capstone/api',
    // Test credentials
    adminUsername: 'admin',
    adminPassword: 'admin123',
  },
  
  // E2E configuration
  e2e: {
    // Base URL for all tests (MUST be inside e2e object)
    baseUrl: 'http://localhost/capstone',
    
    // Test file pattern
    specPattern: 'cypress/e2e/**/*.{js,jsx,ts,tsx}',
    
    // Support files
    supportFile: 'cypress/support/e2e.js',
    
    // Setup node events
    setupNodeEvents(on, config) {
      // implement node event listeners here
      return config;
    },
    
    // Exclude certain files from running
    excludeSpecPattern: ['**/*.spec.js'],
  },
  
  // Component testing (for future use)
  component: {
    devServer: {
      framework: 'create-react-app',
      bundler: 'webpack',
    },
    specPattern: 'cypress/component/**/*.{js,jsx,ts,tsx}',
  },
});
