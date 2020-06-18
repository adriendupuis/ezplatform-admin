$(function () {
    let tab = $('[href="' + document.location.hash + '"]');
    if (tab.length) {
        // Open tab designated by URL hash
        if (document.location.hash.startsWith('#ez-tab')) {
            tab.click();
        } else if ('tab' === tab.attr('role') && tab.parents('.ez-main-nav').length) {
            let childTab = $(document.location.hash).find('a.nav-link:first');
            $('.ez-modal-wrapper').html(`
                <div class="modal fade ez-modal" id="tab-redirect-modal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    ${Translator.trans(/*@Desc("Tab Redirect")*/ 'tab_opener.modal_title', {}, 'ad_admin_content_usage')}
                                </h5>
                            </div>
                            <div class="modal-body">
                                ${Translator.trans(/*@Desc("Redirecting to tab")*/ 'tab_opener.modal_body', {}, 'ad_admin_content_usage')}
                                <em>${tab.text().trim()}</em>&nbsp;/
                                <strong><em>${childTab.text().trim()}</em></strong>…
                            </div>
                        </div>
                    </div>
                </div>
            `);
            $('#tab-redirect-modal').modal('show');
            document.location = childTab.attr('href');
        }
    }
    $('[href^="#ez-tab"]').click(function (event) {
        // Memorize clicked tab
        document.location.hash = $(this).attr('href');
    });
});
