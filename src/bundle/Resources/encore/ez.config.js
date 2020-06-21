const path = require('path');

module.exports = (Encore) => {
    Encore
        .addEntry('ad-admin-css', [
            path.resolve(__dirname, '../public/scss/content_usage.scss'),
        ])
        .addEntry('ad-admin-js', [
            path.resolve(__dirname, '../public/js/ExampleFinder.js'),
            path.resolve(__dirname, '../public/js/content_usage.js'),
        ])
};
// Note: jQuery is provided by https://github.com/ezsystems/ezplatform-admin-ui-assets/tree/v5.0.0/Resources/public/vendors/jquery
