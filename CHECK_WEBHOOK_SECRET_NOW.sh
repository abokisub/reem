#!/bin/bash

echo "================================================================================"
echo "CHECKING WEBHOOK SECRET FOR KOBOPOINT"
echo "================================================================================"
echo ""

php compare_webhook_secrets.php

echo ""
echo "================================================================================"
echo "NEXT: Compare this with Kobopoint's .env file"
echo "================================================================================"
echo ""
echo "On Kobopoint server, run:"
echo "  grep POINTWAVE_WEBHOOK_SECRET .env"
echo ""
echo "They MUST match exactly!"
echo ""
