$(function () {
    let tab = $('[href="' + document.location.hash + '"]');
    if (tab.length) {
        // Opening a tab in a new window uses an URL with a hash.
        // If a tab is found for this hash, open it:
        if (document.location.hash.startsWith('#ez-tab')) {
            tab.click();
        } else if ('tab' === tab.attr('role') && tab.parents('.ez-main-nav').length) {
            let childTab = $(document.location.hash).find('a.nav-link:first');
            $('.ez-modal-wrapper').html(
                '<div class="modal fade ez-modal ez-modal--version-draft-conflict" id="tab-redirect-modal" tabindex="-1" role="dialog">\n' +
                '    <div class="modal-dialog modal-lg" role="document">\n' +
                '        <div class="modal-content">\n' +
                '            <div class="modal-header">' +
                '               <h5 class="modal-title">Tab Redirect</h5>' +
                '            </div>' +
                '            <div class="modal-body">' +
                '                Redirecting to tab' +
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
    } else {
        // If reloading a page with a tab, reopen this tab:
        var currentTabLocation = '', currentTabOwnHref = '';
        for (let cookie of document.cookie.split(';')) {
            let cookieCrumbs = cookie.trim().split('=', 2);
            if (cookieCrumbs.length) {
                if ('current_tab_location' === cookieCrumbs[0]) {
                    currentTabLocation = cookieCrumbs[1];
                } else if ('current_tab_own_href' === cookieCrumbs[0]) {
                    currentTabOwnHref = cookieCrumbs[1];
                }
            }
        }
        console.log(document.cookie, currentTabLocation, currentTabOwnHref);
        if (document.location.href === currentTabLocation) {
            // Reopen the previously clicked tab
            $('[href="' + currentTabOwnHref + '"]').click();
        } else {
            // Forgot the previously clicked tab
            document.cookie = 'current_tab_location=; path=/; SameSite=Strict';
            document.cookie = 'current_tab_own_href=; path=/; SameSite=Strict';
        }
    }
    $('[href^="#ez-tab"]').click(function () {
        // Memorize the clicked tab
        document.cookie = 'current_tab_location=' + document.location.href + '; path=/; SameSite=Strict';
        document.cookie = 'current_tab_own_href=' + $(this).attr('href') + '; path=/; SameSite=Strict';
    });
});
