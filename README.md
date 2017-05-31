# Octophpus
Asynchronous ESI filter for PHP 7.1+. Its scans string for esi tags 
and replace with remote content. Its a simple replace for varnish server just for 
including esi content. The killer feature is that all request are made 
asynchronous, so it should be **faster than varnish**.

**No that this version is just proof of concept. For production implementation 
wait for Octophpus 0.5.**

## Requirements 
* PHP 7.1
* Guzzle 6 

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

## Roadmap

Some features to add in the near feature

* Add option to throw exception when one of the request fails,
* Add logging - use PSR-3 logger interface
* Add caching - use PSR-7 cache interface
* Add timeout option - as global and in esi tag
* Add [hxInclude](http://mnot.github.io/hinclude/) option on timeout