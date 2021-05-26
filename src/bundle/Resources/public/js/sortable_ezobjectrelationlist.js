$('.ez-relations__list').sortable({
    update: function(event, ui) {
        let idList = new Array();
        $('.ez-relations__item', event.target).each(function (index) {
            idList.push($(this).data('content-id'));
            $('.ez-relations__order-input', this).val(index+1);
        });
        $(event.target).closest('.ez-data-source').find('.ez-data-source__input').val(idList.join(',')).attr('value', idList.join(','));
    }
});
