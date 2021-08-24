const path = require('path');

module.exports = (Encore) => {
    Encore
        .addEntry('ad-admin-content-usage-css', [
            path.resolve(__dirname, '../public/scss/content_usage.scss'),
        ])
        .addEntry('ad-admin-content-usage-js', [
            path.resolve(__dirname, '../public/js/ExampleFinder.js'),
            path.resolve(__dirname, '../public/js/content_usage.js'),
        ])
        .addEntry('ad-admin-identification-css', [
            path.resolve(__dirname, '../public/scss/identification.scss'),
        ])
        .addEntry('ad-admin-container-navigator-css', [
            path.resolve(__dirname, '../public/scss/container_navigator.scss'),
        ])
        .addEntry('ad-admin-container-navigator-js', [
            path.resolve(__dirname, '../public/js/ContainerNavigator.js'),
        ])
};
// Note: jQuery is provided by https://github.com/ezsystems/ezplatform-admin-ui-assets/tree/v5.0.0/Resources/public/vendors/jquery
