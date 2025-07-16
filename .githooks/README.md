# Git Hooks

This directory contains Git hooks to help maintain code quality.

## Pre-commit Hook

The pre-commit hook automatically runs quality checks before each commit:

- **Code style fixes** (`phpcbf`)
- **PSR-12 coding standards** (`phpcs`)
- **PHPStan static analysis** (level 6)
- **Unit tests** (`phpunit`)

### Installation

Run the setup script to install the hook:

```bash
./.githooks/setup.sh
```

Or manually copy the hook:

```bash
cp .githooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

### Usage

The hook runs automatically on every commit. If any checks fail, the commit will be blocked.

To skip the hook for a specific commit (not recommended):

```bash
git commit --no-verify -m "Your commit message"
```

### Environment Support

The hook supports both local composer and Docker environments:

- If `composer` is available locally, it will be used
- If only `docker-compose` is available, it will use the Docker container
- If neither is found, the hook will skip checks with a warning

### Manual Checks

You can run the same checks manually:

```bash
# With composer
composer check

# With Docker
docker-compose exec php composer check
```