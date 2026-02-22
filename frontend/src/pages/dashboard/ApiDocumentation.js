import { useState } from 'react';
import { Container, Box, Typography, Tabs, Tab, Card, CardContent, Button, Chip, Alert } from '@mui/material';
import { styled } from '@mui/material/styles';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import Page from '../../components/Page';
import { PATH_DASHBOARD } from '../../routes/paths';
import HeaderBreadcrumbs from '../../components/HeaderBreadcrumbs';

// Styled Components
const CodeBlock = styled(Box)(({ theme }) => ({
  backgroundColor: '#1e1e1e',
  color: '#d4d4d4',
  padding: theme.spacing(2),
  borderRadius: theme.spacing(1),
  overflow: 'auto',
  fontFamily: '"Fira Code", "Courier New", monospace',
  fontSize: '0.875rem',
  lineHeight: 1.6,
  position: 'relative',
  '& pre': {
    margin: 0,
  },
}));

const MethodChip = styled(Chip)(({ method }) => {
  const colors = {
    GET: { bg: '#61affe', color: '#fff' },
    POST: { bg: '#49cc90', color: '#fff' },
    PUT: { bg: '#fca130', color: '#fff' },
    DELETE: { bg: '#f93e3e', color: '#fff' },
  };
  const color = colors[method] || colors.GET;
  return {
    backgroundColor: color.bg,
    color: color.color,
    fontWeight: 600,
    marginRight: 8,
  };
});

const EndpointBox = styled(Box)(({ theme }) => ({
  backgroundColor: theme.palette.grey[100],
  padding: theme.spacing(2),
  borderRadius: theme.spacing(1),
  borderLeft: `4px solid ${theme.palette.primary.main}`,
  marginTop: theme.spacing(2),
  marginBottom: theme.spacing(2),
}));

export default function ApiDocumentation() {
  const [activeTab, setActiveTab] = useState(0);
  const [copiedCode, setCopiedCode] = useState(null);

  const handleCopyCode = (code, id) => {
    navigator.clipboard.writeText(code);
    setCopiedCode(id);
    setTimeout(() => setCopiedCode(null), 2000);
  };

  const renderCodeBlock = (code, language = 'bash', id) => (
    <Box sx={{ position: 'relative' }}>
      <Button
        size="small"
        startIcon={copiedCode === id ? <CheckCircleIcon /> : <ContentCopyIcon />}
        onClick={() => handleCopyCode(code, id)}
        sx={{
          position: 'absolute',
          top: 8,
          right: 8,
          zIndex: 1,
          color: 'white',
          '&:hover': { backgroundColor: 'rgba(255,255,255,0.1)' },
        }}
      >
        {copiedCode === id ? 'Copied!' : 'Copy'}
      </Button>
      <CodeBlock>
        <pre>{code}</pre>
      </CodeBlock>
    </Box>
  );

  // Create Customer Documentation
  const renderCreateCustomerDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        👤 Create Customer
      </Typography>
      <Typography variant="body1" paragraph>
        Create a new customer in your system. This is the first step before creating virtual accounts.
      </Typography>

      <Alert severity="info" sx={{ mb: 3 }}>
        <strong>💡 Simplified:</strong> Only basic information is required. You can update additional details later.
      </Alert>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="POST" method="POST" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/customers
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Create a new customer
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/v1/customers" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone_number": "08012345678"
  }'`, 'bash', 'curl-create-customer')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Customer created successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone_number": "08012345678",
    "kyc_status": "unverified",
    "created_at": "2026-02-21T10:00:00Z"
  }
}`, 'json', 'response-create-customer')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Email already exists"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> A customer with this email already exists in your account.
          </Typography>
          <Typography variant="body2">
            <strong>Solution:</strong> Use a different email or retrieve the existing customer using GET /api/v1/customers
          </Typography>
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Invalid phone number format"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> Phone number format is incorrect.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Use Nigerian format (11 digits starting with 0):
          </Typography>
          {renderCodeBlock(`// ❌ WRONG
"phone_number": "+2348012345678"  // Has country code
"phone_number": "8012345678"      // Missing leading 0
"phone_number": "080-1234-5678"   // Has dashes

