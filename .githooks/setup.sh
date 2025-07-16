#!/bin/bash
#
# Git Hooks Setup Script
# Run this script to install the pre-commit hook
#

echo "🔧 Setting up Git hooks..."

# Copy pre-commit hook
cp .githooks/pre-commit .git/hooks/pre-commit

# Make it executable
chmod +x .git/hooks/pre-commit

echo "✅ Pre-commit hook installed successfully!"
echo ""
echo "The hook will run 'composer check' before each commit, which includes:"
echo "  - Code style fixes (phpcbf)"
echo "  - PSR-12 coding standards check (phpcs)"
echo "  - PHPStan static analysis"
echo "  - Unit tests"
echo ""
echo "To skip the hook for a specific commit, use: git commit --no-verify"