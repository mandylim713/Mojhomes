# Backups folder

The website saves copies of its database here automatically (`db-YYYYMMDD-HHMMSS.sqlite`, newest 14 kept).

**Main, off-site backup:** the site also commits the database, product images and payment receipts
to the `backup/` folder on the **`db-backup` branch** of your private GitHub repository, every time
something changes (checked every minute) and once more whenever Render restarts the server.
Each commit on that branch is a restore point.

**If the server ever loses its data** (new deploy, restart, disk problem) the latest backup is
restored automatically when the site starts. You can also restore manually in **Admin → Backups**.

**Emergency manual restore:** download `backup/database.sqlite` from the `db-backup` branch,
rename it `db-20990101-000000.sqlite`, put it in this folder, commit and push. On the next deploy
(with no database on the server) it will be loaded — see DEPLOY-RENDER.md "Emergency manual restore".

Keep the repository **private** — backups contain customer names, phone numbers and addresses.
