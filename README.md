# phpnomad/framework

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/framework.svg)](https://packagist.org/packages/phpnomad/framework)
[![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/framework.svg)](https://packagist.org/packages/phpnomad/framework)
[![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/framework.svg)](https://packagist.org/packages/phpnomad/framework)
[![License](https://img.shields.io/packagist/l/phpnomad/framework.svg)](https://packagist.org/packages/phpnomad/framework)

`phpnomad/framework` is the cross-package catch-all for PHPNomad. It's where the controller traits, middlewares, validations, events, and small helper interfaces live when they're useful across several packages but don't belong to any single one. Rather than rewriting the same POST handler or uniqueness check in every package that needs it, those patterns get lifted into `phpnomad/framework` so they can be reused.

The package depends on `phpnomad/db`, `phpnomad/di`, `phpnomad/event`, `phpnomad/rest`, and `phpnomad/logger`, which tells you most of what it touches. It isn't a single coherent concept, and the README won't pretend otherwise. It's a kitchen drawer for the recurring patterns that show up once you've built a few PHPNomad services and noticed the repetition.

PHPNomad has been in production for years, powering [Siren](https://sirenaffiliates.com) along with several MCP servers and client systems. The traits and middlewares in this package are the same ones those production systems depend on.

## Installation

```bash
composer require phpnomad/framework
```

Composer will pull in the peer packages listed above automatically.

## Quick Start

Here's a typical REST controller that uses `CreateController` to handle a POST endpoint. The trait supplies the create flow, datastore call, and error handling, while the class itself defines validations, middleware, and how the request maps into model attributes.

```php
<?php

namespace MyApp\Posts\Rest;

use MyApp\Posts\Datastores\PostAdapter;
use MyApp\Posts\Datastores\PostDatastore;
use MyApp\Posts\Services\PostFromRequestService;
use PHPNomad\Framework\Traits\CreateController;
use PHPNomad\Framework\Validations\IsUniqueDatabaseValue;
use PHPNomad\Http\Interfaces\Request;
use PHPNomad\Http\Interfaces\Response;
use PHPNomad\Logger\Interfaces\LoggerStrategy;
use PHPNomad\Rest\Enums\BasicTypes;
use PHPNomad\Rest\Factories\ValidationSet;
use PHPNomad\Rest\Interfaces\Controller;
use PHPNomad\Rest\Interfaces\HasMiddleware;
use PHPNomad\Rest\Interfaces\HasValidations;
use PHPNomad\Rest\Middleware\ValidationMiddleware;
use PHPNomad\Rest\Validations\IsType;

class CreatePost implements Controller, HasValidations, HasMiddleware
{
    use CreateController;

    protected PostFromRequestService $postFromRequest;

    public function __construct(
        Response $response,
        PostDatastore $datastore,
        PostAdapter $adapter,
        LoggerStrategy $logger,
        PostFromRequestService $postFromRequest
    ) {
        $this->response = $response;
        $this->datastore = $datastore;
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->postFromRequest = $postFromRequest;
    }

    public function getValidations(): array
    {
        return [
            'title' => (new ValidationSet())->setRequired()
                ->addValidation(fn() => new IsType(BasicTypes::String)),
            'slug' => (new ValidationSet())->setRequired()
                ->addValidation(fn() => new IsType(BasicTypes::String))
                ->addValidation(fn() => new IsUniqueDatabaseValue($this->datastore)),
        ];
    }

    protected function buildAttributes(Request $request): array
    {
        return $this->postFromRequest->getAttributes($request);
    }

    public function getEndpoint(): string
    {
        return '/posts';
    }

    public function getMiddleware(Request $request): array
    {
        return [new ValidationMiddleware($this)];
    }
}
```

What the trait contributed: a `getResponse()` implementation that calls the datastore, serializes the created record through the adapter, returns 201 on success, 409 on duplicate, and 500 (logged) on datastore errors. The controller class is left to describe the endpoint, not the plumbing.

## What Lives Here

Rather than a single concept, `phpnomad/framework` is organized by the kind of helper it provides.

### Controller traits

`CreateController`, `UpdateController`, and `GetByIdController` are drop-in POST, PUT, and GET handlers for any datastore-backed model. Subclasses supply the datastore, adapter, and a `buildAttributes()` method. The traits handle the rest.

### Middlewares

`PaginationMiddleware` defaults `number` to 10 and caps it at 50. `RecordExistsMiddleware` validates that a datastore record exists by key, raising a 404 if not. `FieldResolverMiddleware` pairs with the field-resolver pattern below to filter a `?fields=` CSV against a whitelist. `ConvertCsvMiddleware` and `PrepareCheckboxMiddleware` handle the smaller request-normalization jobs that come up repeatedly.

### Validations

`IsUniqueDatabaseValue` checks that a value is unique in a datastore, with optional self-exclusion so an update back to the same value still passes. `IdsExist` takes a request param containing an array of IDs and confirms every one of them resolves against a datastore.

### Events and abstracts

`PostVisited` carries a post ID and `SiteVisited` carries an optional interactor ID, both as concrete `Event` classes you can broadcast for tracking. `ModelRestActionEvent` is an abstract `RestActionEvent` base that concrete packages extend to carry model-specific create, update, and delete events through the REST interceptor pipeline. `FieldResolverRegistryInitiatedEvent` is an abstract event type that lets listeners register field resolvers as a model's resolver registry is initialized.

### Field resolver helpers

The `CanResolveFields` interface, `WithFieldResolver` trait, and `CanGetResolvedFields` controller trait together make up the field-resolver pattern, which lets controllers expose `?fields=` query selection over model records with resolvers registered via events at runtime.

## Documentation

Full documentation and the broader PHPNomad architecture guide live at [phpnomad.com](https://phpnomad.com).

## Contributing

Issues and pull requests are welcome on the repository. `phpnomad/framework` is maintained by [Alex Standiford](https://alexstandiford.com) as part of the PHPNomad stack. Feedback is especially welcome on helpers that have grown enough to deserve their own dedicated package.

## License

MIT. See [LICENSE.txt](LICENSE.txt).
