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
