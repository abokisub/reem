# Company Detail Page - Professional Upgrade Specification

## Overview
Complete redesign of the admin company detail page to be professional, comprehensive, and user-friendly.

## Requirements

### 1. Document Management
- [ ] Display all uploaded documents (CAC, Utility Bill, ID Card, etc.)
- [ ] Show document thumbnails/previews
- [ ] Allow admin to view full documents
- [ ] Allow admin to replace/reupload documents
- [ ] Show upload date and status for each document

### 2. Bank Information
- [ ] Remove "N/A" for bank codes
- [ ] Use bank dropdown (fetch from `/api/gateway/banks`)
- [ ] Verify account number when admin edits
- [ ] Show verified account name
- [ ] Professional bank selection UI

### 3. Professional Layout
- [ ] Clean, organized sections
- [ ] Proper spacing and typography
- [ ] Status badges (Verified, Active, Pending, etc.)
- [ ] Responsive design
- [ ] Loading states
- [ ] Error handling

### 4. Onboarding Checklist
- [ ] Show completion status for each KYC section
- [ ] Visual progress indicator
- [ ] Before activation: Show what's missing
- [ ] After activation: Show completed items

### 5. Features
- [ ] Edit company information
- [ ] Upload/replace documents
- [ ] Activate/deactivate company
- [ ] View transaction history
- [ ] View virtual accounts
- [ ] View wallet balance
- [ ] Regenerate API credentials

## Implementation Plan

### Phase 1: Backend API Enhancements
1. Add document upload/retrieval endpoints
2. Add bank verification endpoint
3. Enhance company detail endpoint to include documents

### Phase 2: Frontend Components
1. Document viewer component
2. Bank selector with verification
3. Onboarding checklist component
4. Professional layout with sections

### Phase 3: Integration
1. Connect all components
2. Add error handling
3. Add loading states
4. Test all features

## File Structure
```
frontend/src/pages/admin/companies/
├── detail.js (main file - will be upgraded)
├── components/
│   ├── DocumentSection.js
│   ├── BankSelector.js
│   ├── OnboardingChecklist.js
│   └── CompanyHeader.js
```

## API Endpoints Needed

### Existing:
- GET `/api/admin/companies/{id}` - Get company details
- PUT `/api/admin/companies/{id}` - Update company
- POST `/api/admin/companies/{id}/toggle-status` - Activate/Deactivate

### New/Enhanced:
- GET `/api/admin/companies/{id}/documents` - Get all documents
- POST `/api/admin/companies/{id}/documents` - Upload document
- DELETE `/api/admin/companies/{id}/documents/{doc_id}` - Delete document
- POST `/api/gateway/banks/verify` - Verify bank account
- GET `/api/gateway/banks` - Get bank list

## Next Steps
1. Create backend document management endpoints
2. Create frontend components
3. Integrate everything
4. Test thoroughly
