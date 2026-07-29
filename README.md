# Spectator Role for MainWP Dashboard

## Spectator – Safe, Read-Only Dashboard Access for Clients & Teams for MainWP Dashboard

Want to show your clients or team members how their websites are performing without worrying about accidental updates or setting changes? MainWP Spectator introduces a dedicated "Spectator" role to your self-hosted MainWP Dashboard, giving selected users secure, read-only access.

With this lightweight plugin, your spectators can view active sites, monitor updates, check security statuses, and browse client reports, while all critical actions—like installing plugins, running bulk updates, or altering core configurations—remain strictly locked. It is the perfect tool for transparent agencies and freelancers who want to build client trust without compromising the security of their central network!

## How prepare development environment?

1. get this repo: `git clone git@github.com:iworks/spectator-role-for-mainwp-dashboard.git`
2. run `npm install` if needed `npm install --force` 
3. run `grunt build` to build assets

## How to preapre test environment?

1. get `wordpress-tests-lib` repo `git clone git@github.com:ArcFramework/wordpress-tests-lib.git`
2. set environment variable `WP_TESTS_DIR` to path to `wordpress-tests-lib` repo
3. configure `wp-tests-config.php` to use `wordpress-tests-lib` repo
4. install [PHPUnit Polyfills](https://github.com/Yoast/PHPUnit-Polyfills) - `composer require --dev yoast/phpunit-polyfills:"^4.0"`
5. set environment variable `WP_TESTS_PHPUNIT_POLYFILLS_PATH` to path to `phpunit-polyfills` directory
6. add some tests
7. run `grunt phpunit`


## Changelog

[CHANGELOG](CHANGELOG)
