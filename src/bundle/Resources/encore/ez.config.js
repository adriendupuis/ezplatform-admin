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
};
// Note:
// jQuery is provided in vendor/ezsystems/ezplatform-admin-ui-assets/Resources/public/vendors/jquery/dist/jquery.min.js
// called by vendor/ezsystems/ezplatform-admin-ui/src/bundle/Resources/views/themes/admin/ui/layout.html.twig
