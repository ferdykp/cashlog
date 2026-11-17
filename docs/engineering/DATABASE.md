# Database

`config/database.php` defaults to SQLite when `DB_CONNECTION` is unset; `.env.example` and `phpunit.xml` use SQLite. The production database engine was not inspected and should be verified from deployment configuration without exposing credentials.

Migrations use Laravel schema builders, foreign keys, unique constraints, indexes, and `DECIMAL(15,2)` for currency. PHP tests run against SQLite `:memory:`. This is isolated and safe, but does not prove production row-lock, isolation, or deadlock behavior. `TransactionService` locks affected account rows in ascending ID order.

Before adding same-user composite constraints, run a read-only integrity report against a protected non-production copy and resolve existing invalid references. Never run tests/concurrency checks against production. No schema migration was added by this implementation pass.
