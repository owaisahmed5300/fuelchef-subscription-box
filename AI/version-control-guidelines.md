# Version Control Guidelines (Git & GitHub Standards)

## Purpose

This document defines strict rules for how Git and GitHub must be used in this project.

These rules apply to:
- Human developers
- AI agents (Aider, Copilot, Cursor, Devin, etc.)
- Automated systems (CI, Dependabot, Release Please)

The goal is to maintain:
- Stable production code
- Clean and readable Git history
- Reliable automated releases
- Safe collaboration
- Predictable deployment flow

---

# Core Principles

## 1. Main branch is production

`main` is always considered production-ready. Merging to `main` triggers automated release tagging and/or deployments.

Rules:
- Never commit directly to `main`
- Never push directly to `main`
- All changes must go through Pull Requests
- `main` must always pass CI

If something breaks `main`, it is a high-priority fix.

---

## 2. Every change must be intentional

Random commits are not allowed.

Every commit must:
- Represent a single logical change
- Be meaningful in isolation
- Be safe to revert
- Not include unrelated edits

Bad examples:
- fixing bug + formatting + dependency update in one commit
- WIP dumps
- "fix stuff"
- "updates"
- "final changes"

Good examples:
- fix(api): handle null response from endpoint
- feat(auth): add token refresh logic
- refactor(cache): simplify caching layer

---

## 3. Git history is a product

Git history is not a scratchpad.

It is used for:
- debugging production issues
- generating releases (Release Please)
- understanding system evolution
- auditing changes

Therefore:
- Do not pollute history with noise commits
- Do not commit debugging artifacts
- Do not commit temporary code

---

# Branch Strategy

## Main branch

- Protected
- Always stable
- Always deployable

---

## Feature branches

All development must happen in feature branches.

Naming convention:
`<type>/<issue-number-or-kebab-case-description>`

Rules:
- Must use strictly **lowercase kebab-case** for the description (e.g., `feat/add-user-login`).
- Maximum 50 characters for the branch name.
- Special characters and spaces are forbidden.
- Branches must be short-lived.
- One purpose per branch. No mixing unrelated changes.

---

# Commit Rules

## 1. Use Conventional Commits

Format:
```
type(scope): description
```
*Note: The `(scope)` is optional but highly recommended for clarity.*

Examples:
```
feat(settings): add export feature
fix(api): handle timeout errors
chore(deps): update composer packages
refactor(cache): simplify cache layer
test(auth): add login coverage
docs(readme): update setup instructions
```

---

## 2. Allowed commit types

- `feat` → new feature
- `fix` → bug fix
- `refactor` → code restructure (no behavior change)
- `chore` → maintenance tasks
- `docs` → documentation only
- `test` → tests only
- `style` → formatting only
- `perf` → performance improvements
- `ci` → CI/CD changes
- `build` → build system changes
- `revert` → revert previous commit

---

## 3. Commit message rules

Commit messages must:
- Use imperative tone ("add", not "added")
- Be lowercase in the subject line
- Avoid punctuation at the end
- Be concise but meaningful

Good:
```
fix(api): handle empty response
```

Bad:
```
Fixed API bug.
fix(api): handling empty response.
```

---

## 4. Breaking changes

Breaking changes must be explicitly marked. This directly affects versioning (major release).

Use an exclamation mark before the colon:
```
feat(api)!: change authentication flow
```
Or use the footer:
```
BREAKING CHANGE: authentication flow has been redesigned
```

---

# Pull Request Rules

## 1. Every change must go through a PR

Direct commits to `main` are forbidden.

---

## 2. PR scope must be limited

A PR must represent a single intent.

Bad:
- feature + refactor + dependency updates
- multiple unrelated fixes

Good:
- one feature
- one bug fix
- one refactor

---

## 3. PR size should be reasonable

Recommended:
- Small PRs (< 500 lines changed)

Large PRs:
- Must be justified
- Should be split when possible

---

## 4. PR Title Format

Use Conventional Commits style. Because we use Squash merges, **this title will become the final commit message on `main`**.

```
feat(settings): add import wizard
fix(api): resolve timeout issue
```

---

## 5. PR Description Format (Strict Constraint)

PR descriptions must be concise and structured.
- **DO NOT** write long essays or line-by-line code summaries.
- **DO** use bullet points.
- **DO** state the "Why" (the problem being solved).
- **DO** state the "How" (the high-level approach).
- **DO** include related issue/ticket numbers if applicable.

---

## 6. PR must pass checks

Required before merge:
- CI Status (Linters, Static Analysis)
- Commitlint

All checks must pass.

---

## 7. PR review requirement

- At least 1 approval required.
- Reviews must be resolved before merging.
- Outdated approvals should be dismissed when new commits are pushed.

# Merge Strategy

## Only squash merges allowed

All PRs must be merged using:
- **Squash and merge only**

Do NOT use:
- Merge commits
- Rebase merges (unless explicitly required and approved)

Why:
- Because we exclusively squash merge, the **PR Title** becomes the final commit message on `main`.
- Keeps history clean (one commit per feature).
- Improves rollback safety.
- Allows Release Please to generate the changelog properly based on Conventional Commits.

---

## Branch cleanup

After merge:
- Feature branches should be deleted automatically.

---

# Git Execution Rules

