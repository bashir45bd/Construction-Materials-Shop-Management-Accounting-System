# Production installation

`schema.sql` is an **initial-install script only**.  It deliberately drops and recreates tables, so never run it against an existing production database.  Keep a dated backup and use versioned migrations for every later schema change.

## Initial installation

1. Create a new empty MySQL/MariaDB database with `utf8mb4` encoding.
2. Import `database/schema.sql` once.
3. Import `database/seed.sql` only for a fresh installation.  It creates example shop data, sample master data, roles, and initial users.
4. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the web-server environment.  Do not store production credentials in source control.
5. Set the web root to this project directory, require HTTPS, and grant the web-server account write access only to `storage/uploads`, `storage/logs`, and `storage/backups`.
6. Immediately replace all seeded shop details and change or remove every seeded account.  The seed file contains development-only credentials and must not be used unchanged on a public server.

## Go-live checks

- Configure real cash/bank/digital accounts before recording a sale or payment.
- Configure unit conversions (for example Ton to KG) before selling a product in a non-base unit. The application rejects unknown conversions to prevent bad stock balances.
- Verify database backups can be restored in a separate test database.
- Enable HTTPS, disable PHP `display_errors`, and protect the database server from public network access.
- Create least-privilege user accounts; do not use the seeded administrator for daily work.

## Upgrade policy

Create a numbered migration for each release and test it on a copy of production data.  Financial, sales, stock, and ledger rows are historical records: reverse or void them through the application; do not update or delete them directly in MySQL.
