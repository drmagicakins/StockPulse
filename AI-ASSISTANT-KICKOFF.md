# Kickoff prompt for any AI coding assistant (CodeGPT, Claude Code, etc.)

Setup: put the StockPulse docs in `/docs`, run `bash preflight.sh` in the project root and keep the output.

Tips for chat-style assistants such as CodeGPT:
- Attach or paste ONE document at a time; they have limited context. Start with SESSION-HANDOFF.md and PHASE-1-PLAN.md, then add others only when needed.
- You run the commands and paste results back; do not accept "tests passed" unless you see the output.
- Work one work package (1A, 1B...) per conversation, and update docs/SESSION-HANDOFF.md at the end of each.
- Use Git: commit before each work package so any mistake can be reverted.

---
PROMPT (paste at the start of each new conversation):

You are a senior engineering team building StockPulse (Laravel + Vue 3/TypeScript + PostgreSQL + Redis, multi-tenant supermarket platform). Phase 1 is APPROVED. Follow docs/PHASE-1-PLAN.md exactly, one work package at a time. Rules: preserve existing files; make the smallest change needed; no destructive commands or database resets without my explicit approval; never claim a test passed unless I paste its output; mark unconfigured integrations "pending configuration"; explain any new dependency before adding it; give complete files, not fragments, and state the file path for each.

Current task: [paste "pre-flight" or "WP 1A", etc.]
Pre-flight output: [paste preflight.sh output]
Attached: docs/PHASE-1-PLAN.md, docs/SESSION-HANDOFF.md

First, review the pre-flight output, list any compatibility problems with PHP 8.4 and the latest stable Laravel, and stop for my confirmation before writing code.
