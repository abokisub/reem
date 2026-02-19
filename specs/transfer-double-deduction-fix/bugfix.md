# Bugfix Requirements Document

## Introduction

This document addresses a critical double deduction bug in the transfer flow that causes legitimate transfer requests to fail with "Insufficient balance to cover amount and fees" errors. The bug occurs because the balance is checked and deducted twice during a single transfer operation - once in `TransferPurchase.php` and again in `TransferService.php`. This results in the second check failing even when the user has sufficient funds, as the balance was already deducted by the first operation.

The fix will ensure that balance validation and deduction occurs exactly once per transfer, allowing transfers to succeed when users have sufficient funds.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a user initiates a transfer with sufficient balance THEN the system deducts the balance in TransferPurchase.php and subsequently fails with "Insufficient balance to cover amount and fees" error when TransferService.php attempts to check and deduct the balance again

1.2 WHEN TransferPurchase.php successfully deducts balance and calls TransferRouter → BankingService → TransferService THEN TransferService.php performs a redundant balance check that fails because the balance was already deducted

1.3 WHEN the second balance check fails in TransferService.php THEN the system logs "Failed to Initiate Transfer (Ledger Error)" and "BankingService Transfer Error" and triggers a refund process

### Expected Behavior (Correct)

2.1 WHEN a user initiates a transfer with sufficient balance THEN the system SHALL check and deduct the balance exactly once and proceed with the transfer without redundant balance validation

2.2 WHEN TransferPurchase.php successfully deducts balance and calls TransferRouter → BankingService → TransferService THEN TransferService.php SHALL NOT perform an additional balance check or deduction

2.3 WHEN the transfer flow proceeds through TransferService.php THEN the system SHALL successfully initiate the transfer without "Insufficient balance" errors for users with adequate funds

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a user attempts a transfer with genuinely insufficient balance (before any deduction) THEN the system SHALL CONTINUE TO reject the transfer with an appropriate error message

3.2 WHEN TransferPurchase.php successfully deducts balance and creates a transaction record THEN the system SHALL CONTINUE TO maintain accurate transaction records with correct status values

3.3 WHEN a transfer fails for legitimate reasons (network errors, invalid beneficiary, etc.) THEN the system SHALL CONTINUE TO handle refunds appropriately

3.4 WHEN the transfer completes successfully THEN the system SHALL CONTINUE TO update transaction status and process settlements correctly
