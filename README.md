# KavoshRank WordPress Plugin

Official WordPress connector for the KavoshRank AI SEO and Audio Studio platform.

[![Latest Version](https://img.shields.io/github/v/release/Kavoshrank/wordpress?label=version)](https://github.com/Kavoshrank/wordpress/releases/latest)
[![WordPress Compatibility](https://img.shields.io/badge/WordPress-6.4%2B-blue)](https://wordpress.org)
[![PHP Compatibility](https://img.shields.io/badge/PHP-8.1%2B-purple)](https://php.net)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](LICENSE)

Read this documentation in Persian: [مطالعه راهنما به زبان فارسی (Persian)](README.fa.md)

---

## Overview

KavoshRank connects WordPress websites directly to the KavoshRank AI engine, providing:
- AI Studio Content Automation: Direct publishing and draft staging of AI-generated articles and landing pages.
- AI Radio & Audio Studio (TTS): Automatic conversion of WordPress blog posts into natural radio podcasts with five distinct narrator voices and embedded modern audio players.
- Semantic Internal Linking: Automated replacement and enrichment of contextual internal links.
- Schema & E-E-A-T Structured Data: Injection of Article, FAQ, Author, and Product schema graphs.
- Real-time Diagnostic Handshake: Secure bi-directional REST communication protected by cryptographic site tokens.
- One-Click In-Dashboard Updates: Automatic update notifications and direct one-click upgrades within WordPress Admin.

---

## Requirements

- WordPress 6.4 or higher
- PHP 8.1 or higher
- PHP extensions: `curl`, `json`, `openssl`, `mbstring`
- Active account on [KavoshRank Platform](https://kavoshrank.ir)

---

## Quick Start Guide

### Step 1: Create an Account on KavoshRank

1. Visit [kavoshrank.ir](https://kavoshrank.ir) and click Register.
2. Enter your email and verify your account.
3. Access the Customer Panel at [panel.kavoshrank.ir](https://panel.kavoshrank.ir).
4. Select your desired subscription tier and create a new project with your WordPress site URL (e.g. `https://yourdomain.com`).

### Step 2: Download and Install the Plugin

#### Option A: Direct ZIP Upload (Recommended)
1. Download the latest `kavoshrank.zip` from [Releases](https://github.com/Kavoshrank/wordpress/releases/download/latest/kavoshrank.zip).
2. Log into your WordPress Admin (`/wp-admin`).
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Choose the downloaded `kavoshrank.zip` file, click **Install Now**, and activate the plugin.

#### Option B: Manual Installation via FTP/SFTP
1. Download `kavoshrank.zip` and extract its contents.
2. Upload the `kavoshrank` directory into your WordPress `wp-content/plugins/` directory.
3. Log into WordPress Admin, go to **Plugins**, and activate **KavoshRank SEO Client**.

### Step 3: Connect Plugin to the KavoshRank Platform

1. In the KavoshRank Customer Panel, select your project and navigate to **Settings > Connection**.
2. Click **Generate Connection Key** and copy the generated token.
3. In your WordPress Admin dashboard, click **KavoshRank** in the sidebar.
4. Paste your token into the **Connection Key** input field and click **Verify & Connect**.
5. Once verified, the status indicator will turn green, indicating that your WordPress site is securely connected to the central platform.

---

## Automatic Updates

The plugin includes an automatic update channel. Whenever a new release is published on GitHub, WordPress automatically detects the new version in **Plugins > Installed Plugins** and allows you to update with one click.

You can also force a check at any time by visiting **Dashboard > Updates** in WordPress Admin.

---

## Contributing

We welcome community bug reports, feature suggestions, and pull requests.
Please review [CONTRIBUTING.md](CONTRIBUTING.md) before submitting contributions.

### Reporting Bugs
If you encounter an issue or error, please submit a bug report via [GitHub Issues](https://github.com/Kavoshrank/wordpress/issues/new?template=bug_report.yml).

### Feature Requests
To suggest an enhancement or new capability, open a feature request via [GitHub Issues](https://github.com/Kavoshrank/wordpress/issues/new?template=feature_request.yml).

---

## Security Vulnerabilities

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) and report it responsibly rather than opening a public issue.

---

## License

This project is licensed under the terms of the GNU General Public License v2.0 or later (GPL-2.0-or-later). See the [LICENSE](LICENSE) file for complete details.
