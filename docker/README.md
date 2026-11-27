# docker

Local development stack for StockPulse (WP 1B). Compose file: `../docker-compose.yml`.

| Service | Role | Profile | Host port (127.0.0.1) |
|---|---|---|---|
| postgres | PostgreSQL (default major 18, set `POSTGRES_VERSION`) | default | 5433 |
| pgbouncer | Transaction-mode pooler | `pool` | 6433 |
| redis | Cache, queue, sessions (password required) | default | 6380 |
| rustfs | S3-compatible storage, DEV ONLY (pre-1.0) | default | 9000 (API), 9001 (console) |
| storage-init | One-shot bucket creation | default | none |
| mailpit | Dev mail catcher | default | 8025 (UI), 1025 (SMTP) |
| app, nginx, horizon, scheduler, node | Laravel runtime and tooling (need the backend: `make backend-install`) | `app` | 8080 (nginx), 5173 (vite) |

## Database roles
- `postgres` (superuser): bootstrap and maintenance only. Never used by the application.
- `stockpulse_owner`: owns the databases and schema; runs migrations; not a superuser.
- `stockpulse_app`: runtime role; no superuser, no BYPASSRLS, owns nothing, cannot create objects. Row-level security therefore applies to it.
- Databases: `stockpulse` and `stockpulse_test` (for the tenant-leak test suite).
- The init script runs only when the data volume is empty. To re-run it: `make reset-db`.

## Everyday commands
`make env` (once) | `make backend-install` (once) | `make up-app` | `make migrate` | `make test` | `make lint` | `make format` | `make artisan c="..."` | `make psql` | `make ps` | `make logs s=horizon` | `make down`

## Database connections in Laravel
- `pgsql` (runtime): role `stockpulse_app` through PgBouncer (transaction pooling).
- `pgsql_owner` (migrations only): role `stockpulse_owner`, direct to PostgreSQL, because migrations use session-level locks that transaction pooling would break.
- Horizon dashboard assets are not committed; publish them with `make artisan c="horizon:publish"`.

## Notes and limits
- DEV ONLY: HTTP, dev passwords, loopback-bound ports. Production deployment is designed separately.
- MinIO's community images were withdrawn; RustFS is a dev stand-in for any S3-compatible service. Application code must use only the S3 API.
- PostgreSQL major upgrades need a fresh volume (versioned data directory in the 18+ image).
- Images marked `latest` in `.env.example` are pinned by digest in WP 1I.
- Behind PgBouncer, set tenant context with `SET LOCAL` inside a transaction. Session-level `SET` is unsafe in transaction pooling.
