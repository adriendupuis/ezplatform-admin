$(function() {
    if (document.location.pathname.includes('/view/content/') && $('a[href="#ez-tab-location-view-relations"]').length) {
        let hasReverseRelation = 0 < $('#ez-tab-location-view-relations section .ez-table-header').eq(1).next('div.ez-scrollable-table-wrapper').find('tbody tr').length;
        console.log('hasReverseRelation', hasReverseRelation);
        if (hasReverseRelation) {
            $('input[name="updateVisibility"]').click(function(event) {
                let isVisible = false === $(this).prop('checked');
                console.log('isVisible', isVisible);
                if (isVisible && !window.confirm('This content is used. Are you sure to hide it?')) {
                    event.stopPropagation();
                }
            });
        }
    }
});