# Stripe Webhook Setup

File Converter uses Stripe webhooks to update subscription status and grant credits after checkout. Webhooks must be configured for billing to work correctly.

---

## Required Environment Variables

```env
STRIPE_KEY=pk_test_...          # Publishable key (safe to expose in frontend)
STRIPE_SECRET=sk_test_...       # Secret key (never expose publicly)
STRIPE_WEBHOOK_SECRET=whsec_... # Webhook signing secret
CASHIER_CURRENCY=eur            # or usd
```

---

## Webhook Endpoint

The application listens for Stripe events at:

```
POST /stripe/webhook
```

This route is registered by Laravel Cashier and bypasses CSRF verification.

---

## Required Stripe Events

Register the following events in your Stripe webhook configuration:

| Event | Purpose |
|-------|---------|
| `customer.subscription.created` | Activate subscription, update plan |
| `customer.subscription.updated` | Handle plan changes, renewals |
| `customer.subscription.deleted` | Downgrade to free plan |
| `invoice.payment_succeeded` | Confirm subscription renewal |
| `checkout.session.completed` | Grant credits after credit pack purchase |

---

## Local Testing with Stripe CLI

Install the [Stripe CLI](https://stripe.com/docs/stripe-cli), then:

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

The CLI prints a webhook signing secret (`whsec_...`). Set it in `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Trigger a test event:

```bash
stripe trigger checkout.session.completed
stripe trigger customer.subscription.created
```

---

## Production Setup

1. Go to Stripe Dashboard → Developers → Webhooks
2. Click **Add endpoint**
3. URL: `https://yourdomain.com/stripe/webhook`
4. Select the events listed above
5. Copy the **Signing secret** → set `STRIPE_WEBHOOK_SECRET` in `.env`

---

## Idempotency

Webhook handlers check if the event has already been processed (using `billing_webhook_events` table). Duplicate deliveries from Stripe are ignored safely.

---

## Inspecting Failed Webhooks

In the Stripe Dashboard, navigate to **Developers → Webhooks → [your endpoint]** to see delivery attempts and error responses.

Locally, check `storage/logs/laravel.log` for webhook handler errors.

---

## Security

- Never log `STRIPE_SECRET` or full Stripe event payloads containing payment method details
- The `STRIPE_WEBHOOK_SECRET` is used to verify that events genuinely came from Stripe
- Rotate the webhook secret if it is ever exposed