// ✅ CORRECT
"phone_number": "08012345678"     // 11 digits, starts with 0`, 'javascript', 'customer-phone-troubleshoot')}
        </CardContent>
      </Card>
    </Box>
  );

  // Update Customer Documentation
  const renderUpdateCustomerDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        ✏️ Update Customer
      </Typography>
      <Typography variant="body1" paragraph>
        Update customer information such as name, phone, or address.
      </Typography>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="PUT" method="PUT" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/customers/{'{customer_id}'}
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Update customer details
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X PUT "https://app.pointwave.ng/api/v1/customers/cust_abc123xyz456" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -d '{
    "first_name": "John",
    "last_name": "Smith",
    "phone_number": "08087654321",
    "address": "123 New Street, Lagos"
  }'`, 'bash', 'curl-update-customer')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Customer updated successfully",
  "data": {
    "customer_id": "cust_abc123xyz456",
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "phone_number": "08087654321",
    "address": "123 New Street, Lagos",
    "updated_at": "2026-02-21T10:30:00Z"
  }
}`, 'json', 'response-update-customer')}

      <Alert severity="info" sx={{ mt: 3 }}>
        <strong>💡 Note:</strong> You can update any field except <code>email</code> and <code>customer_id</code>. Only send the fields you want to update.
      </Alert>
    </Box>
  );

  // Update Virtual Account Documentation
  const renderUpdateVADoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        🔄 Update Virtual Account
      </Typography>
      <Typography variant="body1" paragraph>
        Update virtual account status (activate or deactivate).
      </Typography>

      <Alert severity="warning" sx={{ mb: 3 }}>
        <strong>⚠️ Important:</strong> Only STATIC virtual accounts can be updated. Dynamic accounts cannot be modified.
      </Alert>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="PUT" method="PUT" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/virtual-accounts/{'{account_id}'}
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Update virtual account status
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X PUT "https://app.pointwave.ng/api/v1/virtual-accounts/6644694207" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -d '{
    "status": "inactive",
    "reason": "Customer requested account closure"
  }'`, 'bash', 'curl-update-va')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Virtual account updated successfully",
  "data": {
    "account_number": "6644694207",
    "account_name": "John Doe",
    "status": "inactive",
    "updated_at": "2026-02-21T10:45:00Z"
  }
}`, 'json', 'response-update-va')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Valid Status Values
      </Typography>

      <Card>
        <CardContent>
          <Box sx={{ display: 'grid', gap: 2 }}>
            {[
              { status: 'active', desc: 'Account can receive payments', color: 'success' },
              { status: 'inactive', desc: 'Account is deactivated, cannot receive payments', color: 'error' },
              { status: 'suspended', desc: 'Account is temporarily suspended', color: 'warning' },
            ].map((item) => (
              <Box key={item.status} sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                <Chip label={item.status} color={item.color} size="small" sx={{ minWidth: 100 }} />
                <Typography variant="body2" color="text.secondary">
                  {item.desc}
                </Typography>
              </Box>
            ))}
          </Box>
        </CardContent>
      </Card>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Invalid status value" or "Data truncated for column 'status'"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> You're using an invalid status value.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Use only these exact values:
          </Typography>
          {renderCodeBlock(`// ❌ WRONG
"status": "deactivated"  // Not a valid enum value
"status": "closed"       // Not a valid enum value
"status": "disabled"     // Not a valid enum value

// ✅ CORRECT
"status": "active"       // Valid
"status": "inactive"     // Valid (use this to deactivate)
"status": "suspended"    // Valid`, 'javascript', 'va-status-troubleshoot')}
          <Typography variant="body2" sx={{ mt: 2 }}>
            <strong>Note:</strong> To deactivate an account, use <code>"status": "inactive"</code> (not "deactivated").
          </Typography>
        </CardContent>
      </Card>
    </Box>
  );

  // Delete Virtual Account Documentation
  const renderDeleteVADoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        🗑️ Delete Virtual Account
      </Typography>
      <Typography variant="body1" paragraph>
        Permanently delete a virtual account. This action cannot be undone.
      </Typography>

      <Alert severity="error" sx={{ mb: 3 }}>
        <strong>⚠️ Warning:</strong> Only STATIC virtual accounts can be deleted. Dynamic accounts cannot be deleted. The account will be set to "inactive" status.
      </Alert>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="DELETE" method="DELETE" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/virtual-accounts/{'{account_id}'}
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Delete a virtual account
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X DELETE "https://app.pointwave.ng/api/v1/virtual-accounts/6644694207" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Idempotency-Key: $(uuidgen)"`, 'bash', 'curl-delete-va')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Virtual account deleted successfully",
  "data": {
    "account_number": "6644694207",
    "account_name": "John Doe",
    "status": "inactive",
    "deleted_at": "2026-02-21T11:00:00Z"
  }
}`, 'json', 'response-delete-va')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Dynamic virtual accounts cannot be deleted"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> You're trying to delete a dynamic virtual account.
          </Typography>
          <Typography variant="body2">
            <strong>Solution:</strong> Only static accounts can be deleted. Dynamic accounts are managed by the system and cannot be removed.
          </Typography>
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Virtual account not found" or "Invalid account ID"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> The account number or ID doesn't exist.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> You can use either the account number OR the UUID:
          </Typography>
          {renderCodeBlock(`// Both formats work:
DELETE /api/v1/virtual-accounts/6644694207           // Account number
DELETE /api/v1/virtual-accounts/va_abc123xyz456      // UUID

