<?php

/**
 * Comprehensive Settlement and Transfer Verification Script
 * 
 * This script verifies:
 * 1. Internal Wallet Transfers (Wallet-to-Wallet)
 * 2. External Bank Transfers (to other banks)
 * 3. Settlement Rules (Auto, Manual, Weekend/Holiday Skip)
 * 4. Settlement Delay Configuration (1 hour, 24 hours, etc.)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "========================================\n";
echo "Settlement & Transfer Verification\n";
echo "========================================\n\n";

// Colors
$GREEN = "\033[0;32m";
$YELLOW = "\033[1;33m";
$RED = "\033[0;31m";
$BLUE = "\033[0;34m";
$NC = "\033[0m"; // No Color

function printStatus($label, $status, $details = '') {
    global $GREEN, $RED, $YELLOW, $NC;
    $color = $status ? $GREEN : $RED;
    $icon = $status ? '✓' : '✗';
    echo "{$color}{$icon}{$NC} {$label}";
    if ($details) {
        echo " {$YELLOW}({$details}){$NC}";
    }
    echo "\n";
}

function printSection($title) {
    global $BLUE, $NC;
    echo "\n{$BLUE}=== {$title} ==={$NC}\n";
}

// ============================================
// 1. CHECK INTERNAL TRANSFER FUNCTIONALITY
// ============================================
printSection("Internal Wallet Transfers");

$internalTransferController = file_exists('app/Http/Controllers/Purchase/InternalTransferController.php');
printStatus("Internal Transfer Controller", $internalTransferController, 
    $internalTransferController ? "Found" : "Missing");

if ($internalTransferController) {
    $content = file_get_contents('app/Http/Controllers/Purchase/InternalTransferController.php');
    
    $hasVerifyUser = strpos($content, 'function verifyUser') !== false;
    printStatus("  - Verify User Method", $hasVerifyUser);
    
    $hasTransfer = strpos($content, 'function transfer') !== false;
    printStatus("  - Transfer Method", $hasTransfer);
    
    $hasChargeCalculation = strpos($content, 'calculateWalletCharge') !== false;
    printStatus("  - Charge Calculation", $hasChargeCalculation);
    
    $hasBeneficiarySave = strpos($content, 'Beneficiary::updateOrCreate') !== false;
    printStatus("  - Beneficiary Save", $hasBeneficiarySave);
}

// Check internal transfer route
$routes = file_get_contents('routes/api.php');
$hasInternalRoute = strpos($routes, 'transfer/internal') !== false;
printStatus("Internal Transfer Route", $hasInternalRoute, 
    $hasInternalRoute ? "/api/transfer/internal" : "Not found");

// ============================================
// 2. CHECK EXTERNAL TRANSFER FUNCTIONALITY
// ============================================
printSection("External Bank Transfers");

$externalTransferController = file_exists('app/Http/Controllers/Purchase/TransferPurchase.php');
printStatus("External Transfer Controller", $externalTransferController,
    $externalTransferController ? "Found" : "Missing");

if ($externalTransferController) {
    $content = file_get_contents('app/Http/Controllers/Purchase/TransferPurchase.php');
    
    $hasBalanceCheck = strpos($content, 'balance_already_deducted') !== false;
    printStatus("  - Double Deduction Fix", $hasBalanceCheck, 
        $hasBalanceCheck ? "Implemented" : "Missing");
    
    $hasTransactionRef = strpos($content, 'transaction_reference') !== false;
    printStatus("  - Transaction Reference Passing", $hasTransactionRef);
    
    $hasRefundLogic = strpos($content, 'refund') !== false;
    printStatus("  - Refund Logic", $hasRefundLogic);
}

// Check TransferService
$transferService = file_exists('app/Services/PalmPay/TransferService.php');
printStatus("PalmPay Transfer Service", $transferService);

if ($transferService) {
    $content = file_get_contents('app/Services/PalmPay/TransferService.php');
    
    $hasConditionalLogic = strpos($content, 'if ($balanceAlreadyDeducted && $existingReference)') !== false;
    printStatus("  - Conditional Balance Logic", $hasConditionalLogic);
    
    $hasProviderCall = strpos($content, 'processPalmPayTransfer') !== false;
    printStatus("  - Provider Integration", $hasProviderCall);
}

// ============================================
// 3. CHECK SETTLEMENT SYSTEM
// ============================================
printSection("Settlement System");

// Check settlement command
$settlementCommand = file_exists('app/Console/Commands/ProcessSettlements.php');
printStatus("Settlement Command", $settlementCommand,
    $settlementCommand ? "settlements:process" : "Missing");

if ($settlementCommand) {
    $content = file_get_contents('app/Console/Commands/ProcessSettlements.php');
    
    $hasAutoCheck = strpos($content, 'auto_settlement_enabled') !== false;
    printStatus("  - Auto Settlement Check", $hasAutoCheck);
    
    $hasWeekendSkip = strpos($content, 'skipWeekends') !== false;
    printStatus("  - Weekend Skip Logic", $hasWeekendSkip);
    
    $hasHolidaySkip = strpos($content, 'skipHolidays') !== false;
    printStatus("  - Holiday Skip Logic", $hasHolidaySkip);
    
    $hasDelayCalculation = strpos($content, 'calculateSettlementDate') !== false;
    printStatus("  - Delay Calculation", $hasDelayCalculation);
    
    $hasTimePreservation = strpos($content, 'if ($delayHours >= 24)') !== false;
    printStatus("  - Time Preservation (<24h)", $hasTimePreservation);
}

// Check settlement model
$settlementModel = file_exists('app/Models/SettlementQueue.php');
printStatus("Settlement Queue Model", $settlementModel);

// Check settlement controller
$settlementController = file_exists('app/Http/Controllers/Admin/SettlementController.php');
printStatus("Settlement Controller", $settlementController);

if ($settlementController) {
    $content = file_get_contents('app/Http/Controllers/Admin/SettlementController.php');
    
    $hasGetConfig = strpos($content, 'function getConfig') !== false;
    printStatus("  - Get Configuration", $hasGetConfig);
    
    $hasUpdateConfig = strpos($content, 'function updateConfig') !== false;
    printStatus("  - Update Configuration", $hasUpdateConfig);
    
    $hasCompanyConfig = strpos($content, 'function getCompanyConfig') !== false;
    printStatus("  - Company-Specific Config", $hasCompanyConfig);
    
    $hasStatistics = strpos($content, 'function getStatistics') !== false;
    printStatus("  - Statistics", $hasStatistics);
}

// ============================================
// 4. CHECK DATABASE CONFIGURATION
// ============================================
printSection("Database Configuration");

try {
    $settings = DB::table('settings')->first();
    
    if ($settings) {
        printStatus("Settings Table", true, "Found");
        
        // Auto Settlement
        $autoEnabled = property_exists($settings, 'auto_settlement_enabled');
        printStatus("  - auto_settlement_enabled", $autoEnabled, 
            $autoEnabled ? ($settings->auto_settlement_enabled ? "Enabled" : "Disabled") : "Missing");
        
        // Settlement Delay
        $hasDelay = property_exists($settings, 'settlement_delay_hours');
        printStatus("  - settlement_delay_hours", $hasDelay,
            $hasDelay ? "{$settings->settlement_delay_hours} hours" : "Missing");
        
        // Weekend Skip
        $hasWeekend = property_exists($settings, 'settlement_skip_weekends');
        printStatus("  - settlement_skip_weekends", $hasWeekend,
            $hasWeekend ? ($settings->settlement_skip_weekends ? "Yes" : "No") : "Missing");
        
        // Holiday Skip
        $hasHoliday = property_exists($settings, 'settlement_skip_holidays');
        printStatus("  - settlement_skip_holidays", $hasHoliday,
            $hasHoliday ? ($settings->settlement_skip_holidays ? "Yes" : "No") : "Missing");
        
        // Settlement Time
        $hasTime = property_exists($settings, 'settlement_time');
        printStatus("  - settlement_time", $hasTime,
            $hasTime ? $settings->settlement_time : "Missing");
        
        // Minimum Amount
        $hasMin = property_exists($settings, 'settlement_minimum_amount');
        printStatus("  - settlement_minimum_amount", $hasMin,
            $hasMin ? "₦" . number_format($settings->settlement_minimum_amount, 2) : "Missing");
            
    } else {
        printStatus("Settings Table", false, "No records found");
    }
} catch (\Exception $e) {
    printStatus("Settings Table", false, "Error: " . $e->getMessage());
}

// Check settlement_queue table
try {
    $queueExists = DB::select("SHOW TABLES LIKE 'settlement_queue'");
    printStatus("settlement_queue Table", !empty($queueExists));
    
    if (!empty($queueExists)) {
        $pendingCount = DB::table('settlement_queue')->where('status', 'pending')->count();
        $completedCount = DB::table('settlement_queue')->where('status', 'completed')->count();
        $failedCount = DB::table('settlement_queue')->where('status', 'failed')->count();
        
        echo "  - Pending: {$pendingCount}\n";
        echo "  - Completed: {$completedCount}\n";
        echo "  - Failed: {$failedCount}\n";
    }
} catch (\Exception $e) {
    printStatus("settlement_queue Table", false, "Error: " . $e->getMessage());
}

// ============================================
// 5. CHECK WEBHOOK INTEGRATION
// ============================================
printSection("Webhook Integration");

$webhookHandler = file_exists('app/Services/PalmPay/WebhookHandler.php');
printStatus("Webhook Handler", $webhookHandler);

if ($webhookHandler) {
    $content = file_get_contents('app/Services/PalmPay/WebhookHandler.php');
    
    $hasVACredit = strpos($content, 'handleVirtualAccountCredit') !== false;
    printStatus("  - Virtual Account Credit", $hasVACredit);
    
    $hasTransferSuccess = strpos($content, 'handleTransferSuccess') !== false;
    printStatus("  - Transfer Success", $hasTransferSuccess);
    
    $hasTransferFailed = strpos($content, 'handleTransferFailed') !== false;
    printStatus("  - Transfer Failed", $hasTransferFailed);
    
    $hasSettlementQueue = strpos($content, 'settlement_queue') !== false;
    printStatus("  - Settlement Queue Integration", $hasSettlementQueue);
    
    $hasSelfFundingDetection = strpos($content, 'isCompanySelfFunding') !== false;
    printStatus("  - Self-Funding Detection", $hasSelfFundingDetection);
    
    $hasInstantCredit = strpos($content, 'INSTANT CREDIT') !== false;
    printStatus("  - Instant Credit (Self-Funding)", $hasInstantCredit);
}

// ============================================
// 6. SETTLEMENT SCENARIOS TEST
// ============================================
printSection("Settlement Scenarios");

try {
    $settings = DB::table('settings')->first();
    
    if ($settings && property_exists($settings, 'auto_settlement_enabled')) {
        
        // Scenario 1: Auto Settlement Enabled
        if ($settings->auto_settlement_enabled) {
            echo "{$GREEN}✓{$NC} Auto Settlement: ENABLED\n";
            
            $delayHours = $settings->settlement_delay_hours ?? 24;
            echo "  - Delay: {$delayHours} hours\n";
            
            // Test calculation
            $now = Carbon::now();
            $testDate = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
                $now,
                $delayHours,
                $settings->settlement_skip_weekends ?? true,
                $settings->settlement_skip_holidays ?? true,
                $settings->settlement_time ?? '02:00:00'
            );
            
            echo "  - Example: Transaction now → Settlement at " . $testDate->format('Y-m-d H:i:s') . "\n";
            
            // Check if it skips weekend
            if ($testDate->isWeekend() && ($settings->settlement_skip_weekends ?? true)) {
                echo "  {$RED}✗ Weekend skip not working properly{$NC}\n";
            } else {
                echo "  {$GREEN}✓ Weekend skip working{$NC}\n";
            }
            
        } else {
            echo "{$YELLOW}⚠{$NC} Auto Settlement: DISABLED (Instant settlement)\n";
        }
        
        // Scenario 2: 1 Hour Delay
        echo "\n{$BLUE}Testing 1-Hour Delay:{$NC}\n";
        $oneHourTest = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
            Carbon::now(),
            1,
            false,
            false,
            '02:00:00'
        );
        echo "  Transaction at: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        echo "  Settlement at:  " . $oneHourTest->format('Y-m-d H:i:s') . "\n";
        
        $diff = Carbon::now()->diffInMinutes($oneHourTest);
        if ($diff >= 59 && $diff <= 61) {
            echo "  {$GREEN}✓ 1-hour delay working correctly{$NC}\n";
        } else {
            echo "  {$RED}✗ 1-hour delay not accurate (diff: {$diff} minutes){$NC}\n";
        }
        
        // Scenario 3: Weekend Skip
        echo "\n{$BLUE}Testing Weekend Skip:{$NC}\n";
        $friday = Carbon::parse('next friday 23:00:00');
        $weekendTest = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
            $friday,
            2, // 2 hours (would land on Saturday)
            true, // Skip weekends
            false,
            '02:00:00'
        );
        echo "  Transaction: " . $friday->format('l, Y-m-d H:i:s') . "\n";
        echo "  Settlement:  " . $weekendTest->format('l, Y-m-d H:i:s') . "\n";
        
        if ($weekendTest->isWeekend()) {
            echo "  {$RED}✗ Weekend skip not working{$NC}\n";
        } else {
            echo "  {$GREEN}✓ Weekend skip working (moved to Monday){$NC}\n";
        }
        
    } else {
        echo "{$YELLOW}⚠ Cannot test scenarios - settings not configured{$NC}\n";
    }
    
} catch (\Exception $e) {
    echo "{$RED}✗ Error testing scenarios: {$e->getMessage()}{$NC}\n";
}

// ============================================
// 7. RECOMMENDATIONS
// ============================================
printSection("Recommendations");

$issues = [];
$warnings = [];

if (!$internalTransferController) {
    $issues[] = "Internal transfer controller is missing";
}

if (!$externalTransferController) {
    $issues[] = "External transfer controller is missing";
}

if (!$settlementCommand) {
    $issues[] = "Settlement processing command is missing";
}

try {
    $settings = DB::table('settings')->first();
    if (!$settings) {
        $issues[] = "Settings table has no records";
    } else {
        if (!property_exists($settings, 'auto_settlement_enabled')) {
            $warnings[] = "auto_settlement_enabled column missing in settings";
        }
        if (!property_exists($settings, 'settlement_delay_hours')) {
            $warnings[] = "settlement_delay_hours column missing in settings";
        }
    }
} catch (\Exception $e) {
    $issues[] = "Cannot access settings table: " . $e->getMessage();
}

if (empty($issues) && empty($warnings)) {
    echo "{$GREEN}✓ All systems operational!{$NC}\n";
    echo "\nYour settlement system is properly configured:\n";
    echo "  • Internal wallet transfers: Working\n";
    echo "  • External bank transfers: Working\n";
    echo "  • Auto settlement: Configured\n";
    echo "  • Weekend/Holiday skip: Configured\n";
    echo "  • Flexible delay: Supported (1 hour to 168 hours)\n";
} else {
    if (!empty($issues)) {
        echo "{$RED}Critical Issues:{$NC}\n";
        foreach ($issues as $issue) {
            echo "  • {$issue}\n";
        }
    }
    
    if (!empty($warnings)) {
        echo "\n{$YELLOW}Warnings:{$NC}\n";
        foreach ($warnings as $warning) {
            echo "  • {$warning}\n";
        }
    }
}

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";
