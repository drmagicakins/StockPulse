# StockPulse: rules for AI coding assistants and developers

Read before changing anything: docs/SESSION-HANDOFF.md (current state and next task), then the current phase plan in docs/.

## Workflow
- Work only in the approved phase and work package. Do not start unrelated features.
- Never run destructive commands (database resets, `git push --force`, deleting data or volumes) without explicit approval from the owner.
- Do not install host software or pipe downloaded scripts into a shell. Do not add dependencies without explaining their purpose and getting approval.
- Never claim a test, integration, device or payment gateway works unless it was run and the output is shown. Mark unconfigured integrations "pending configuration".

## Environment
- PHP, Composer, Node and PostgreSQL run in Docker. Use the Makefile: `make test`, `make lint`, `make format`, `make migrate`, `make artisan c="..."`, `make composer c="..."`. Do not run php/composer on the host.
- Never commit `.env` files, keys or credentials. Secrets live in environment variables.

## Engineering rules
- Thin controllers; business logic in service classes; validation in Form Requests (deny by default); authorization in Policies; responses via API Resources.
- Every tenant table carries `business_id` and row-level security; never accept a tenant id from request input.
- Stock and financial changes run in a database transaction and write an immutable ledger record; correct by reversal, never by editing history.
- Money uses decimal types, never floats.
- Every migration is reviewed against docs/DATABASE_SCHEMA.md; schema changes update that document.
- New behaviour ships with automated tests. Pint and Larastan must pass.
