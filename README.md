# PointWave Payment Gateway

A complete payment gateway platform built with Laravel and React.

---

## 🚀 Quick Start

### Installation
```bash
# Install dependencies
composer install
cd frontend && npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build frontend
npm run build
```

### Development
```bash
# Start Laravel
php artisan serve

# Start React (in another terminal)
cd frontend && npm start
```

---

## 📚 Documentation

All documentation is in the `docs/` folder:

- **[Complete System Overview](docs/archive/COMPLETE_SYSTEM_READY.md)**
- **[Charges Guide](docs/archive/CHARGES_EXPLAINED_SIMPLE.md)**
- **[Admin Monitoring](docs/archive/ADMIN_API_MONITORING_COMPLETE.md)**
- **[Developer Reference](docs/archive/DEVELOPER_QUICK_REFERENCE.md)**

See [docs/README.md](docs/README.md) for full documentation index.

---

## 🧪 Testing

```bash
# Test charges system
php docs/test-scripts/test_complete_charges_system.php

# Test API logging
php docs/test-scripts/test_api_request_logs.php

# Test SPA routing
php docs/test-scripts/test_spa_routing.php
```

---

## 🔑 Admin Access

- URL: `https://app.pointwave.ng/secure/login`
- Email: admin@pointwave.com
- Password: @Habukhan2025

---

## 📊 Key Features

- ✅ PalmPay Virtual Accounts
- ✅ KYC Verification (BVN, NIN)
- ✅ Transfer Operations
- ✅ Automated Charges (0.5% capped at ₦500)
- ✅ Settlement System (24h delay)
- ✅ Admin API Monitoring
- ✅ Webhook Management
- ✅ Company Dashboards

---

## 🛠️ Tech Stack

- **Backend**: Laravel 10
- **Frontend**: React 18
- **Database**: MySQL
- **Payment**: PalmPay API
- **Server**: Nginx + PHP 8.2

---

## 📞 Support

- Documentation: `docs/`
- Test Scripts: `docs/test-scripts/`
- Logs: `storage/logs/laravel.log`

---

**Version**: 1.0.0  
**Last Updated**: February 18, 2026  
**Status**: ✅ Production Ready
