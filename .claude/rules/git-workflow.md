# Git Workflow & Code Quality

## Protected Branches
- **Never commit directly** to version branches (e.g., `2026`)
- **Never commit directly** to release candidate branches (e.g., `2026_rc`)
- Pre-commit hook blocks commits to protected branches

## Working Branches
- Use custom branch names for development
- Create PRs to merge into main branches

## Pre-commit Hooks

Pre-commit hooks run automatically on each commit. Skip with `--no-verify` only if necessary.
