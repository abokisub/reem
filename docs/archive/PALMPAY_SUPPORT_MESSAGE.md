# Message to PalmPay Support

---

## Subject: RC Number Verification Failed - Aggregator Model Setup Required

---

Dear PalmPay Support Team,

I hope this message finds you well.

I am writing regarding an issue we're experiencing with virtual account creation using our company's RC number in aggregator mode.

### Company Details
- **Merchant Name**: PointWave Business
- **Merchant ID**: 126020209274801
- **App ID**: L260202154361881198161
- **Company RC Number**: RC-9058987
- **Business Type**: Payment Aggregator/Fintech Platform

### Issue Description

We are attempting to create virtual accounts for our end-users using the **Aggregator Model** (Master KYC), where all virtual accounts are created under our company's RC number instead of requiring individual BVN/NIN for each end-user.

However, when we make API calls to create virtual accounts, we receive the following error:

**Error Code**: AC100007  
**Error Message**: "LicenseNumber verification failed"

### API Request Details

**Endpoint**: `POST /api/v2/virtual/account/label/create`

**Request Payload**:
```json
{
  "virtualAccountName": "PointWave Business-Jamil Abubakar Bashir",
  "identityType": "company",
  "licenseNumber": "RC-9058987",
  "customerName": "Jamil Abubakar Bashir",
  "email": "habukhan001@gmail.com",
  "phoneNumber": "08078889419",
  "bankCode": "100033",
  "accountReference": "BONITA-1771363361_100033",
  "requestTime": 1771363361769,
  "version": "V2.0",
  "nonceStr": "4CXtcG0Onewb6OFzB05xCYDst27CVOy0"
}
```

**Response**:
```json
{
  "data": null,
  "respCode": "AC100007",
  "respMsg": "LicenseNumber verification failed",
  "success": false
}
```

### What We Need

We need assistance with the following:

1. **RC Number Verification**: Is our company RC number (RC-9058987) registered and verified in your system for aggregator mode?

2. **Aggregator Model Activation**: Do we need to activate or whitelist our RC number for creating virtual accounts on behalf of our end-users?

3. **KYC Documentation**: Do you need any additional KYC documents from our company to enable aggregator mode?

4. **Alternative Solution**: If aggregator mode is not available, what is the recommended approach for creating virtual accounts for end-users who may not have BVN/NIN?

### Business Context

We are a payment aggregator platform that provides virtual account services to businesses and their end-users. Our clients integrate our API to generate virtual accounts for their customers. 

In many cases, end-users do not have BVN/NIN readily available, which is why we need the aggregator model where our company's RC number covers all virtual accounts created through our platform.

### Urgency

We are preparing for production launch and need to resolve this issue urgently. Our clients are waiting to integrate our virtual account service.

### Contact Information

- **Technical Contact**: [Your Name]
- **Email**: [Your Email]
- **Phone**: [Your Phone]
- **Company**: PointWave
- **Website**: [Your Website]

Please advise on the next steps to resolve this issue. We are available for a call or meeting if needed to expedite the resolution.

Thank you for your prompt attention to this matter.

Best regards,  
[Your Name]  
[Your Title]  
PointWave

---

## Alternative Shorter Version (WhatsApp/Quick Message)

---

Hello PalmPay Support,

We're experiencing an issue with virtual account creation using aggregator mode.

**Issue**: Error AC100007 - "LicenseNumber verification failed"

**Details**:
- Merchant ID: 126020209274801
- Company RC: RC-9058987
- We're trying to create virtual accounts using our company RC (aggregator model) instead of individual customer BVN

**Question**: Is our RC number registered for aggregator mode? Do we need to submit additional documents or activate this feature?

We're launching to production soon and need this resolved urgently.

Thanks!

---

## What to Expect from PalmPay

They may ask for:

1. **Company KYC Documents**:
   - Certificate of Incorporation (CAC)
   - Memorandum and Articles of Association
   - Board Resolution
   - Directors' IDs
   - Proof of Business Address

2. **Aggregator Agreement**:
   - Special merchant agreement for aggregator services
   - Higher transaction limits
   - Compliance documentation

3. **Compliance Requirements**:
   - AML/CFT policies
   - Customer onboarding procedures
   - Risk management framework

4. **Technical Integration**:
   - Webhook configuration
   - IP whitelisting
   - API rate limits adjustment

---

## Temporary Workaround

While waiting for PalmPay's response, you can:

1. **Collect Customer BVN** (Current Working Solution):
   - Request BVN from end-users during onboarding
   - Use individual mode (identityType: "personal")
   - This is currently working perfectly

2. **Optional BVN Field**:
   - Make BVN optional in your API
   - If provided, use individual mode
   - If not provided, queue for later processing once aggregator mode is enabled

3. **Hybrid Approach**:
   - Use individual mode for users with BVN
   - Use aggregator mode for users without BVN (once enabled)

---

## Next Steps

1. ✅ Copy the message above
2. ✅ Fill in your contact details
3. ✅ Send to PalmPay support email/portal
4. ✅ Follow up after 24-48 hours if no response
5. ✅ Meanwhile, continue using individual mode (BVN required)

