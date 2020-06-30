class ExampleFinder {
    /**
     * @param {(string|Element|jQuery)} contentTypeSelect
     * @param {(string|Element|jQuery)} languageCodeSelect
     * @param {string} tableBaseUrl
     * @param {string} searchBaseUrl
     * @param {(string|Element|jQuery)} resultContainer
     * @param {(string|Element|jQuery)} statusContainer
     * @param {number} [limit=25]
     */
    constructor(contentTypeSelect, languageCodeSelect, tableBaseUrl, searchBaseUrl, resultContainer, statusContainer, limit = 25) {
        this.contentTypeSelect = $(contentTypeSelect);
        this.languageCodeSelect = $(languageCodeSelect);
        this.tableBaseUrl = tableBaseUrl;
        this.searchBaseUrl = searchBaseUrl;
        this.resultContainer = $(resultContainer);
        this.statusContainer = $(statusContainer);
        this.xhr = null;
        this
            .setLimit(limit)
            .resetSearch()
            .setEventHandlers()
        ;
    }

    setEventHandlers() {
        this
            .setContentTypeSelectEventHandler()
            .setLanguageCodeSelectEventHandler()
        ;
    }

    setContentTypeSelectEventHandler() {
        this.contentTypeSelect.val('').change(function () {
            this.abortSearch();
            let contentType = this.contentTypeSelect.val();
            if (contentType) {
                this.displayStatus(Translator.trans(/*@Desc("Initialize field table…")*/ 'example_finder.status.field_table_init', {}, 'ad_admin_content_usage'));
                this.resultContainer.load(
                    this.tableBaseUrl + contentType,
                    function (response, status, xhr) {
                        if ('error' === status) {
                            this.displayStatus(xhr.statusText);
                            this.resultContainer.html('');
                        } else {
                            this.displayStatus(Translator.trans(/*@Desc("Ready to search…")*/ 'example_finder.status.ready_to_search', {}, 'ad_admin_content_usage'));
                            this.setContentType(contentType).search();
                        }
                    }.bind(this)
                );
            }
        }.bind(this));
        return this;
    }

    setLanguageCodeSelectEventHandler() {
        this.languageCodeSelect.val('').change(function () {
            // Clean previous language's examples by reloading the table and running a new search.
            this.contentTypeSelect.change();
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

    /**
     * Add limit to offset
     * @returns {number} offset value after being increased
     */
    increaseOffset() {
        this.offset += this.limit;
        return this.offset;
    }

    resetSearch() {
        this.offset = 0;
        this.examples = {};
        this.fieldUsage = {};
        this.progressCount = 0;
        this.abortSearch();
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
            //href: example.urlAlias
        }).html(example.name);
    }

    mergeFieldUsage(fieldUsage, sliceCount) {
        for (let fieldDefIdentifier in fieldUsage) {
            if ('undefined' === typeof this.fieldUsage[fieldDefIdentifier]) {
                this.fieldUsage[fieldDefIdentifier] = 0;
            }
            this.fieldUsage[fieldDefIdentifier] += fieldUsage[fieldDefIdentifier];
        }
        this.progressCount += sliceCount;
        return this;
    }

    displayFieldUsage() {
        for (let fieldDefIdentifier in this.fieldUsage) {
            let percent = Math.floor(100 * this.fieldUsage[fieldDefIdentifier] / this.progressCount) + '%',
                ratio = this.fieldUsage[fieldDefIdentifier] + '/' + this.progressCount;
            $('#' + fieldDefIdentifier).find('.usage').empty().append('<span class="percent">'+percent+'</span> <small>('+ratio+')</small>');
        }
        return this;
    }

    search() {
        let url = this.searchBaseUrl + this.contentType + '/' + this.offset + '/' + this.limit;
        if (this.languageCodeSelect.val()) {
            url += '/' + this.languageCodeSelect.val();
        }
        this.displayStatus(Translator.trans(/*@Desc("Searching…")*/ 'example_finder.status.searching', {}, 'ad_admin_content_usage'), true);
        this.xhr = $.getJSON(url, function (data) {
            if (data.totalCount) {
                this.setTotalCount(data.totalCount);
                this.mergeExamples(data.examples).displayExamples();
                this.mergeFieldUsage(data.fieldUsage, data.sliceCount).displayFieldUsage();
                if (this.increaseOffset() < data.totalCount) {
                    this.search();
                } else {
                    this.displayStatus(Translator.trans(/*@Desc("Displaying final result.")*/ 'example_finder.status.final_result_display', {}, 'ad_admin_content_usage'), false);
                }
            } else {
                this.displayStatus(Translator.trans(/*@Desc("No content of this type.")*/ 'example_finder.status.no_content', {}, 'ad_admin_content_usage'), false);
            }
        }.bind(this)).fail(function (xhr, status, error) {
            this.displayStatus(error);
        }.bind(this));
    }

    abortSearch() {
        if (this.xhr) {
            this.xhr.abort();
            this.xhr = null;
        }
    }

    displayStatus(status, progress = false) {
        let statusText = status;
        if (progress && this.totalCount) {
            statusText = statusText + ' (' + Math.floor(100 * this.offset / this.totalCount) + '%)';
        }
        this.statusContainer.text(statusText);
    }
}

// Expose the class
window.ExampleFinder = ExampleFinder;
