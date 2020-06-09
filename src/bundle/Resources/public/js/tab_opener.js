$(function () {
    let tab = $('[href="' + document.location.hash + '"]');
    if (tab.length) {
        if (document.location.hash.startsWith('#ez-tab')) {
            tab.click();
        } else if ('tab' === tab.attr('role') && tab.parents('.ez-main-nav').length) {
            document.location = $(document.location.hash).find('.nav-link').attr('href');
        }
    }
});
