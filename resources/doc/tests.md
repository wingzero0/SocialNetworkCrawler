# Tests

### Requirements

1. Install `PHPUnit` by running

        composer install

2. Make sure testing section in environment file `.env` is updated (refer to `.env.example`), and fill in `FB_APP_ID` and `FB_APP_SECRET`. 

### Run tests

In project directory, run all tests by

    ./vendor/bin/phpunit

Or run single test (e.g., `CGFeedCrawlerTest`) by

    ./vendor/bin/phpunit tests/CGFeedCrawlerTest

### Remarks

* Latest PHPUnit 6.x requires PHP 7. Now `fbcrawler` is running on PHP 5.6w, so `PHPUnit 5.x` is used.
* All `PHPUnit` settings are in `phpunit.xml`.
* PHPUnit code coverage requires `Xdebug`, install it if needed.
