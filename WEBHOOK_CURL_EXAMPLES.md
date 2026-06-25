# NymCard Webhook cURL Examples

## Webhook Endpoint
```
POST /api/webhooks/nymcard
```

## Example 1: Payment Flow Initiated
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.flow.initiated",
    "timestamp": "2026-01-05T12:00:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "success",
    "metadata": {
      "currency": "AED",
      "amount": "200.00"
    }
  }'
```

## Example 2: Payment Flow Completed
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.flow.completed",
    "timestamp": "2026-01-05T12:05:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "success",
    "metadata": {
      "currency": "AED",
      "amount": "200.00",
      "transaction_id": "TXN-123456"
    }
  }'
```

## Example 3: Payment Flow Failed
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.flow.failed",
    "timestamp": "2026-01-05T12:05:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "failure",
    "metadata": {
      "currency": "AED",
      "amount": "200.00",
      "reason": "Insufficient funds"
    }
  }'
```

## Example 4: Consent Granted
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "consent.granted",
    "timestamp": "2026-01-05T12:01:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "success",
    "metadata": {}
  }'
```

## Example 5: Consent Revoked
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "consent.revoked",
    "timestamp": "2026-01-05T12:02:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "failure",
    "metadata": {}
  }'
```

## Example 6: System Error
```bash
curl -X POST http://localhost:8000/api/webhooks/nymcard \
  -H "Content-Type: application/json" \
  -d '{
    "event": "system.error",
    "timestamp": "2026-01-05T12:00:00Z",
    "resourceId": "mock-resource-12345678",
    "flowType": "SIP",
    "status": "failure",
    "metadata": {
      "error_code": "ERR_001",
      "error_message": "Internal processing error"
    }
  }'
```

## Notes:
1. Replace `mock-resource-12345678` with an actual `nymcard_resource_id` from your `app_user_payments` table
2. Replace `http://localhost:8000` with your actual domain/URL
3. The `resourceId` must match an existing payment's `nymcard_resource_id` in the database
4. All timestamps should be in ISO 8601 format
5. The webhook endpoint is public (no authentication required)

## To get a real resource ID from database:
```bash
php artisan tinker
>>> App\Models\AppUserPayment::whereNotNull('nymcard_resource_id')->first()?->nymcard_resource_id
```

