window.onload = function () {
    let exampleFinder = new ExampleFinder(
        '#example_finder_content_type',
        '#example_finder_language_code',
        exampleFinderConfig.layoutBaseUrl,
        exampleFinderConfig.searchBaseUrl,
        '#example_finder_result',
        '#example_finder_status'
    );
};
