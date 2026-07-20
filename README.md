# KTV LOUNGE - Karaoke on Vercel + Turso

Migrated from PHP/MySQL to TypeScript/Turso for free Vercel Hobby + Turso deployment.

## Architecture

```
public/             → Static HTML/CSS/JS (Vercel static serving)
api/*.ts            → Vercel Functions (API endpoints)
src/
  db/
    schema.ts       → Turso/SQLite table definitions
    index.ts        → Database client connection
    seed.ts         → Default settings & admin account
    migrate.ts      → Migration script
  lib/
    response.ts     → JSON response helpers
    auth.ts         → JWT auth, token generation
    validation.ts   → Sanitization helpers
```

## Deployment Prerequisites

1. **Vercel account** (hobby tier - free): https://vercel.com
2. **Turso account** (free tier - 500MB): https://turso.tech

## Step-by-Step Deployment

### 1. Create Turso Database

```bash
# Install Turso CLI
npm install -g @turso/cli

# Login
turso auth login

# Create database
turso db create ktv-lounge

# Get database URL and auth token
turso db show ktv-lounge --url
turso db tokens create ktv-lounge
```

Save the URL and token — you'll need them next.

### 2. Push to GitHub

```bash
git init
git add .
git commit -m "Initial Vercel + Turso migration"
git remote add origin https://github.com/YOUR_USER/ktv-lounge.git
git push -u origin main
```

### 3. Deploy to Vercel

- Go to https://vercel.com → Add New Project → Import GitHub repo
- In **Environment Variables**, add:

| Variable | Value |
|----------|-------|
| `TURSO_DATABASE_URL` | `libsql://your-db.turso.io` (from step 1) |
| `TURSO_AUTH_TOKEN` | Your Turso auth token |
| `ADMIN_USERNAME` | `admin` (default) |
| `ADMIN_PASSWORD` | Change this to a secure password |
| `JWT_SECRET` | A random string (for admin JWT tokens) |

- Deploy — Vercel auto-detects the TypeScript api/ directory

### 4. Run Database Migration

After first deploy, run the migration to create tables:

```bash
# In Vercel dashboard, go to your project → Cron Jobs
# Or trigger via: https://your-app.vercel.app/api/migrate
```

Alternatively, the tables auto-create on first API call.

### 5. Access Admin Panel

Go to `https://your-app.vercel.app/admin/login.html`
Login with: `admin` / the password you set in env vars

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `TURSO_DATABASE_URL` | Yes | Turso database connection URL |
| `TURSO_AUTH_TOKEN` | Yes | Turso authentication token |
| `ADMIN_USERNAME` | No | Default: `admin` |
| `ADMIN_PASSWORD` | No | Default: `admin123` (change in production!) |
| `YOUTUBE_API_KEY` | No | YouTube API v3 key for search |
| `JWT_SECRET` | No | Secret for admin JWT tokens |
| `TIMEZONE` | No | Default: `Asia/Manila` |
| `MAX_QUEUE_PER_ROOM` | No | Default: `50` |

## What Changed from PHP Version

| Area | PHP (Old) | TypeScript (New) |
|------|-----------|-------------------|
| Runtime | PHP 8.x on Apache | Node.js on Vercel Functions |
| Database | MySQL/MariaDB | Turso (SQLite-compatible) |
| Auth | PHP sessions | JWT tokens (Bearer) |
| Frontend | PHP-rendered HTML | Static HTML + JS API calls |
| Admin auth | `$_SESSION` | JWT in localStorage |
| Deploy | cPanel/InfinityFree | Vercel (`git push`) |

## Local Development

```bash
npm install
npm run dev   # Starts Vercel dev server
```

## Turso SQL → MySQL Differences

- Use `datetime('now')` instead of `NOW()`
- Use `INTEGER PRIMARY KEY AUTOINCREMENT` instead of `INT AUTO_INCREMENT`
- Use `TEXT CHECK(col IN (...))` instead of `ENUM(...)`
- Use `ON CONFLICT DO UPDATE SET` instead of `ON DUPLICATE KEY UPDATE`
- Use `INSERT OR IGNORE` instead of `INSERT IGNORE`
