#!/bin/bash

echo "=========================================="
echo "Fix Migration - Install Doctrine DBAL"
echo "=========================================="
echo ""
echo "The migration needs doctrine/dbal package to change column types"
echo ""

# Install doctrine/dbal
echo "📦 Installing doctrine/dbal..."
composer require doctrine/dbal

if [ $? -ne 0 ]; then
    echo "❌ Failed to install doctrine/dbal"
    exit 1
fi

echo ""
echo "✅ Doctrine DBAL installed!"
echo ""
echo "🔧 Running migration..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ Migration completed successfully!"
echo "=========================================="
echo ""
echo "📝 Next steps:"
echo "   1. Build frontend: cd frontend && npm run build"
echo "   2. Test: php test_settlement_system.php"
echo ""
