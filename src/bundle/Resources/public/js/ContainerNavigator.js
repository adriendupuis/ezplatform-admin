class ContainerNavigator {
    constructor(baseUrl, container, text, submit, errors) {
        this.baseUrl = baseUrl;
        this.container = $(container);
        this.text = $(text);
        this.errors = $(errors);
        this.submit = $(submit).click(this.onSubmit.bind(this));
    }

    onSubmit(event) {
        event.preventDefault();
        let text = $('#container_navigator_text').val();
        if (text.length) {
            this.load(text);
        } else {
            this.displayError('Enter a service, a service tag or an event.');
        }
    }

    disableSubmit(state) {
        this.submit.prop('disabled', state);
    }

    wrapInto(html, element, direction) {
        return $('ul' === element.prop('tagName') ? '<li>' : '<div>')[direction + 'To'](element).append(html);
    }

    displayError(msg) {
        this.wrapInto(msg, this.errors, 'prepend').fadeIn(200).delay(1000).fadeOut(1000);
    }

    load(text, type = 'auto') {
        this.loadHtml(this.baseUrl + '/' + encodeURI(text) + '/' + type);
    }

    loadHtml(url) {
        if (!url) { return; }
        this.disableSubmit(true);
        $.ajax({
            url: url,
            success: function (data, textStatus, jqXHR) {
                if (data) {
                    let newElt = this.wrapInto(data, this.container, 'append');
                    newElt.find('a').click(function (event) {
                        event.preventDefault();
                        let target = $(event.target);
                        if (target.data('name')) {
                            this.load(target.data('name'), target.data('type'));
                        } else {
                            this.loadHtml(target.attr('href'));
                        }
                    }.bind(this));
                    newElt.find('.container_navigator_close').click(function(event) {
                        event.preventDefault();
                        $(event.target).closest('div, li').remove();
                    });
                } else {
                    this.displayError();
                }
                this.disableSubmit(false);
            }.bind(this),
            error: function (jqXHR, textStatus, errorThrown) {
                this.displayError(errorThrown);
                this.disableSubmit(false);
            }.bind(this),
        })
    }
}

// Expose the class
window.ContainerNavigator = ContainerNavigator;
