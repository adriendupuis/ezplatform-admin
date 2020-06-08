class ExampleFinder {
    /**
     * @param {(string|Element|jQuery)} contentTypeSelect
     * @param {string} tableBaseUrl
     * @param {string} searchBaseUrl
     * @param {(string|Element|jQuery)} resultContainer
     * @param {(string|Element|jQuery)} statusContainer
     * @param {number} [limit=25]
     */
    constructor(contentTypeSelect, tableBaseUrl, searchBaseUrl, resultContainer, statusContainer, limit = 25) {
        this.contentTypeSelect = $(contentTypeSelect);
        this.tableBaseUrl = tableBaseUrl;
        this.searchBaseUrl = searchBaseUrl;
        this.resultContainer = $(resultContainer);
        this.statusContainer = $(statusContainer);
        this
            .setLimit(limit)
            .resetSearch()
            .setEventHandler()
        ;
    }

    setEventHandler() {
        this.contentTypeSelect.val('').change(function () {
            if (this.contentTypeSelect.val()) {
                let contentType = this.contentTypeSelect.val();
                this.displayStatus('field_table_init');
                this.resultContainer.load(
                    this.tableBaseUrl + contentType,
                    function (response, status, xhr) {
                        if ('error' === status) {
                            this.displayStatus(xhr.statusText);
                            this.resultContainer.html('');
                        } else {
                            this.displayStatus('ready_to_search');
                            this.setContentType(contentType).search();
                        }
                    }.bind(this)
                );
            }
        }.bind(this));
        return this;
    }

    setContentType(contentType) {
        this.contentType = contentType;
        this.resetSearch();
        return this;
    }

    setLimit(limit) {
        this.limit = limit;
        return this;
    }

    increaseOffset() {
        this.offset += this.limit;
        return this.offset;
    }

    resetSearch() {
        this.offset = 0;
        this.examples = {};
        return this;
    }

    setTotalCount(totalCount) {
        this.totalCount = totalCount;
    }

    mergeExamples(examples) {
        for (let fieldDefIdentifier in examples) {
            let fieldExamples = examples[fieldDefIdentifier];
            if ('undefined' === typeof this.examples[fieldDefIdentifier]) {
                this.examples[fieldDefIdentifier] = {};
            }
            if (fieldExamples.good) {
                if (!this.examples[fieldDefIdentifier].best || this.examples[fieldDefIdentifier].best.score < fieldExamples.good.score) {
                    this.examples[fieldDefIdentifier].best = fieldExamples.good;
                }
            }
            if (fieldExamples.bads) {
                if (!this.examples[fieldDefIdentifier].bads) {
                    this.examples[fieldDefIdentifier].bads = Array.from(fieldExamples.bads);
                }
                this.examples[fieldDefIdentifier].bads.concat(fieldExamples.bads)
            }
        }
        return this;
    }

    displayExamples() {
        for (let fieldDefIdentifier in this.examples) {
            let fieldExamples = this.examples[fieldDefIdentifier];
            if ('undefined' !== typeof fieldExamples.best) {
                $('#' + fieldDefIdentifier).find('.best-example').empty().append(this.getExampleLinkElement(fieldExamples.best));
            }
            if ('undefined' !== typeof fieldExamples.bads) {
                $('#' + fieldDefIdentifier).find('.bad-examples ul').empty();
                for (let badExample of fieldExamples.bads) {
                    $('#' + fieldDefIdentifier).find('.bad-examples ul').append($('<li>').append(this.getExampleLinkElement(badExample)));
                }
            }
        }
        return this;
    }

    getExampleLinkElement(example) {
        return $('<a>', {
            href: example.url
            //href: example.url_alias
        }).html(example.name);
    }

    search() {
        let url = this.searchBaseUrl + this.contentType + '/' + this.offset + '/' + this.limit;
        this.displayStatus('searching', true);
        $.getJSON(url, function (data, status, xhr) {
            if ('error' === status) {
                //TODO
                return;
            }
            if (data.totalCount) {
                this.setTotalCount(data.totalCount);
                this.mergeExamples(data.examples).displayExamples();
                if (this.increaseOffset() < data.totalCount) {
                    this.search();
                } else {
                    this.displayStatus('final_result_display', false);
                }
            } else {
                this.displayStatus('no_content', false);
            }
        }.bind(this));
    }

    displayStatus(status, progress = false) {
        let statusText = Translator.trans(status,  {}, 'ad_admin_content_usage');
        if (progress && this.totalCount) {
            statusText = statusText + ' (' + Math.floor(100 * this.offset / this.totalCount) + '%)';
        }
        this.statusContainer.text(statusText);
    }
}
// Expose the class
window.ExampleFinder = ExampleFinder;