// Get the correct ID from:
GET /api/v1/virtual-accounts                         // List all accounts`, 'javascript', 'va-delete-troubleshoot')}
        </CardContent>
      </Card>
    </Box>
  );

  // Get Banks Documentation
  const renderBanksDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        🏦 Get Banks List
      </Typography>
      <Typography variant="body1" paragraph>
        Retrieve a list of all supported Nigerian banks for transfers and account verification.
      </Typography>

      <Alert severity="warning" sx={{ mb: 2 }}>
        <strong>⚠️ Important:</strong> Use the <code>/api/gateway/banks</code> endpoint for the correct JSON response format.
      </Alert>

      <Alert severity="info" sx={{ mb: 3 }}>
        <strong>💡 Pro Tip:</strong> Cache the banks list in your application to avoid repeated API calls. The list rarely changes.
      </Alert>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="GET" method="GET" size="small" />
          <Typography variant="body1" component="code">
            /api/gateway/banks
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Get list of all supported Nigerian banks
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      <Tabs value={activeTab} onChange={(e, v) => setActiveTab(v)} sx={{ mb: 2 }}>
        <Tab label="cURL" />
        <Tab label="JavaScript" />
        <Tab label="PHP" />
        <Tab label="Python" />
      </Tabs>

      {activeTab === 0 && renderCodeBlock(`curl -X GET "https://app.pointwave.ng/api/gateway/banks" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID"`, 'bash', 'curl-banks')}

      {activeTab === 1 && renderCodeBlock(`const axios = require('axios');

const getBanks = async () => {
  try {
    const response = await axios.get('https://app.pointwave.ng/api/gateway/banks', {
      headers: {
        'Authorization': 'Bearer YOUR_SECRET_KEY',
        'x-api-key': 'YOUR_API_KEY',
        'x-business-id': 'YOUR_BUSINESS_ID'
      }
    });
    
    console.log('Banks:', response.data.data);
    return response.data.data;
  } catch (error) {
    console.error('Error:', error.response.data);
  }
};

getBanks();`, 'javascript', 'js-banks')}

      {activeTab === 2 && renderCodeBlock(`<?php
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://app.pointwave.ng/api/gateway/banks",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer YOUR_SECRET_KEY",
        "x-api-key: YOUR_API_KEY",
        "x-business-id: YOUR_BUSINESS_ID"
    ],
]);

$response = curl_exec($curl);
$banks = json_decode($response, true);

curl_close($curl);

// Display banks
foreach ($banks['data'] as $bank) {
    echo $bank['name'] . " - " . $bank['code'] . "\\n";
}`, 'php', 'php-banks')}

      {activeTab === 3 && renderCodeBlock(`import requests

url = "https://app.pointwave.ng/api/gateway/banks"
headers = {
    "Authorization": "Bearer YOUR_SECRET_KEY",
    "x-api-key": "YOUR_API_KEY",
    "x-business-id": "YOUR_BUSINESS_ID"
}

response = requests.get(url, headers=headers)
banks = response.json()

# Display banks
for bank in banks['data']:
    print(f"{bank['name']} - {bank['code']}")`, 'python', 'py-banks')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Banks retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Access Bank",
      "code": "044",
      "slug": "access-bank",
      "logo": "https://app.pointwave.ng/banks/access-bank.png",
      "active": true,
      "supports_transfer": true,
      "supports_account_lookup": true
    },
    {
      "id": 2,
      "name": "GTBank",
      "code": "058",
      "slug": "gtbank",
      "logo": "https://app.pointwave.ng/banks/gtbank.png",
      "active": true,
      "supports_transfer": true,
      "supports_account_lookup": true
    },
    {
      "id": 3,
      "name": "Zenith Bank",
      "code": "057",
      "slug": "zenith-bank",
      "logo": "https://app.pointwave.ng/banks/zenith-bank.png",
      "active": true,
      "supports_transfer": true,
      "supports_account_lookup": true
    }
    // ... more banks
  ],
  "meta": {
    "total": 24,
    "active": 24,
    "last_updated": "2026-02-18T10:00:00Z"
  }
}`, 'json', 'response-banks')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Common Nigerian Banks
      </Typography>

      <Card>
        <CardContent>
          <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: 2 }}>
            {[
              { name: 'Access Bank', code: '044' },
              { name: 'GTBank', code: '058' },
              { name: 'Zenith Bank', code: '057' },
              { name: 'First Bank', code: '011' },
              { name: 'UBA', code: '033' },
              { name: 'Fidelity Bank', code: '070' },
              { name: 'Union Bank', code: '032' },
              { name: 'Stanbic IBTC', code: '221' },
              { name: 'Sterling Bank', code: '232' },
              { name: 'Wema Bank', code: '035' },
              { name: 'Kuda Bank', code: '090267' },
              { name: 'Opay', code: '999992' },
              { name: 'PalmPay', code: '999991' },
            ].map((bank) => (
              <Box key={bank.code} sx={{ p: 1, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
                <Typography variant="body2" fontWeight={600}>
                  {bank.name}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  Code: {bank.code}
                </Typography>
              </Box>
            ))}
          </Box>
        </CardContent>
      </Card>

      <Alert severity="success" sx={{ mt: 3 }}>
        <strong>✅ Best Practice:</strong> Cache the banks list in localStorage or your database to reduce API calls. Update it once per day.
      </Alert>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Failed to retrieve banks" or 500 Server Error
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> You're getting HTML response instead of JSON, or a 500 error.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Make sure you're using the correct endpoint:
          </Typography>
          {renderCodeBlock(`// ❌ WRONG - Old endpoint
