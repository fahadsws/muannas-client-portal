# Muannas Atelier — Client Portal

Laravel 12 app (PHP 8.2+, MySQL). One place where clients review content, give feedback and approve, replacing WhatsApp approvals. Built on the existing campaign-board system (same login, roles, layout and design).

## Who sees what

| Role | How it is set up | Access |
|---|---|---|
| **Admin** | Team → role *Owner* (or *Manager*) | Everything, all clients |
| **Team member** | Team → any other role (e.g. *Editor*) | Only the clients assigned to them (Manage clients → Muannas team), unless the role has "Access every client" |
| **Client** | Manage clients → client logins | Own workspace only: view, comment, approve. Never sees drafts, other clients, or the internal Campaign board |

## Brief checklist (Muannas_Client_Portal_Brief.pdf, module names as in the brief)

| # | Module | Where |
|---|---|---|
| 1 | Users & Access | `/login`, `/forgot-password`; roles under Team and Roles & permissions |
| 2 | Client Dashboard | `/portal/c/{client}` |
| 3 | Content Review | `/portal/c/{client}/content`, `/portal/posts/{id}`, review link `/r/{token}` |
| 4 | Content Calendar | `/portal/c/{client}/calendar` |
| 5 | Projects & Tasks | `/portal/c/{client}/projects` |
| 6 | Files & Assets | `/portal/c/{client}/files` |
| 7 | Notifications | Emails + shareable review link (sent from Content Review) |
| 8 | Admin Panel (named Control Room in the sidebar) | `/portal/admin` (clients and team, bulk upload, all pending approvals) |
| 9 | Payments & Invoices | `/portal/c/{client}/invoices` |
| 10 | Performance Reports | `/portal/c/{client}/reports` |
| 11 | Branding | Agency name and colour in Settings; client logo per client |
| 12 | Technical Requirements | See Handover below |
| 13 | Delivery & Support | `php artisan test` (30 tests) |

Post statuses: Draft → Pending Approval → Changes Requested / Approved → Published. Invoice statuses (Pending, Due Soon = within 3 days, Overdue, Paid) are calculated from the dates.

## Install

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# edit .env: APP_URL, DB_*, MAIL_*, QUEUE_CONNECTION, PORTAL_ADMIN_*
php artisan migrate --force
php artisan db:seed --force          # platform admin (+ Muannas agency & first admin when PORTAL_ADMIN_EMAIL/PASSWORD are set)
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Set `APP_ENV=production`, `APP_DEBUG=false`. Point the web root at `public/`. `storage/` and `bootstrap/cache` must be writable. Do **not** run `PortalDemoSeeder` in production.

### Required server settings

- **Cron** (invoice reminders, run every minute): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
- **Mail**: real SMTP in `.env` (`MAIL_MAILER=smtp`, host, port, username, password, `MAIL_FROM_ADDRESS`). With `MAIL_MAILER=log` nothing is delivered.
- **Queue**: emails are queued. `QUEUE_CONNECTION=sync` sends instantly (simplest). With `database`, run a worker: `php artisan queue:work --tries=3` under Supervisor.
- **PHP upload limits** (php.ini): `upload_max_filesize=26M`, `post_max_size=64M`, `max_file_uploads=30`. Bulk upload accepts up to 60 images, but the server only receives `max_file_uploads` per request — upload in batches if you hit it.
- **HTTPS** on `portal.muannasatelier.com`; set `SESSION_SECURE_COOKIE=true`.

## Data & files

All uploads (post images, files, invoices, reports, logos) live in `storage/app/private`, outside the web root, and are only served through checked routes. Back up that folder and the database together. Reels are links only (YouTube, Google Drive, Frame.io, Vimeo), so storage stays small.

## Handover (brief section 12)

Code, hosting, domain and database must sit under Muannas Atelier accounts: create the server, domain and database in their accounts, deploy this repo from their GitHub, and give them the admin login (`PORTAL_ADMIN_*`). Rotate every password on handover.

### Running costs to share before the build is accepted

No prices are fixed here: confirm current quotes with each provider.

| Item | Needed for |
|---|---|
| Web hosting / VPS with PHP 8.2, MySQL, cron | Running the portal |
| Domain `muannasatelier.com` (+ DNS for the `portal` subdomain) | Address |
| SSL certificate | HTTPS (free with Let's Encrypt) |
| Transactional email service (or business mailbox SMTP) | All notification emails |
| Backups (hosting snapshot or storage) | Database + `storage/app/private` |

## Demo data (local only)

```bash
php artisan db:seed --class=PortalDemoSeeder
```
Creates `admin@muannas.test`, `team@muannas.test`, `client@noor.test`, `client@atlas.test` (password `Portal@12345`).

## Phase 2 (not built, as per the brief)

Arabic version, WhatsApp notifications, online card payments, automatic performance data from Meta and TikTok.
