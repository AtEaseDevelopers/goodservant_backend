# Snoodle backend (Good Servant Food Industries)

Laravel admin panel and driver API for the Snoodle van-sales system. The driver
mobile app is a separate Flutter repository (`van_sales`, branch `snoodle`).

**This repository is public.** Never commit passwords, tokens, keys, `.env`
values or server login details here.

## Working agreement

After the owner asks for a change and it is done and checked:

1. Commit on `main` with a short message saying what changed.
2. Push `main` to `origin`.
3. Deploy to production and run migrations.
4. Tell the owner what is live and what was not verified.

Only deploy when the owner has asked for the change to go live. If they say
"don't deploy yet", commit locally and stop.

## Deploying this backend

No server login is needed, only push access to this repository:

1. Push `main`.
2. Open the site's pull route (`routes/web.php` -> `DeployWebhookController::pull`):
   `curl -sk -m 180 https://sales.snoodle.com.my/git-pull`
   It pulls `main`, runs `composer install` and rebuilds the caches. Read the
   output: it must show the pushed commit and no `error` / `fatal`.
3. If the change has a migration, run it the same way:
   `curl -sk -m 180 https://sales.snoodle.com.my/migrate`
4. Check `https://sales.snoodle.com.my/login` still returns 200.

`curl` needs `-k` on Windows. A `DEPLOY.local.md` file (git-ignored) may exist
in this folder with SSH access for one-off checks on the server; it is optional
for deploying. Never look for or guess credentials.

## Things that bite

- Many PHP and Blade files use CRLF line endings. Keep them; scripted edits
  must preserve CRLF or `str_replace` silently matches nothing.
- Another Claude session sometimes works on this repo. Run `git fetch` and
  `git status` before committing.
- Mobile app text comes from the `mobile_translations` table. A new label in
  the app needs a migration here that adds it in English, Malay and Chinese and
  bumps `mobile_translation_versions`, or Malay/Chinese show the raw key.
- `Trip.date`, `InvoicePayment.created_at` and similar are cast to `d-m-Y`
  strings. Use `getRawOriginal('date')` in queries.
- Drivers are on a trip when their latest `trips` row has `type = 1`. Do not
  rely on `drivers.trip_id`.
- Check changes against the local site (`https://s-noodle.test`, database
  `snoodle`) before deploying. Test data changes inside a rolled-back
  transaction.