GET /api/v1/banks

// ✅ CORRECT - Use gateway endpoint
GET /api/gateway/banks`, 'javascript', 'banks-troubleshoot')}
          <Typography variant="body2" sx={{ mt: 2 }}>
            The <code>/api/v1/banks</code> endpoint returns proper JSON response. The old <code>/banks</code> endpoint returns HTML documentation.
          </Typography>
        </CardContent>
      </Card>
    </Box>
  );

  // Virtual Accounts Documentation
  const renderVirtualAccountsDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        💳 Create Virtual Account
      </Typography>
      <Typography variant="body1" paragraph>
        Create a dedicated virtual account for a customer to receive payments.
      </Typography>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="POST" method="POST" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/virtual-accounts
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Create a new virtual account for collections
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/v1/virtual-accounts" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: $(uuidgen)" \\
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone_number": "08012345678",
    "bvn": "22222222222",
    "account_type": "static"
  }'`, 'bash', 'curl-va')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Parameters
      </Typography>

      <Card>
        <CardContent>
          <Box sx={{ '& > div': { py: 1, borderBottom: '1px solid', borderColor: 'divider' } }}>
            <Box sx={{ display: 'grid', gridTemplateColumns: '200px 100px 1fr', gap: 2 }}>
              <Typography variant="body2" fontWeight={600}>
                Parameter
              </Typography>
              <Typography variant="body2" fontWeight={600}>
                Required
              </Typography>
              <Typography variant="body2" fontWeight={600}>
                Description
              </Typography>
            </Box>
            {[
              { name: 'first_name', required: true, desc: 'Customer first name' },
              { name: 'last_name', required: true, desc: 'Customer last name' },
              { name: 'email', required: true, desc: 'Customer email address' },
              { name: 'phone_number', required: true, desc: 'Customer phone (11 digits, starts with 0)' },
              { name: 'bvn', required: true, desc: 'Bank Verification Number (11 digits)' },
              { name: 'account_type', required: false, desc: 'static or dynamic (default: static)' },
            ].map((param) => (
              <Box key={param.name} sx={{ display: 'grid', gridTemplateColumns: '200px 100px 1fr', gap: 2 }}>
                <Typography variant="body2" component="code">
                  {param.name}
                </Typography>
                <Chip
                  label={param.required ? 'Required' : 'Optional'}
                  size="small"
                  color={param.required ? 'error' : 'warning'}
                />
                <Typography variant="body2" color="text.secondary">
                  {param.desc}
                </Typography>
              </Box>
            ))}
          </Box>
        </CardContent>
      </Card>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Virtual account created successfully",
  "data": {
    "id": 123,
    "account_number": "6644694207",
    "account_name": "John Doe",
    "bank_name": "PalmPay",
    "bank_code": "999991",
    "customer": {
      "id": 456,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone_number": "08012345678"
    },
    "account_type": "static",
    "status": "active",
    "created_at": "2026-02-18T10:00:00Z"
  }
}`, 'json', 'response-va')}

      <Alert severity="info" sx={{ mt: 3 }}>
        <strong>💡 Note:</strong> Virtual accounts are created instantly. Customers can start receiving payments immediately.
      </Alert>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Customer not found" or "Invalid customer_id"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> You're trying to create a virtual account for a non-existent customer.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Create the customer first, then use the returned <code>customer_id</code>:
          </Typography>
          {renderCodeBlock(`// Step 1: Create Customer
POST /api/v1/customers
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone_number": "08012345678"
}

// Response: { "data": { "customer_id": "cust_abc123" } }

// Step 2: Create Virtual Account
POST /api/v1/virtual-accounts
{
  "customer_id": "cust_abc123",  // Use the ID from step 1
  "account_name": "John Doe"
}`, 'javascript', 'va-troubleshoot-1')}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "BVN is required" or "Invalid BVN format"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> BVN validation is failing.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Ensure BVN is exactly 11 digits:
          </Typography>
          {renderCodeBlock(`// ❌ WRONG
"bvn": "2222222222"    // 10 digits
"bvn": "222222222222"  // 12 digits
"bvn": "22222-222222"  // Contains dash

