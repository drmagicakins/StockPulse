# Development Environment (Windows + WSL2 Ubuntu + Docker)

Decision D8 (owner-approved): develop inside Ubuntu on WSL2 with Docker Desktop. PHP, Composer, Node and PostgreSQL run in containers from WP 1B; the host only needs Git, Docker and an editor.

## 1. Install Ubuntu on WSL2 (PowerShell, as normal user)
```
wsl --install -d Ubuntu
wsl --set-default Ubuntu
wsl -l -v
```
Restart if asked, then create your Ubuntu username and password when the terminal opens. `wsl -l -v` should show Ubuntu, VERSION 2.

## 2. Docker Desktop integration
Docker Desktop > Settings > Resources > WSL integration: enable your Ubuntu distro. Apply and restart. In Ubuntu, `docker --version` and `docker compose version` should work.

## 3. Tools inside Ubuntu
```
sudo apt update && sudo apt install -y git pipx
pipx ensurepath
pipx install pre-commit
git config --global core.autocrlf input
git config --global user.name "Your Name"
git config --global user.email "you@example.com"
```
Open a new Ubuntu terminal after `pipx ensurepath`.

## 4. Get the repository into Ubuntu (not under /mnt/c)
On Windows, commit and push your work first (from the project folder):
```
git switch -c phase-1/foundation
git add .
git commit -m "docs: planning set and WP 1A tooling"
git push -u origin phase-1/foundation
```
Then in Ubuntu:
```
mkdir -p ~/projects && cd ~/projects
git clone <your-origin-url> stockpulse
cd stockpulse
git switch phase-1/foundation
pre-commit install
```
Get the origin URL on Windows with `git remote -v`. Work only in this Ubuntu copy from now on; the old Windows folder can be kept as a backup.

## 5. Editor
VS Code with the "WSL" extension: run `code .` from the Ubuntu project folder. Install the CodeGPT extension inside the WSL window so it sees the Linux filesystem.

## 6. Verify
```
bash preflight.sh
docker run --rm hello-world
```
Expected: preflight prints versions without the WSL error. PHP/Composer/Node may show "not installed" on Ubuntu; that is expected because they run in containers from WP 1B.

## Notes
- Windows Laravel Herd (PHP on Windows) is not part of this setup and does not conflict.
- Host ports used by the dev stack (all on 127.0.0.1): 8080 (nginx), 5433 (PostgreSQL), 6433 (PgBouncer), 6380 (Redis), 9000/9001 (RustFS S3 API/console), 8025/1025 (Mailpit), 5173 (Vite). They are configurable in `.env`.
