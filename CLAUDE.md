# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Portfolio website for Micode with minimal visit tracking. Static HTML/CSS/JavaScript frontend, PHP/MySQL backend for logging.

## Tech Stack

- **Frontend:** Vanilla HTML, CSS, JavaScript
- **Backend:** PHP 7.2+ with PDO MySQL
- **Hosting:** Hostinger

## Commands

```bash
php init-db.php   # Create visits table
```

## Architecture

- `index.html` — Portfolio (single-page, dark theme, cyan accent #00d4ff)
- `stats.php` — Logs visits via `?url=...`, returns 1x1 GIF, fails silently
- `init-db.php` — Creates `visits` table

## Database

```sql
visits (id, url VARCHAR(2000), created_at TIMESTAMP)
```

Tracking captures full URL including UTM params and query strings.
