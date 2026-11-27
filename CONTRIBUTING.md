# Contributing to StockPulse

## Workflow
1. Read `docs/SESSION-HANDOFF.md` and the current phase plan before starting.
2. Work in the approved phase and work package only. Do not start unrelated features.
3. Branch from `main`: `phase-<n>/<topic>` for phase work, `fix/<topic>` for fixes.
4. Make small, reviewable commits. Commit before each work package so it can be reverted.
5. Open a pull request; CI must pass. Describe what changed, how it was tested and any migrations.
6. Update `CHANGELOG.md` and affected docs in the same pull request.

## Commit messages
Imperative, concise subject (max ~72 chars), optional body explaining why. Prefix with the area where useful, e.g. `inventory: add movement ledger guard`.

## Engineering rules (summary of the master specification)
- Preserve working code; make the smallest appropriate change. No destructive database operations without explicit approval.
- Thin controllers; business logic in service classes; validation in Form Requests; authorization in Policies; responses via API Resources.
- Every stock and financial change runs in a database transaction and writes its ledger record. Ledgers are append-only; correct by reversal.
- Money uses decimal types, never floats. Every tenant table carries `business_id`.
- Secrets live in environment variables, never in source control.
- Every migration is reviewed against `docs/DATABASE_SCHEMA.md`; schema changes update that document.
- Do not claim a feature, test, integration or hardware works unless it was actually run. Mark unconfigured integrations "pending configuration".

## Tests
New behaviour ships with automated tests. Security-relevant changes include tenant-isolation tests. Report exact commands and results in the pull request.

## Local checks
`pre-commit install` once; hooks run on every commit. Full environment setup: `docs/DEV_ENVIRONMENT.md`.
