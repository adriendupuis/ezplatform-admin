$(function () {
    let tab = $('[href="' + document.location.hash + '"]');
    if (tab.length) {
        if (document.location.hash.startsWith('#ez-tab')) {
            tab.click();
        } else if ('tab' === tab.attr('role') && tab.parents('.ez-main-nav').length) {
            let childTab = $(document.location.hash).find('a.nav-link:first');
            $('.ez-modal-wrapper').html(
                '<div class="modal fade ez-modal ez-modal--version-draft-conflict" id="tab-redirect-modal" tabindex="-1" role="dialog">\n' +
                '    <div class="modal-dialog modal-lg" role="document">\n' +
                '        <div class="modal-content">\n' +
                '            <div class="modal-header">' +
                '               <h5 class="modal-title">' + Translator.trans(/*@Desc("Tab Redirect")*/ 'tab_opener.modal_title', {}, 'ad_admin_content_usage') + '</h5>' +
                '            </div>' +
                '            <div class="modal-body">' +
                '                ' + Translator.trans(/*@Desc("Redirecting to tab")*/ 'tab_opener.modal_body', {}, 'ad_admin_content_usage') +
                '                <em>' + tab.text().trim() + '</em>&nbsp;/' +
                '                <strong><em>' + childTab.text().trim() + ' </em></strong>…' +
                '            </div>' +
                '        </div>' +
                '    </div>' +
                '</div>'
            );
            $('#tab-redirect-modal').modal('show');
            document.location = childTab.attr('href');
        }
    }
});
