# Payment and order workflow testing

Apoint supports direct Safaricom Daraja STK Push for customer checkout. PayHero remains in the codebase but is not used by the customer checkout while Apoint waits for PayHero's response.

The customer checkout route is `POST /orders/{order}/payments`. Daraja sends callbacks to `POST /mpesa/callback`.

## Local Daraja configuration

Use Safaricom Daraja sandbox credentials in your local `.env` file. Never commit credentials or paste them into chat.

```dotenv
MPESA_CONSUMER_KEY=your_sandbox_consumer_key
MPESA_CONSUMER_SECRET=your_sandbox_consumer_secret
MPESA_SHORTCODE=your_sandbox_shortcode
MPESA_PASSKEY=your_sandbox_passkey
MPESA_CALLBACK_URL=https://your-public-https-tunnel.example/mpesa/callback
MPESA_SANDBOX=true
```

The callback URL must be publicly reachable over HTTPS. For local testing, expose your local Laravel server through a trusted HTTPS tunnel and set the exact tunnel URL plus `/mpesa/callback`. Restart the tunnel and update the URL if its hostname changes.

After editing `.env`, run:

```bash
php artisan config:clear
php artisan optimize:clear
```

The order page displays checkout only when all five Daraja credentials/settings are present, the order is in KES with a whole-number total, and the order is awaiting payment.

## Automated coverage

Tests should fake the Daraja OAuth and STK Push HTTP responses and exercise the application without contacting Safaricom or sending a real prompt. Run:

```bash
php artisan test --compact tests/Feature/Payments
php artisan test --compact
```

Automated tests do not prove that real Safaricom credentials, callback reachability, or a real STK prompt work.

## Manual local acceptance sequence

1. Create a client account and verify the email.
2. Submit a quote request, upload a PDF or image, and inspect it from the admin request page.
3. Save and send a quote; confirm the client receives it.
4. Accept the quote as the client. Confirm the order is created.
5. Open the order view. Confirm **Pay securely with M-Pesa** appears when Daraja is configured.
6. Enter a Safaricom sandbox test number in international format (for example, the test number Safaricom provides, formatted as `2547XXXXXXXX`) and request the STK Push.
7. Confirm the prompt is received by the sandbox test flow and complete or cancel it.
8. Confirm the Daraja callback updates the matching payment. A successful callback must match checkout and merchant request IDs, receipt number, and amount before the order is marked paid/confirmed.
9. Confirm a failed/cancelled callback does not mark the order paid. Confirm duplicate callbacks do not duplicate payment records or notifications.
10. In Admin → Orders, start processing and then complete the order. Confirm the client receives the expected in-app notifications and completion email.

Do not test using a real customer's number or initiate a real-money transaction unless Apoint explicitly intends to do so. Never manually mark an order paid to simulate a successful callback.

## PayHero status

PayHero credentials and callback configuration are intentionally not required for the customer checkout path. Do not enable PayHero or switch the checkout back to it until Apoint receives and reviews PayHero's response.
