# Contributing to KavoshRank WordPress Plugin

Thank you for your interest in contributing to the KavoshRank WordPress Plugin! We welcome bug reports, improvements, documentation updates, and feature pull requests from the open-source community.

---

## Code of Conduct

We expect all contributors to adhere to standard open-source collaboration practices:
- Be respectful, constructive, and professional in all communications.
- Focus on technical merit, maintainability, and code quality.
- Do not include proprietary, malicious, or non-GPL compatible code.

---

## Submitting Pull Requests

To ensure a smooth review process and fair resource allocation, please adhere to these rules:

1. **Single Open PR Limit**:
   - Each contributor is allowed **at most one open pull request at a time**.
   - If you already have an open pull request, please wait until it is reviewed, merged, or closed before opening another.
   - Additional pull requests opened while one is active will be automatically closed by our CI automation.

2. **Branching Strategy**:
   - Fork the repository to your own GitHub account.
   - Create a feature or fix branch from `main` (e.g. `feature/audio-player-controls` or `fix/rest-nonce-verification`).
   - Keep commits small, focused, and well-described.

3. **Coding Standards**:
   - Follow WordPress Coding Standards (WPCS).
   - Write PHP code targeting PHP 8.1+ compatibility.
   - Use strict typing where applicable and sanitize/escape all input and output using standard WordPress functions (`sanitize_text_field`, `esc_html`, `wp_kses_post`, etc.).
   - Verify nonces and user capabilities on every admin action (`current_user_can`, `check_admin_referer`).

4. **Local Verification**:
   Before opening a PR, verify syntax across all PHP files:
   ```bash
   find . -name "*.php" -exec php -l {} \;
   ```
   Ensure zero syntax errors or warnings.

---

## Reporting Bugs

Before creating a bug report, please check existing open issues to avoid duplicates.
When reporting a bug, use the [Bug Report Template](.github/ISSUE_TEMPLATE/bug_report.yml) and include:
- A clear description of the bug.
- Steps to reproduce the issue.
- Expected behavior versus actual behavior.
- Relevant WordPress version, PHP version, and web server environment.
- Any error logs or stack traces (redacting any private tokens or site credentials).

---

## Requesting Features

If you have an idea for a feature or enhancement, please open a [Feature Request](.github/ISSUE_TEMPLATE/feature_request.yml) with:
- The problem your feature aims to solve.
- Your proposed solution or user experience workflow.
- Any alternative solutions you considered.

---

## License

By contributing to this project, you agree that your contributions will be licensed under the project's [GNU General Public License v2.0 or later](LICENSE).
