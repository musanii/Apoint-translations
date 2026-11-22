# Payment and order workflow testing

Apoint currently initiates customer M-Pesa payments through PayHero STK Push. The public callback endpoint is `POST /payhero/callback`; the direct Daraja callback endpoint is separate and is not the route used by the customer checkout form.

## Provider configuration

Set these values in the deployment environment (never commit credentials):

- `PAYHERO_BASE_URL`
- `PAYHERO_USERNAME`
- `PAYHERO_PASSWORD`
- `PAYHERO_CHANNEL_ID`
- `PAYHERO_CALLBACK_URL`

The callback URL must be publicly reachable over HTTPS and must point to the deployed application's `/payhero/callback` route. Clear the Laravel config cache after changing environment values.

Use a PayHero sandbox/test channel and test phone number if your PayHero account provides them. The configured channel ID is passed to PayHero as the payment channel. Do not use a real customer's number or approve a real-money prompt while performing acceptance testing.

## Automated coverage

The feature tests fake the PayHero HTTP API and exercise the real application checkout and callback services without contacting PayHero or initiating a real STK prompt. They cover:

- STK push initiation and persistence of the checkout request ID
- successful callback and transition to paid/confirmed
- failed/cancelled callback without marking the order paid
- duplicate successful callback idempotency
- ownership protection on checkout initiation

Run:

```bash
php artisan test --compact tests/Feature/Payments
php artisan test --compact tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/PasswordResetTest.php
```

## Manual acceptance sequence

1. Create a client account and confirm the branded verification email arrives.
2. Submit a quote request, upload a PDF or image, and inspect it from the admin request page. Office documents and archives remain downloadable; in-browser preview is limited to PDF, JPEG and PNG.
3. Save a quote draft, publish it, and confirm the client receives the branded quotation email.
4. Accept the quote as the client. Confirm an order appears in the admin Orders workspace.
5. Initiate M-Pesa checkout with a provider-approved test number. Approve or cancel the test prompt.
6. Confirm the provider callback updates the payment record and order payment status. A successful payment should confirm the order; a failed attempt must leave it unpaid/failed.
7. In Admin → Orders, start processing and confirm the client receives an in-app notification. Complete the order and confirm the completion notification and email.
8. Retry a callback payload in a controlled test environment and confirm the payment record and notifications are not duplicated.

## Email delivery

Transactional emails are sent after the database transaction commits through the configured Laravel mail transport. They no longer depend on a continuously running database queue worker. Configure a real SMTP/API mail transport and a valid `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`. A successful application response does not prove that the receiving mailbox accepted or displayed the message; confirm delivery in the mailbox and inspect Laravel logs for transport errors.
