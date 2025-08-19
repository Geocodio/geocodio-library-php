# Changelog

All notable changes to `geocodio-library-php` will be documented in this file

## 2.5 - 2025-08-19

- Update to use Geocodio API v1.9

## 2.4 - 2025-08-19

- Added configurable HTTP timeouts for API requests
- Fixed return type declarations for geocode and reverse methods

## 2.3 - 2025-05-20

- Update to use Geocodio API v1.8

## 2.2 - 2025-05-06

- Added fields support for list geocoding

## 2.1 - 2025-03-06

- Now using `Authorization` header instead of `api_key` query parameter for authentication

## 2.0 - 2024-09-20

- Added support for list geocoding
- Breaking: all method responses now return an associative array rather than a JSON decoded object
- Breaking: Dropped support for unsupported PHP versions
  - Minimum supported version of PHP is now 8.2

## 1.7.0 - 2024-09-02

- Added support for the `format` parameter, thanks to pull request [#7](https://github.com/Geocodio/geocodio-library-php/pull/7) by [@kirilldakhniuk](https://github.com/kirilldakhniuk)

## 1.6.0 - 2023-09-27

- Fix: Unintalized variable (minor)

## 1.5.0 - 2021-11-13

- Update to use Geocodio API v1.7

## 1.4.0 - 2021-03-23

- Added support for PHP 8

## 1.3.0 - 2020-10-01

- Added compatibility with Guzzle 7 (Laravel 8)

## 1.2.0 - 2020-06-03

- Update to use Geocodio API v1.6

## 1.1.0 - 2020-05-14

- Update to use Geocodio API v1.5

## 1.0.2 - 2019-09-19

- First official release
