// Karma configuration
module.exports = function(config) {
  config.set({
    basePath: '../..',
    frameworks: ['jasmine'],
    // list of files / patterns to load in the browser
    files: [
      'src/vendor_bower/angular/angular.js',
      'src/vendor_bower/angular-route/angular-route.js',
      'src/vendor_bower/angular-animate/angular-animate.js',
      'src/vendor_bower/angular-sanitize/angular-sanitize.js',
      'test/lib/angular/angular-mocks.js',
      'src/vendor_bower/ng-file-upload/ng-file-upload.js',
      'src/vendor_bower/jquery/jquery.js',
      'src/vendor_bower/angular-bootstrap/*ui-bootstrap*.js',
      'src/angular-app/**/*.js',
      'test/app/**/unit/*.spec.js'
    ],

    // list of files to exclude
    exclude: [],

    // test results reporter to use
    // possible values: dots || progress || growl
    reporters: ['progress'],

    // web server port
    port: 8080,

    // cli runner port
    runnerPort: 9100,

    // enable / disable colors in the output (reporters and logs)
    colors: true,

    // level of logging
    // possible values: LOG_DISABLE || LOG_ERROR || LOG_WARN || LOG_INFO ||
  // LOG_DEBUG
    logLevel: config.LOG_WARN,

    // enable / disable watching file and executing tests whenever any file
  // changes
    autoWatch: false,

    // Start these browsers, currently available:
    // - Chrome
    // - ChromeCanary
    // - Firefox
    // - Opera
    // - Safari (only Mac)
    // - PhantomJS
    // - IE (only Windows)
    browsers: ['PhantomJS'],

    // If browser does not capture in given timeout [ms], kill it
    captureTimeout: 8000,

    // Continuous Integration mode
    // if true, it capture browsers, run tests and exit
    singleRun: false
  });
};
