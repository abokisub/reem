# Logs Pages - Quick Reference

## 🎉 Problem Solved!
The 404 errors for webhook logs, API logs, and audit logs have been fixed.

## 📍 Where to Find Them

In the company dashboard sidebar under "MERCHANT" section:
1. **Webhook Logs** - View webhook delivery attempts
2. **API Request Logs** - View API requests made to your endpoints
3. **Audit Logs** - View user activity and changes

## 🔗 Direct URLs

- Webhook Logs: `http://localhost:3000/dashboard/webhook-logs`
- API Logs: `http://localhost:3000/dashboard/api-logs`
- Audit Logs: `http://localhost:3000/dashboard/audit-logs`

Production:
- Webhook Logs: `https://app.pointwave.ng/dashboard/webhook-logs`
- API Logs: `https://app.pointwave.ng/dashboard/api-logs`
- Audit Logs: `https://app.pointwave.ng/dashboard/audit-logs`

## 📊 What Each Page Shows

### Webhook Logs
- Event type (e.g., payment.success)
- Webhook URL where event was sent
- HTTP status code (200, 405, etc.)
- Delivery status (success/failed)
- Number of delivery attempts
- Timestamp

### API Request Logs
- API endpoint called
- HTTP method (GET, POST, PUT, DELETE)
- Response status code
- Client IP address
- Response time in milliseconds
- Timestamp

### Audit Logs
- Action performed (create, update, delete, login)
- User who performed the action
- Resource affected (customer, account, etc.)
- IP address
- Timestamp

## 🎨 Features

All three pages include:
- ✅ Pagination (5, 10, 20, 50 rows per page)
- ✅ Dense view toggle (fit more rows on screen)
- ✅ Color-coded status indicators
- ✅ Responsive design
- ✅ Loading states
- ✅ Empty state messages
- ✅ Real-time data from database

## 🔐 Access Requirements

- Must be logged in as company user
- Business account must be activated
- Only shows logs for your company (data isolation)

## 📝 Current Data

For PointWave Business (Company ID: 2):
- **Webhook Logs**: 1 record (failed delivery to easeid.ai)
- **API Request Logs**: 0 records (no API calls yet)
- **Audit Logs**: 0 records (no activity logged yet)

## 🚀 Testing

To test the pages:
1. Login as: abokisub@gmail.com
2. Click on any of the three log pages in sidebar
3. Verify data loads without 404 error
4. Try pagination and dense view toggle

## 🛠️ Backend Endpoints

If you need to access the data programmatically:

```bash
# Webhook Logs
GET /api/secure/webhooks?id={userId}

# API Request Logs
GET /api/secure/api/requests?id={userId}

# Audit Logs
GET /api/secure/audit/logs?id={userId}
```

All return JSON:
```json
{
  "status": "success",
  "data": [...],
  "count": 0
}
```

## ✅ Status: COMPLETE

All pages are working and accessible. No more 404 errors!
