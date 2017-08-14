# Contributing

## Pull Request Process

Nobody has direct access to the master branch, so all merges will be done using [Pull Requests](https://bitbucket.org/ciandt_it/docker-behat/pull-requests/).
Work should be done on branches following the naming convetion: feature/descriptive-feature-name

## Coding Standards

We'll be using the [PSR-2 coding standards](http://www.php-fig.org/psr/psr-2/), checking the code and formatting the code with [PHP CodeSniffer](https://packagist.org/packages/squizlabs/php_codesniffer).

To execute CodeSniffer, just run `composer cs` on the `contrib` folder, inside the docker-behat container.
To execute Code Beautifier, fixing some of the CodeSniffer issues, just run `composer cbf` on the `contrib` folder, inside the docker-behat container.

## Tests

The tests run in Behat itself. All tests must pass for your PR to be merged, and every new feature should be accompanied by a feature that tests it's functionalities.
To execute the tests, you must start the `the-internet` docker compose service, used in the test suite. Run `docker-compose up -d the-internet`, outside of the docker-behat container and inside your `docker-behat` folder.
To execute the test suite run `composer test` on the `contrib` folder, inside the docker-behat container. 
