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

The exact deploy commands, server address, login user and where the keys live
are in `DEPLOY.local.md` in this folder. That file is git-ignored on purpose.
If it is missing (fresh clone), ask the owner for it - do not guess or search
for credentials.

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
