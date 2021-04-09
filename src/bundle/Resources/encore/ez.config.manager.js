const path = require('path');

module.exports = (eZConfig, eZConfigManager) => {
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-layout-js',
        newItems: [
            path.resolve(__dirname, '../public/js/tab_opener.js'),
        ],
    });
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-location-view-js',
        newItems: [
            path.resolve(__dirname, '../public/js/relation_checker.js'),
        ],
    });
};
