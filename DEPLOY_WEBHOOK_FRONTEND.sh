#!/bin/bash

echo "=========================================="
echo "Webhook Frontend Deployment"
echo "=========================================="

# Step 1: Add all changes
echo "Step 1: Adding all changes to git..."
git add .

# Step 2: Commit changes
echo "Step 2: Committing changes..."
git commit -m "Complete webhook frontend implementation

- Updated AdminWebhookLogs.js with new webhook system
  - Changed endpoint from /api/secure/webhooks to /api/admin/webhooks
  - Added 11 columns: event_id, transaction_ref, direction, company_name, event_type, provider_name, status, attempt_count, http_status, created_at, actions
  - Added expandable row to view full payload and response
  - Added retry button for failed webhooks
  - Added Iconify icons for expand/collapse and retry actions

- Updated WebhookLogs.js (company view) with sanitized fields
  - Uses /api/webhooks endpoint
  - Shows only: event_id, transaction_ref, event_type, delivery_status, http_status, attempt_count, last_attempt_at, created_at
  - No raw payloads or internal logs visible

- Added webhook routes to routes/api.php
  - Admin routes: GET /admin/webhooks, GET /admin/webhooks/{id}, POST /admin/webhooks/{id}/retry
  - Company routes: GET /webhooks, GET /webhooks/{id}

Backend already deployed:
- webhook_events table migration
- WebhookEvent model
- IncomingWebhookService, OutgoingWebhookService, WebhookRetryService
- AdminWebhookController, CompanyWebhookController
- RetryFailedWebhooks command
- Cron job configured in Kernel.php

Ready for production deployment"

# Step 3: Push to GitHub
echo "Step 3: Pushing to GitHub..."
git push origin main

echo ""
echo "=========================================="
echo "✓ Changes pushed to GitHub successfully!"
echo "=========================================="
echo ""
echo "Next steps on server:"
echo "1. Pull changes: git pull origin main"
echo "2. Run migration: php artisan migrate --force"
echo "3. Build frontend locally (npm not on server):"
echo "   cd frontend && npm run build"
echo "4. Copy build to server via SCP"
echo "5. Clear Laravel caches:"
echo "   php artisan config:clear"
echo "   php artisan route:clear"
echo "   php artisan cache:clear"
echo "6. Test webhook pages:"
echo "   - Admin: /secure/webhooks"
echo "   - Company: /dashboard/webhook"
echo ""
echo "Webhook system features:"
echo "- Role-based visibility (admin sees all, company sees sanitized)"
echo "- Expandable rows to view payloads/responses (admin only)"
echo "- Manual retry for failed webhooks (admin only)"
echo "- Automatic retry via cron (every minute)"
echo "- Exponential backoff: 1min, 5min, 15min, 1hr, 6hrs"
echo "=========================================="
