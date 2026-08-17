---
paths:
  - routes/web.php
---

# Routes

## Invokable controller class must exist before registering its route
Route::post('path', SomeController::class) (single-action/invokable form) validates method_exists($action, '__invoke') when the route collection is compiled at boot, not lazily at dispatch. If the controller class doesn't exist yet, the whole app fails to boot with "Invalid route action: [...]" and every test in the suite fails, not just the ones touching that route. When a PLAN phase adds only the route (controller deferred to a later phase), create a minimal invokable controller stub in the same phase so routes/web.php stays bootable; fill in real logic in the later phase.