// ✅ CORRECT
"bvn": "22222222222"   // Exactly 11 digits`, 'javascript', 'va-troubleshoot-2')}
        </CardContent>
      </Card>
    </Box>
  );

  // Verify Account Documentation
  const renderVerifyAccountDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        ✅ Verify Bank Account
      </Typography>
      <Typography variant="body1" paragraph>
        Verify a bank account number and retrieve the account holder's name. This is essential before making transfers to ensure you're sending money to the correct recipient.
      </Typography>

      <Alert severity="info" sx={{ mb: 3 }}>
        <strong>💡 Use Case:</strong> Always verify account details before initiating transfers to prevent sending money to wrong accounts.
      </Alert>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="POST" method="POST" size="small" />
          <Typography variant="body1" component="code">
            /api/gateway/banks/verify
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Verify bank account and get account holder name
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Parameters
      </Typography>

      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Box sx={{ '& > div': { py: 1, borderBottom: '1px solid', borderColor: 'divider' } }}>
            <Box sx={{ display: 'grid', gridTemplateColumns: '200px 100px 1fr', gap: 2 }}>
              <Typography variant="body2" fontWeight={600}>
                Parameter
              </Typography>
              <Typography variant="body2" fontWeight={600}>
                Required
              </Typography>
              <Typography variant="body2" fontWeight={600}>
                Description
              </Typography>
            </Box>
            {[
              { name: 'accountNumber', required: true, desc: 'Bank account number (10 digits)' },
              { name: 'bankCode', required: true, desc: 'Bank code from GET /api/gateway/banks' },
            ].map((param) => (
              <Box key={param.name} sx={{ display: 'grid', gridTemplateColumns: '200px 100px 1fr', gap: 2 }}>
                <Typography variant="body2" component="code">
                  {param.name}
                </Typography>
                <Chip
                  label={param.required ? 'Required' : 'Optional'}
                  size="small"
                  color={param.required ? 'error' : 'warning'}
                />
                <Typography variant="body2" color="text.secondary">
                  {param.desc}
                </Typography>
              </Box>
            ))}
          </Box>
        </CardContent>
      </Card>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/gateway/banks/verify" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -d '{
    "accountNumber": "0123456789",
    "bankCode": "058"
  }'`, 'bash', 'curl-verify-account')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "success": true,
  "data": {
    "accountNumber": "0123456789",
    "accountName": "JOHN DOE",
    "bankCode": "058"
  }
}`, 'json', 'response-verify-account')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Complete Integration Example
      </Typography>

      <Typography variant="body2" paragraph>
        Here's how to integrate account verification in your transfer flow:
      </Typography>

      {renderCodeBlock(`// Step 1: Get banks list (cache this)
const banks = await fetch('https://app.pointwave.ng/api/gateway/banks', {
  headers: {
    'Authorization': 'Bearer YOUR_SECRET_KEY',
    'x-api-key': 'YOUR_API_KEY',
    'x-business-id': 'YOUR_BUSINESS_ID'
  }
}).then(r => r.json());

// Step 2: User selects bank and enters account number
const selectedBank = '058'; // GTBank
const accountNumber = '0123456789';

// Step 3: Verify account before transfer
const verification = await fetch('https://app.pointwave.ng/api/gateway/banks/verify', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_SECRET_KEY',
    'x-api-key': 'YOUR_API_KEY',
    'x-business-id': 'YOUR_BUSINESS_ID',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    accountNumber: accountNumber,
    bankCode: selectedBank
  })
}).then(r => r.json());

if (verification.success) {
  console.log('Account Name:', verification.data.accountName);
  // Show account name to user for confirmation
  // Then proceed with transfer
} else {
  console.error('Verification failed:', verification.message);
}`, 'javascript', 'verify-integration')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Account verification failed" or "Invalid account number"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> The account number doesn't exist or is invalid.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Ensure the account number is correct:
          </Typography>
          {renderCodeBlock(`// ✅ CORRECT
"accountNumber": "0123456789"  // 10 digits, no spaces

