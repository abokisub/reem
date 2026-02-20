# Webhook Management System - Implementation Plan

## Executive Summary

This document outlines the complete implementation plan for a structured webhook management system with role-based visibility, separated from the transaction state machine.

---

## Database Schema

### webhook_events Table

**Purpose:** Unified table for all incoming and outgoing webhook events

```sql
CREATE TABLE webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(255) UNIQUE NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    direction ENUM('incoming', 'outgoing') NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    endpoint_url VARCHAR(500) NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'delivered', 'failed', 'duplicate') NOT NULL DEFAULT 'pending',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at TIMESTAMP NULL,
    next_retry_at TIMESTAMP NULL,
    http_status INT NULL,
    response_body TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_company_id (company_id),
    INDEX idx_direction (direction),
    INDEX idx_status (status),
    INDEX idx_next_retry (next_retry_at),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

---

## Implementation Files

### 1. Migration File
### 2. WebhookEvent Model
### 3. Incoming Webhook Service
### 4. Outgoing Webhook Service
### 5. Webhook Retry Service
### 6. Admin Controller
### 7. Company Controller
### 8. Artisan Commands

---

## Key Principles

1. **Separation from Transaction State Machine**
   - Webhooks are audit/notification only
   - They do NOT determine transaction status
   - Transaction status comes from transactions table only

2. **Role-Based Visibility**
   - Admin: See everything (incoming + outgoing, raw payloads)
   - Company: See only their outgoing webhooks (sanitized view)

3. **Idempotency**
   - Incoming webhooks use unique provider_reference
   - Duplicate detection via event_id
   - Safe to replay webhooks

4. **Retry Logic**
   - Exponential backoff for outgoing webhooks
   - Max 5 attempts
   - Automatic retry via cron job

---

## Next Steps

1. Create migration file
2. Create WebhookEvent model
3. Implement services
4. Create controllers
5. Add routes
6. Create artisan commands
7. Test system