To prevent messy Git histories and dependency conflicts:
- Always use `git pull --rebase` when updating branches. Never create local merge commits.
- **NEVER** manually edit lock files (e.g., `composer.lock`, `package-lock.json`). ALWAYS use the package manager command (e.g., `composer update`, `npm install`) to generate them.
- Always run local linters and formatters before creating a commit.

---

# Rebase Rules

## Allowed locally

Developers may:
- Rebase feature branches against `main`.
- Amend commits.
- Squash commits locally before opening a PR.

---

## Forbidden on shared branches

Never:
- Rebase `main`.
- Force push to `main`.
- Rewrite shared history of any active PR being collaborated on.

---

# CI / Automation Rules

## CI must always pass

No exceptions.

CI includes:
- Linting and Formatting
- Static analysis
- Package manager validation
- Commit validation (Commitlint)

---

## Dependabot rules

Dependabot PRs:
- Must pass CI.
- Should be squash merged.
- Should not mix multiple dependency groups unless grouped by Dependabot itself.

---

## Release Please rules

Release Please is the only system allowed to:
- Create versions.
- Create tags.
- Generate changelogs.
- Publish releases.

Never manually:
- Create tags.
- Bump versions in code or metadata files.
- Edit release commits.

---

# AI Agent Rules (Strict Directives)

AI agents must follow stricter constraints than humans. You must act deterministically and prioritize system safety.

## 1. Context & Execution (Read Before Write)
- **Do not guess context.** Before writing code, use search/grep to read existing project patterns, interfaces, and utilities.
- **Match existing style.** If the project uses specific naming conventions or design patterns, you must copy them exactly.

## 2. Allowed actions
- Create feature branches.
- Open pull requests.
- Commit changes.
- Run tests and linters locally.
- Fix simple, isolated issues.
- Read CI logs if a build fails and attempt to push a fix.

## 3. Forbidden actions (CRITICAL)
You must **NEVER**:
- Push directly to `main`.
- Force push (`git push -f`) to any shared or remote branch.
- Delete remote branches or tags.
- Rewrite Git history on shared branches.
- Bypass or disable CI checks.
- Merge Pull Requests.
- Approve Pull Requests.
- Modify GitHub Actions/Workflows (`.github/workflows/`) without explicit human approval.

## 4. Anti-Loop & Error Handling (Stop Conditions)
AI agents must not get stuck in infinite loops.
- **CI Failures:** If a commit fails CI, you may attempt to fix it. If you fail to fix the CI after **3 consecutive attempts**, you MUST STOP, do not push again, and request human assistance.
- **Merge Conflicts:** If you encounter a Git merge conflict, DO NOT attempt to resolve it by guessing. Stop and notify the user.

## 5. AI Commit & PR Discipline
- Be minimal and focused. Do not refactor unrelated code you happen to be viewing.
- Never commit debugging artifacts (`var_dump`, `console.log`, `print()`, commented-out code).
- Follow Conventional Commits strictly. Do not deviate from the allowed types.
- **MANDATORY**: You must use the EXACT correct prefix for the type of work performed. Getting prefixes wrong pollutes the changelog and breaks semantic versioning. Pay close attention to these commonly confused prefixes:
  - **`ci:` vs `build:`**
    - `ci:` is ONLY for GitHub Actions, workflows, and CI configuration (e.g., `.github/workflows/`, `commitlint.yml`).
    - `build:` is for scripts and tools that compile, scope, or bundle the project (e.g., `scripts/scope`, `scripts/update-version`, `composer.json`).
  - **`fix:` vs `ci:` / `build:`**
    - `fix:` is ONLY for fixing bugs in the **application/plugin code**.
    - **Never** use `fix:` if you are fixing a broken GitHub Action (use `ci:`) or a broken build script (use `build:`).
  - **`refactor:` vs `chore:`**
    - `refactor:` is for rewriting **application code** logic without changing its external behavior (e.g., extracting a method, renaming variables).
    - `chore:` is for repository maintenance, dependency updates, and tooling tweaks that don't directly affect production code, builds, or CI.
  - **`style:` vs `refactor:`**
    - `style:` is STRICTLY for formatting changes (whitespace, PSR-12 fixes, PHP_CodeSniffer fixes). It does not change the AST.
    - `refactor:` involves actual structural code changes.

## 6. AI Safety Rule
If uncertain about a command, a requirement, or an architectural decision:
```
Do not guess. Do not assume. Stop and request clarification from the human developer.
```

---

# Security Rules

Never commit:
- API keys
- Tokens
- Passwords
- Private certificates
- `.env` files
- Secrets of any kind

Use:
- `.env.example` for structure and documentation only.

---

# Hotfix Policy

For critical production issues:

1. Create a hotfix branch from `main` (e.g., `fix/critical-login-crash`).
2. Apply the minimal fix only. No refactoring.
3. Open a PR immediately.
4. Run CI.
5. Get approval.
6. Squash merge.
7. Let Release Please process the release and generate the patch version.

No shortcuts allowed.

---

# Definition of Done

A change is only complete when:

- Code is implemented.
- CI is green (PHPCS + PHPStan).
- Commitlint passes.
- PR is reviewed and approved.
- PR is merged via Squash and merge.
- Release Please successfully processes versioning (if applicable).

---

# Final Rule

Git history is part of system integrity.

Every change must be:
- Intentional
- Reviewable
- Traceable
- Reversible

If a change does not meet these standards, it must not be merged.