// ❌ WRONG
"accountNumber": "012 345 6789"  // Has spaces
"accountNumber": "012-345-6789"  // Has dashes
"accountNumber": "123456789"     // Only 9 digits`, 'javascript', 'verify-troubleshoot-1')}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Invalid bank code"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> The bank code you provided doesn't exist.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Get the correct bank code from the banks list:
          </Typography>
          {renderCodeBlock(`// Get banks first
GET /api/gateway/banks

// Use the bankCode from response:
{
  "bankCode": "058",  // Use this
  "bankName": "GTBank"
}`, 'javascript', 'verify-troubleshoot-2')}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#e8f5e9', border: '1px solid #4caf50' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            💡 Best Practices
          </Typography>
          <Typography variant="body2" component="div">
            <ul style={{ marginTop: 8, paddingLeft: 20 }}>
              <li>Always verify accounts before making transfers</li>
              <li>Show the account name to users for confirmation</li>
              <li>Cache bank codes to avoid repeated API calls</li>
              <li>Validate account number format (10 digits) before API call</li>
              <li>Handle verification failures gracefully</li>
              <li>Don't proceed with transfer if verification fails</li>
            </ul>
          </Typography>
        </CardContent>
      </Card>

      <Alert severity="success" sx={{ mt: 3 }}>
        <strong>✅ Pro Tip:</strong> Account verification is instant and helps prevent costly transfer mistakes. Always verify before sending money!
      </Alert>
    </Box>
  );

  // Transfers Documentation
  const renderTransfersDoc = () => (
    <Box>
      <Typography variant="h4" gutterBottom>
        💸 Initiate Transfer
      </Typography>
      <Typography variant="body1" paragraph>
        Send money to any Nigerian bank account.
      </Typography>

      <EndpointBox>
        <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
          <MethodChip label="POST" method="POST" size="small" />
          <Typography variant="body1" component="code">
            /api/v1/transfers
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Initiate a bank transfer
        </Typography>
      </EndpointBox>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Request Example
      </Typography>

      {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/v1/transfers" \\
  -H "Authorization: Bearer YOUR_SECRET_KEY" \\
  -H "x-api-key: YOUR_API_KEY" \\
  -H "x-business-id: YOUR_BUSINESS_ID" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: $(uuidgen)" \\
  -d '{
    "amount": 5000,
    "bank_code": "058",
    "account_number": "0123456789",
    "account_name": "Jane Doe",
    "narration": "Payment for services",
    "reference": "TXN-' + Date.now() + '"
  }'`, 'bash', 'curl-transfer')}

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        Response Example
      </Typography>

      {renderCodeBlock(`{
  "status": "success",
  "message": "Transfer initiated successfully",
  "data": {
    "id": 789,
    "reference": "TXN-1708257600000",
    "amount": 5000,
    "fee": 30,
    "total_amount": 5030,
    "bank_code": "058",
    "bank_name": "GTBank",
    "account_number": "0123456789",
    "account_name": "Jane Doe",
    "narration": "Payment for services",
    "status": "pending",
    "created_at": "2026-02-18T10:00:00Z"
  }
}`, 'json', 'response-transfer')}

      <Alert severity="warning" sx={{ mt: 3 }}>
        <strong>⚠️ Important:</strong> Transfers are processed immediately but may take a few minutes to reflect in the recipient's account.
      </Alert>

      <Typography variant="h6" sx={{ mt: 4, mb: 2 }}>
        🔧 Troubleshooting
      </Typography>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Insufficient funds"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> Your wallet balance is too low to complete the transfer.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Check your balance first using GET /api/v1/balance:
          </Typography>
          {renderCodeBlock(`// Step 1: Check balance
GET /api/v1/balance

// Response: { "data": { "balance": 50000.00 } }

// Step 2: Ensure you have enough for amount + fee
// Transfer amount: ₦5,000
// Transfer fee: ₦30
// Total needed: ₦5,030`, 'javascript', 'transfer-balance-troubleshoot')}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Invalid bank code"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> The bank code you provided doesn't exist.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Get the correct bank code from GET /api/v1/banks:
          </Typography>
          {renderCodeBlock(`// Get banks list
GET /api/v1/banks

// Find your bank and use its code:
{
  "name": "GTBank",
  "code": "058"  // Use this code
}`, 'javascript', 'transfer-bank-troubleshoot')}
        </CardContent>
      </Card>

      <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800' }}>
        <CardContent>
          <Typography variant="subtitle2" gutterBottom fontWeight={600}>
            ❌ Error: "Duplicate request-id" or "Transaction already processed"
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Problem:</strong> You're using the same reference/request-id for multiple transfers.
          </Typography>
          <Typography variant="body2" paragraph>
            <strong>Solution:</strong> Generate a unique reference for each transfer:
          </Typography>
          {renderCodeBlock(`// ❌ WRONG - Same reference
"reference": "TXN-001"  // Used multiple times

// ✅ CORRECT - Unique reference
"reference": "TXN-" + Date.now()           // JavaScript
"reference": "TXN-" . time()               // PHP
"reference": f"TXN-{int(time.time())}"     // Python

// Or use UUID
"reference": crypto.randomUUID()           // JavaScript`, 'javascript', 'transfer-ref-troubleshoot')}
        </CardContent>
      </Card>
    </Box>
  );
    // KYC Verification Documentation
    const renderKYCDoc = () => (
      <Box>
        <Typography variant="h4" gutterBottom>
          🔐 KYC Verification
        </Typography>
        <Typography variant="body1" paragraph>
          Verify customer identity using BVN (Bank Verification Number) or NIN (National Identification Number).
        </Typography>

        <Alert severity="info" sx={{ mb: 3 }}>
          <strong>💡 Smart Charging:</strong> KYC verification is FREE during your company onboarding.
          Once verified, you'll be charged per verification when using the API to verify your customers.
        </Alert>

        <Alert severity="warning" sx={{ mb: 3 }}>
          <strong>⚠️ Pricing:</strong> BVN verification costs ₦25, NIN verification costs ₦45.
          Charges are automatically deducted from your wallet balance.
        </Alert>

        {/* BVN Verification */}
        <Typography variant="h5" sx={{ mt: 4, mb: 2 }}>
          📋 Verify BVN
        </Typography>

        <EndpointBox>
          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
            <MethodChip label="POST" method="POST" size="small" />
            <Typography variant="body1" component="code">
              /api/gateway/kyc/verify/bvn
            </Typography>
          </Box>
          <Typography variant="body2" color="text.secondary">
            Verify a customer's Bank Verification Number
          </Typography>
        </EndpointBox>

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Request Example
        </Typography>

        {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/gateway/kyc/verify/bvn" \\
    -H "Authorization: Bearer YOUR_SECRET_KEY" \\
    -H "x-api-key: YOUR_API_KEY" \\
    -H "x-business-id: YOUR_BUSINESS_ID" \\
    -H "Content-Type: application/json" \\
    -H "Idempotency-Key: $(uuidgen)" \\
    -d '{
      "bvn": "22490148602"
    }'`, 'bash', 'curl-bvn')}

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Response Example
        </Typography>

        {renderCodeBlock(`{
    "status": true,
    "request_id": "8bbce774-5044-426b-bf6d-b5733a7f5778",
    "message": "BVN verified successfully",
    "data": {
      "verified": true,
      "bvn": "22490148602",
      "data": {
        "firstName": "JOHN",
        "middleName": "CHUKWUEMEKA",
        "lastName": "DOE",
        "dateOfBirth": "01-Jan-1990",
        "phoneNumber": "08012345678",
        "email": "john.doe@example.com",
        "gender": "Male",
        "enrollmentBank": "058",
        "enrollmentBranch": "Victoria Island",
        "registrationDate": "15-Mar-2015",
        "watchListed": "NO"
      },
      "charged": true,
      "charge_amount": 25.00,
      "transaction_reference": "KYC_ENHANCED_BVN_1771666890_8059"
    }
  }`, 'json', 'response-bvn')}

        {/* NIN Verification */}
        <Typography variant="h5" sx={{ mt: 5, mb: 2 }}>
          🆔 Verify NIN
        </Typography>

        <EndpointBox>
          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
            <MethodChip label="POST" method="POST" size="small" />
            <Typography variant="body1" component="code">
              /api/gateway/kyc/verify/nin
            </Typography>
          </Box>
          <Typography variant="body2" color="text.secondary">
            Verify a customer's National Identification Number
          </Typography>
        </EndpointBox>

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Request Example
        </Typography>

        {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/gateway/kyc/verify/nin" \\
    -H "Authorization: Bearer YOUR_SECRET_KEY" \\
    -H "x-api-key: YOUR_API_KEY" \\
    -H "x-business-id: YOUR_BUSINESS_ID" \\
    -H "Content-Type: application/json" \\
    -H "Idempotency-Key: $(uuidgen)" \\
    -d '{
      "nin": "35257106066"
    }'`, 'bash', 'curl-nin')}

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Response Example
        </Typography>

        {renderCodeBlock(`{
    "status": true,
    "request_id": "9be2f92e-9056-434a-8ba7-743e77624cc3",
    "message": "NIN verified successfully",
    "data": {
      "verified": true,
      "nin": "35257106066",
      "data": {
        "firstName": "JANE",
        "middleName": "CHIOMA",
        "lastName": "SMITH",
        "dateOfBirth": "15-May-1985",
        "phoneNumber": "08098765432",
        "email": "jane.smith@example.com",
        "gender": "Female",
        "residenceAddress": "123 Lagos Street, Victoria Island, Lagos",
        "residenceState": "Lagos",
        "residenceLga": "Eti-Osa",
        "photo": "base64_encoded_photo_string"
      },
      "charged": true,
      "charge_amount": 45.00,
      "transaction_reference": "KYC_ENHANCED_NIN_1771666889_6606"
    }
  }`, 'json', 'response-nin')}

        {/* Bank Account Verification */}
        <Typography variant="h5" sx={{ mt: 5, mb: 2 }}>
          🏦 Verify Bank Account
        </Typography>

        <EndpointBox>
          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
            <MethodChip label="POST" method="POST" size="small" />
            <Typography variant="body1" component="code">
              /api/gateway/kyc/verify/bank-account
            </Typography>
          </Box>
          <Typography variant="body2" color="text.secondary">
            Verify bank account ownership and get account name
          </Typography>
        </EndpointBox>

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Request Example
        </Typography>

        {renderCodeBlock(`curl -X POST "https://app.pointwave.ng/api/gateway/kyc/verify/bank-account" \\
    -H "Authorization: Bearer YOUR_SECRET_KEY" \\
    -H "x-api-key: YOUR_API_KEY" \\
    -H "x-business-id: YOUR_BUSINESS_ID" \\
    -H "Content-Type: application/json" \\
    -H "Idempotency-Key: $(uuidgen)" \\
    -d '{
      "account_number": "0123456789",
      "bank_code": "058"
    }'`, 'bash', 'curl-bank-verify')}

        <Typography variant="h6" sx={{ mt: 3, mb: 2 }}>
          Response Example
        </Typography>

        {renderCodeBlock(`{
    "status": true,
    "request_id": "7cd3e8f1-2a4b-4c5d-8e9f-1a2b3c4d5e6f",
    "message": "Bank account verified successfully",
    "data": {
      "verified": true,
      "account_number": "0123456789",
      "account_name": "JOHN DOE",
      "bank_code": "058",
      "bank_name": "GTBank",
      "charged": true,
      "charge_amount": 50.00,
      "transaction_reference": "KYC_BANK_ACCOUNT_1771666900_1234"
    }
  }`, 'json', 'response-bank-verify')}

        {/* Troubleshooting */}
        <Typography variant="h6" sx={{ mt: 5, mb: 2 }}>
          🔧 Troubleshooting
        </Typography>

        <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
          <CardContent>
            <Typography variant="subtitle2" gutterBottom fontWeight={600}>
              ❌ Error: "Insufficient balance"
            </Typography>
            <Typography variant="body2" paragraph>
              <strong>Problem:</strong> Your wallet doesn't have enough funds to pay for the KYC verification.
            </Typography>
            <Typography variant="body2" paragraph>
              <strong>Solution:</strong> Check your balance and ensure you have enough:
            </Typography>
            {renderCodeBlock(`// Check balance first
  GET /api/gateway/balance

  // Ensure you have:
  // - BVN verification: ₦25
  // - NIN verification: ₦45
  // - Bank account verification: ₦50`, 'javascript', 'kyc-balance-troubleshoot')}
          </CardContent>
        </Card>

        <Card sx={{ bgcolor: '#fff3e0', border: '1px solid #ff9800', mb: 2 }}>
          <CardContent>
            <Typography variant="subtitle2" gutterBottom fontWeight={600}>
              ❌ Error: "Invalid BVN/NIN format"
            </Typography>
            <Typography variant="body2" paragraph>
              <strong>Problem:</strong> The BVN or NIN you provided is not in the correct format.
            </Typography>
            <Typography variant="body2" paragraph>
              <strong>Solution:</strong> Ensure the format is correct:
            </Typography>
            {renderCodeBlock(`// ✅ CORRECT
  "bvn": "22490148602"  // 11 digits
  "nin": "35257106066"  // 11 digits

  // ❌ WRONG
  "bvn": "2249014860"   // Too short
  "nin": "352-571-0606" // Contains dashes`, 'javascript', 'kyc-format-troubleshoot')}
          </CardContent>
        </Card>

        <Card sx={{ bgcolor: '#e8f5e9', border: '1px solid #4caf50' }}>
          <CardContent>
            <Typography variant="subtitle2" gutterBottom fontWeight={600}>
              💡 Best Practices
            </Typography>
            <Typography variant="body2" component="div">
              <ul style={{ marginTop: 8, paddingLeft: 20 }}>
                <li>Always validate BVN/NIN format before making API calls</li>
                <li>Cache verification results to avoid duplicate charges</li>
                <li>Use Idempotency-Key to prevent duplicate requests</li>
                <li>Check wallet balance before verification</li>
                <li>Store verification data securely in your database</li>
                <li>Monitor KYC transactions in your dashboard</li>
              </ul>
            </Typography>
          </CardContent>
        </Card>

        <Alert severity="success" sx={{ mt: 3 }}>
          <strong>✅ Transaction Tracking:</strong> All KYC verifications appear in your transaction history
          with the beneficiary showing the verified identifier (e.g., "BVN: 22490148602" or "NIN: 35257106066").
        </Alert>
      </Box>
    );



  return (
    <Page title="API Documentation">
      <Container maxWidth="lg">
        <HeaderBreadcrumbs
          heading="API Documentation"
          links={[
            { name: 'Dashboard', href: PATH_DASHBOARD.general.app },
            { name: 'API Documentation' },
          ]}
        />

        <Card>
          <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
            <Tabs value={activeTab} onChange={(e, v) => setActiveTab(v)} variant="scrollable" scrollButtons="auto">
              <Tab label="Create Customer" />
              <Tab label="Update Customer" />
              <Tab label="Create Virtual Account" />
              <Tab label="Update Virtual Account" />
              <Tab label="Delete Virtual Account" />
              <Tab label="Get Banks" />
              <Tab label="Verify Account" />
              <Tab label="Transfers" />
              <Tab label="KYC Verification" />
            </Tabs>
          </Box>

          <CardContent sx={{ p: 4 }}>
            {activeTab === 0 && renderCreateCustomerDoc()}
            {activeTab === 1 && renderUpdateCustomerDoc()}
            {activeTab === 2 && renderVirtualAccountsDoc()}
            {activeTab === 3 && renderUpdateVADoc()}
            {activeTab === 4 && renderDeleteVADoc()}
            {activeTab === 5 && renderBanksDoc()}
            {activeTab === 6 && renderVerifyAccountDoc()}
            {activeTab === 7 && renderTransfersDoc()}
            {activeTab === 8 && renderKYCDoc()}
          </CardContent>
        </Card>

        <Alert severity="info" sx={{ mt: 3 }}>
          <Typography variant="body2">
            <strong>Need more help?</strong> Visit our full documentation at{' '}
            <a href="/docs" target="_blank" rel="noopener noreferrer">
              /docs
            </a>{' '}
            or contact support at support@pointwave.ng
          </Typography>
        </Alert>
      </Container>
    </Page>
  );
}
