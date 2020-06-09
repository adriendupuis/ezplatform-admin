const path = require('path');

module.exports = (eZConfig, eZConfigManager) => {
    eZConfigManager.add({
        eZConfig,
        entryName: 'ezplatform-admin-ui-layout-js',
        newItems: [path.resolve(__dirname, '../public/js/tab_opener.js')],
    });
};
