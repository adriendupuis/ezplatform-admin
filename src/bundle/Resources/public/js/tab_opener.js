$(function () {
    if (document.location.hash.startsWith('#ez-tab')) {
        $('[href="' + document.location.hash + '"]').click();
    }
});