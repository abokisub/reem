# 🚀 Deploy Now - Quick Commands

## 🔴 CRITICAL: Fix Database Errors First

Webhooks are currently failing! Run this immediately:

```bash
# Option 1: Automated (Recommended)
./FIX_DATABASE_ERRORS.sh

# Option 2: Manual
php artisan migrate --path=database/migrations/2026_02_18_173000_add_net_amount_to_transactions.php
```

**Test:** Send ₦250 to 6644694207 and verify transaction is created.

---

## 🌐 Deploy Landing Page (When Ready)

```bash
# Navigate to landing page folder
cd LandingPage

# Install dependencies (first time only)
npm install

# Build for production
npm run build

# Upload to server
scp -r build/* user@server:/path/to/website/
```

**Test:** Visit landing page and check all links work.

---

## 📋 Quick Checklist

### Database Fixes (Do Now)
- [ ] Run `./FIX_DATABASE_ERRORS.sh`
- [ ] Test webhook with ₦250 payment
- [ ] Verify transaction created
- [ ] Check logs: `tail -f storage/logs/laravel.log`

### Landing Page (Do Later)
- [ ] `cd LandingPage && npm install`
- [ ] `npm run build`
- [ ] Upload `build/` folder to server
- [ ] Test all pages and links
- [ ] Test on mobile devices

---

## 🆘 If Something Goes Wrong

### Database Migration Failed
```bash
# Check what went wrong
php artisan migrate:status

# Rollback if needed
php artisan migrate:rollback --step=1
```

### Landing Page Build Failed
```bash
# Clear node_modules and try again
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

## 📞 Need Help?

- Email: support@pointwave.ng
- Phone: 02014542876

---

**Priority:** 🔴 Fix database errors NOW (webhooks failing)  
**Status:** Ready to deploy
