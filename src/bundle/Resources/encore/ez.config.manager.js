const path = require('path');

module.exports = (eZConfig, eZConfigManager) => {
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-layout-js',
        newItems: [path.resolve(__dirname, '../public/js/tab_opener.js')],
    });
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-content-edit-parts-js',
        newItems: [
            path.resolve(__dirname, '../public/js/jquery-ui.min.js'),
            path.resolve(__dirname, '../public/js/sortable_ezobjectrelationlist.js'),
        ],
    });
};
