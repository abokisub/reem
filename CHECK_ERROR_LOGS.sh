#!/bin/bash

echo "=========================================="
echo "Checking Laravel Error Logs"
echo "=========================================="
echo ""

echo "Last 50 lines of Laravel log:"
echo ""
tail -50 storage/logs/laravel.log

echo ""
echo "=========================================="
echo "Checking for recent 500 errors:"
echo "=========================================="
echo ""
grep -i "error\|exception\|fatal" storage/logs/laravel.log | tail -20
