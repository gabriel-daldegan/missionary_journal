# Error Handling Best Practices

## Exception Reporting and Rendering

There are two valid approaches — choose one and apply it consistently across the project.

**Co-location on the exception class** — keeps behavior alongside the exception definition, easier to find:

```php
class InvalidOrderException extends Exception
{
    public function report(): void { /* custom reporting */ }

    public function render(Request $request): Response
    {
        return response()->view('errors.invalid-order', status: 422);
    }
}
```

**Centralized in `bootstrap/app.php`** — all exception handling in one place, easier to see the full picture:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (InvalidOrderException $e) { /* ... */ });
    $exceptions->render(function (InvalidOrderException $e, Request $request) {
        return response()->view('errors.invalid-order', status: 422);
    });
})
```

Check the existing codebase and follow whichever pattern is already established.

## Use `ShouldntReport` for Exceptions That Should Never Log

More discoverable than listing classes in `dontReport()`.

```php
class PodcastProcessingException extends Exception implements ShouldntReport {}
```

## Throttle High-Volume Exceptions

A single failing integration can flood error tracking. Use `throttle()` to sample or rate-limit reported exceptions before they reach logs or external trackers.

```php
use Illuminate\Support\Lottery;
use Throwable;

$exceptions->throttle(function (Throwable $e): Lottery {
    return Lottery::odds(1, 1000);
});
```

## Enable `dontReportDuplicates()`

Prevents the same exception instance from being logged multiple times when `report($e)` is called in multiple catch blocks.

```php
$exceptions->dontReportDuplicates();
```

## Force JSON Error Rendering for API Routes

Laravel auto-detects `Accept: application/json` but API clients may not set it. Explicitly declare JSON rendering for API routes.

```php
$exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
    return $request->is('api/*') || $request->expectsJson();
});
```

## Add Context to Exception Classes

Attach structured data to exceptions at the source via a `context()` method — Laravel includes it automatically in the log entry.

```php
class InvalidOrderException extends Exception
{
    public function context(): array
    {
        return ['order_id' => $this->orderId];
    }
}
```
