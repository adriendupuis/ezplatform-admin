class ExampleFinder {
    /**
     * @param {string} searchBaseUrl
     * @param {(string|Element|jQuery)} resultContainer
     * @param {string} contentBaseUrl
     * @param {(string|Element|jQuery)} statusContainer
     * @param {number} limit
     */
    constructor(searchBaseUrl, resultContainer, contentBaseUrl, statusContainer, limit = 1/*TODO: 25*/) {
        this.searchBaseUrl = searchBaseUrl;
        this.resultContainer = resultContainer;
        this.contentBaseUrl = contentBaseUrl;
        this.statusContainer = statusContainer;
        this.setLimit(limit).resetSearch();
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
        console.log(this.examples);
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
            href: this.contentBaseUrl + example.id
        }).html(example.name);
    }

    search() {
        let url = this.searchBaseUrl + this.contentType + '/' + this.offset + '/' + this.limit;
        this.displayStatus('Searching…', true);
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
                    this.displayStatus('Displaying final result.', false);
                }
            } else {
                this.displayStatus('No content of this type.', false);
            }
        }.bind(this));
    }

    displayStatus(status, progress = false) {
        let statusText = status;
        if (progress && this.totalCount) {
            statusText = statusText + ' (' + Math.floor(100 * this.offset / this.totalCount) + '%)';
        }
        $(this.statusContainer).text(statusText);
    }
}
// Expose the class
window.ExampleFinder = ExampleFinder;