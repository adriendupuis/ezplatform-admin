window.onload = function () {
    let exampleFinder = new ExampleFinder(
        '#example_finder_content_type',
        exampleFinderConfig.layoutBaseUrl,
        exampleFinderConfig.searchBaseUrl,
        '#example_finder_result',
        '#example_finder_status'
    );
};