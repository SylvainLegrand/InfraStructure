# dev - Development Tools

Development tools for code quality, static analysis, and module scaffolding.

## Directory Structure

| Subdirectory | Purpose |
|--------------|---------|
| tools/ | Phan, PHPStan, fix scripts |
| setup/ | CodeSniffer rules, PHPUnit config |
| skeletons/ | Module templates |

## Module Templates

The `skeletons/` directory provides templates for creating new modules:

- Module descriptor templates
- Class templates
- Page templates

## Static Analysis & Pre-commit Hooks

See `.claude/rules/git-workflow.md` for PHPStan, Phan, CodeSniffer commands, and pre-commit hook setup.
