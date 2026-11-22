# Notification delivery in production

Apoint stores in-app notifications in the database and queues branded transactional email separately. Do not use the synchronous queue driver in production.

## Required environment

Set these values in the production environment (keep credentials out of source control):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=your-authenticated-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notifications@your-domain.example
MAIL_FROM_NAME="Apoint Translations"
```

Use the actual SMTP values supplied by your email provider. Verify the sending domain with SPF and DKIM, and publish a DMARC policy. Confirm that the From address is permitted by the SMTP provider.

## Deploy

Run the migration during deployment:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The existing jobs migration creates the database queue tables. The notifications migration creates the in-app notification store.

## Run the queue

On a VPS or managed worker platform, keep a worker supervised and running:

```bash
php artisan queue:work database --sleep=1 --tries=5 --timeout=60
```

On shared hosting without persistent workers, configure a cron job to drain queued mail regularly (adjust the PHP and project paths to the hosting account):

```cron
* * * * * cd /home/ACCOUNT/path-to-app && /usr/bin/php artisan queue:work database --stop-when-empty --tries=5 --timeout=60 --sleep=1 >> storage/logs/queue-worker.log 2>&1
```

The worker timeout must remain shorter than the database queue connection's `retry_after` value (90 seconds by default). Do not run both a cron worker and a separate worker against the same queue unless the hosting setup is designed for concurrent workers.

## Monitor and verify

- Check `storage/logs/laravel.log` and the queue worker log for delivery/SMTP errors.
- Inspect failed jobs with `php artisan queue:failed`; retry after correcting the cause with `php artisan queue:retry all`.
- Send a real test email to addresses on more than one provider (for example, Gmail and Outlook).
- Verify that the email arrives, the From identity is correct, links use the HTTPS production domain, and the in-app bell updates after a page refresh.
- Confirm a queued email is processed after a business transaction commits. A mail provider outage should not roll back a quote, order, or payment update.

Do not consider email delivery operational until the SMTP credentials, DNS authentication, and queue worker/cron are configured and verified on the production host.
