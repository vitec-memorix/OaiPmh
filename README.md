[![Latest Version](https://img.shields.io/packagist/v/picturae/oai-pmh.svg?style=flat-square)](https://packagist.org/packages/league/html-to-markdown)
[![Build Status](https://travis-ci.org/picturae/OaiPmh.svg?branch=develop)](https://travis-ci.org/picturae/OaiPmh)
[![Coverage Status](https://coveralls.io/repos/picturae/OaiPmh/badge.svg?service=github)](https://coveralls.io/github/picturae/OaiPmh)
[![Total Downloads](https://img.shields.io/packagist/dt/league/picturae/oai-pmh.svg?style=flat-square)](https://packagist.org/packages/league/html-to-markdown)

# Description

Provides an wrapper to produce a OAI-PMH endpoint.

# Usage

```php
// Create provider
// Where $repository is an instance of \Picturae\OaiPmh\Interfaces\Repository
$provider = new \Picturae\OaiPmh\Provider($repository);

$repo->setRequest($somePsr7ServerRequest);

$response = $repo->execute();
```
