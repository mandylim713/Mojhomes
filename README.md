# Mojhometouch website — deploy on Render

Plain PHP 8.3 + SQLite, packaged as a Docker service. Everything sets itself up automatically:
the database is created on first start, backups run on their own, and the latest backup is
restored automatically if the server ever starts without its data.

---

## 1. Put the code on GitHub (PRIVATE repository)

1. Create a **private** repository on GitHub, e.g. `mojhometouch`.
2. Upload/push the contents of this folder to the `main` branch.

> The repository must stay **private** — backups contain customer names, phones and addresses.
> The site refuses to back up to a public repository.

## 2. Create a GitHub token for backups

GitHub → Settings → Developer settings → **Fine-grained tokens** → Generate new token

- Repository access: **Only select repositories** → your `mojhometouch` repo
- Permissions → Repository → **Contents: Read and write**
- Expiration: the longest allowed (set a reminder to renew it)

Copy the token (starts with `github_pat_…`).

## 3. Deploy on Render

Render dashboard → **New → Blueprint** → connect your GitHub → pick the repo.
Render reads `render.yaml` and asks for these values:

| Variable | What to enter |
|---|---|
| `SITE_URL` | `https://mojhometouch.onrender.com` (or your own domain later) |
| `ADMIN_EMAIL` | the email you'll log in with |
| `ADMIN_PASSWORD` | a strong password (min 8 characters) |
| `GITHUB_BACKUP_REPO` | `yourGitHubName/mojhometouch` |
| `GITHUB_BACKUP_TOKEN` | the token from step 2 |
| `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `MAIL_FROM` | your email account's SMTP details (see step 5) |

Click **Apply**. The first build takes a few minutes. Then open `SITE_URL/admin/`.

### Plan: Starter (recommended) or Free

`render.yaml` uses the **Starter** plan with a 1 GB persistent disk, so the site never sleeps and
data stays on the server between deploys. GitHub backups still run as an off-site copy.
Check Render's pricing page for the current cost.

**Free plan instead:** in `render.yaml` change `plan: starter` to `plan: free` and delete the
`disk:` block (4 lines). The site still works — every time it wakes up or redeploys, it restores
automatically from the GitHub backup. On Free, the site sleeps after ~15 minutes without visitors
(first visit then takes up to a minute).

## 4. First login checklist (Admin → Dashboard)

The dashboard shows a checklist:
1. Add the order-notification email (Settings → Business details)
2. Add the company bank account (Settings → Payments) — enables bank-transfer checkout
3. **Set real product prices and stock** (sample prices were loaded) → then tick “I've reviewed prices”
4. Review delivery zones and fees
5. Optional: connect Flutterwave for card/transfer/USSD payments
6. Upload real project photos (Projects Gallery) and product photos

## 5. Email (order notifications)

Render has no mail server, so the site sends email through SMTP. Use the details from:
- Your domain email (cPanel → Email Accounts → Connect Devices): host `mail.yourdomain.com`, port 465 with `SMTP_SECURE=ssl`, or 587 with `tls`
- Zoho Mail, Brevo, or Gmail (with an App Password)

Test it: Admin → Settings → Email → **Save & send test email**. (SMTP can also be entered there
instead of in Render.)

## 6. Flutterwave (online payments, optional)

Admin → Settings → Payments → tick Flutterwave, paste the **live** Public and Secret keys, and
type any long random text as the *Webhook secret hash*. In the Flutterwave dashboard → Settings →
Webhooks, paste the Webhook URL shown in Admin and the same secret hash.
Every payment is verified with Flutterwave before an order is marked paid.

## 7. Custom domain

Render → your service → Settings → Custom Domains → add `mojhometouch.com` and follow the DNS
instructions. Then update `SITE_URL` to `https://mojhometouch.com`.

---

## Backups — how they work

- **Automatic:** within a minute or two of any change (order, payment, product, setting, upload)
  the site saves a backup, and once more whenever Render stops the server for a deploy/restart.
- **Where:** the `backup/` folder on the **`db-backup` branch** of your GitHub repo —
  `database.sqlite`, `uploads/` (product & gallery images), `proofs/` (payment receipts).
  Every commit on that branch is a restore point. Your `main` branch is never touched, so pushes
  you make to `main` never conflict with backups, and backups don't trigger redeploys.
- **Also on the server:** the newest 14 copies in `/backups` (on the disk).
- **Auto-restore:** if the server starts with no database, it restores the latest backup before
  the site opens. If GitHub is briefly unreachable, it keeps retrying and heals itself as long as
  no new orders were taken in the meantime.
- **Protection:** an empty/different database never overwrites your GitHub backup automatically.
  If that situation happens, Admin shows a red alert with a one-click **Restore from GitHub**.
- **Admin → Backups:** back up now, download a full backup (.zip), restore from GitHub, restore
  a server copy, or upload a `.sqlite` file to restore.

### Emergency manual restore (if all else fails)
1. On GitHub open the `db-backup` branch → `backup/database.sqlite` → Download.
2. Rename it `db-20990101-000000.sqlite` and put it in the `backups/` folder of `main`.
3. Push, then on Render: Manual Deploy → **Clear build cache & deploy**, and (Starter plan) delete
   `storage/mojhometouch.sqlite` from the disk via Render Shell: `rm /var/data/storage/mojhometouch.sqlite`
   then restart. The file is loaded automatically on start.

---

## Hosting on cPanel instead (Namecheap etc.)

Upload the folder contents to `public_html`, make sure `storage/`, `uploads/` and `backups/` are
writable (755/775). No environment variables are needed; mail uses the server's mail().
For automatic GitHub backups on cPanel, set the `backup` values in `config.php` and add a cron job
(every 10 minutes): `php /home/USER/public_html/cli/backup.php auto`

## Default login (only if ADMIN_EMAIL / ADMIN_PASSWORD were not set)
`admin@mojhometouch.com` / `Moj@Admin2026` — the dashboard forces you to change it.

Built by Digital WebOracle ICT (DWO).
