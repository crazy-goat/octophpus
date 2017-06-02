# Octophpus
Asynchronous ESI filter for PHP 7.1+. Its scans string for `esi:include` tags 
and replace with remote content. It's a simple replace for varnish server just for 
including esi content. The killer feature is that all request are made 
asynchronous, so it should be **faster than varnish**.

**No that this version is just proof of concept. For production implementation 
wait for Octophpus 0.5.**

## Requirements 
* PHP 7.1
* Guzzle 6 
* cURL extension for PHP. _Octopus will run without cURL, but multiple request will not work_

## Installation

The recommended way to install Octophpus is through [Composer](http://getcomposer.org).

```bash
php composer.phar require crazygoat/octophpus
```

To upgrade to newest version execute:

```bash
php composer.phar update crazygoat/octophpus
```

## Example code

```php
include_once "../vendor/autoload.php";

$text = '<esi:include src="http://crazy-goat.com/"/>';

$octophpus = new \CrazyGoat\Octophpus\Mantle();
echo $octophpus->decorate($text);
```

## Limitations

* Octophpus only handle `esi:include` from ESI spec. 
* All `esi:include` tags must contains full uri in `src` attribute. Uri
  **must** contain protocol, domain name and path. See below.
* If data string contains two or `esi:include` tags with same `src` Octophpus
  will make multiple requests.

## ESI tag format

Octophpus does not cover full specification [ESI RFC](https://www.w3.org/TR/esi-lang).
It also adds some `esi:include` parameters that are not part of RFC.

Minimal valid `esi:include` is listed below:
```html
<esi:include src="http://crazy-goat.com/"/>
    ^          ^                         ^
    1          2                         3
```
 1. `esi:include` tag name, it is no case sensitive.
 1. `src` must contains full URI.
 1. Tag must end with `/>`. Tags like this `<esi:include ... ></esi:include>`
 will not work

Multi-line tag will work, so if you want to pass more parameters you can split it
to more lines:

```html
<esi:include 
    src="http://crazy-goat.com/"
/>
```
## Roadmap

List of features to be add in the near feature. _This list is not ordered._

* New features
    * Add option to throw exception when one of the request fails,
    * Add logging - use PSR-3 logger interface
    * Add caching - use PSR-7 cache interface
    * Add timeout option - as global and in esi tag
    * Add default url `protocol` and `domain` to handle relative `src`
    * Add [hxInclude](http://mnot.github.io/hinclude/) option on timeout
    * Add option to pass headers to requests (for example cookies, x-forwared-proto)
    * Add recurrence ESI requests
* Deployment and maintenance
    * Prepare roadmap ticket, and remove this stuff
    * Travis - form running tests
* Docs and examples
    * Create super duper logo
    * Add better docs
    * Add more examples
    * Add basic test
