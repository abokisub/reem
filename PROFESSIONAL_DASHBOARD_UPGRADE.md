# Professional Dashboard Upgrade - Complete Guide

## Overview

This document outlines all changes needed to make the company dashboard fully professional, matching the reference images provided.

## Pages to Update

1. RA Transactions Page - Add Refund & Notification features
2. Customers Page - Professional layout with search and export
3. Wallet Page - Professional layout with transaction history

## IMPORTANT NOTES

⚠️ **All frontend changes require manual build and upload**
⚠️ **Test each feature after deployment to avoid errors**
⚠️ **Backend APIs are already in place - only frontend needs updates**

---

## 1. RA TRANSACTIONS PAGE UPDATES

### Features to Add:

#### A. Modal Action Buttons
- **Initiate Refund** button (red/orange color)
- **Resend Notification** button (blue color)
- Both buttons should be at the bottom of the modal

#### B. Working Search, Filter, Export
- Search bar should filter transactions in real-time
- Filter button should show status filters (All, Successful, Failed, Pending)
- Export button should download CSV/Excel of transactions

### Implementation:

**File: `frontend/src/pages/dashboard/RATransactions.js`**

Add these functions:

```javascript
const [refundLoading, setRefundLoading] = useState(false);
const [notificationLoading, setNotificationLoading] = useState(false);

const handleInitiateRefund = async () => {
    if (!selectedTransaction) return;
    
    setRefundLoading(true);
    try {
        await axios.post(`/api/transactions/${selectedTransaction.id}/refund`, {
            transaction_id: selectedTransaction.transaction_id,
            amount: selectedTransaction.amount
        });
        enqueueSnackbar('Refund initiated successfully', { variant: 'success' });
        handleCloseModal();
        initialize(page, rowsPerPage, filterName);
    } catch (error) {
        enqueueSnackbar(error.response?.data?.message || 'Failed to initiate refund', { variant: 'error' });
    } finally {
        setRefundLoading(false);
    }
};

const handleResendNotification = async () => {
    if (!selectedTransaction) return;
    
    setNotificationLoading(true);
    try {
        await axios.post(`/api/transactions/${selectedTransaction.id}/resend-notification`, {
            transaction_id: selectedTransaction.transaction_id
        });
        enqueueSnackbar('Notification sent successfully', { variant: 'success' });
    } catch (error) {
        enqueueSnackbar(error.response?.data?.message || 'Failed to send notification', { variant: 'error' });
    } finally {
        setNotificationLoading(false);
    }
};

const handleExport = async () => {
    try {
        const response = await axios.get(
            `/api/system/all/ra-history/records/${AccessToken}/secure/export`,
            { responseType: 'blob' }
        );
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `ra-transactions-${new Date().getTime()}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        enqueueSnackbar('Export successful', { variant: 'success' });
    } catch (error) {
        enqueueSnackbar('Export failed', { variant: 'error' });
    }
};
```

Update the modal DialogActions:

```javascript
<DialogActions sx={{ p: 2, gap: 1 }}>
    <Button 
        onClick={handleInitiateRefund}
        variant="contained"
        color="error"
        disabled={refundLoading || selectedTransaction?.status !== 'successful'}
        startIcon={<Iconify icon="eva:refresh-fill" />}
    >
        {refundLoading ? 'Processing...' : 'Initiate Refund'}
    </Button>
    <Button 
        onClick={handleResendNotification}
        variant="contained"
        color="info"
        disabled={notificationLoading}
        startIcon={<Iconify icon="eva:email-fill" />}
    >
        {notificationLoading ? 'Sending...' : 'Resend Notification'}
    </Button>
    <Button onClick={handleCloseModal} variant="outlined">
        Close
    </Button>
</DialogActions>
```

Update the header buttons to be functional:

```javascript
<Button
    variant="outlined"
    startIcon={<Iconify icon="eva:download-fill" />}
    onClick={handleExport}
    sx={{ borderRadius: 1.5 }}
>
    Export
</Button>
```

---

## 2. CUSTOMERS PAGE

### Current Issues:
- Page may not exist or is not properly styled
- Missing search and export functionality
- No customer details view

### Required Features:
- Total customers count display
- "Create New Customer" button
- Search customers functionality
- Export CSV button
- Customer list table with columns:
  - Customer ID
  - Customer Name
  - Customer Email
  - Phone Number
  - Date
  - Action (View icon)
- Click view icon to see customer details page

### Implementation:

**File: `frontend/src/pages/dashboard/Customers.js`** (may need to be created)

```javascript
import { useState, useEffect } from 'react';
import {
    Card,
    Table,
    TableRow,
    TableBody,
    TableCell,
    Container,
    Typography,
    TableContainer,
    TablePagination,
    Box,
    Button,
    IconButton,
    styled,
    alpha
} from '@mui/material';
import { useSnackbar } from 'notistack';
import { useNavigate } from 'react-router-dom';
import Page from '../../components/Page';
import Label from '../../components/Label';
import Scrollbar from '../../components/Scrollbar';
import Iconify from '../../components/Iconify';
import { TransHead, PlanToolbar } from '../../sections/admin/user/list';
import axios from '../../utils/axios';
import { PATH_DASHBOARD } from '../../routes/paths';
import useSettings from '../../hooks/useSettings';

const TABLE_HEAD = [
    { id: 'customer_id', label: 'Customer ID', alignRight: false },
    { id: 'name', label: 'Customer Name', alignRight: false },
    { id: 'email', label: 'Customer Email', alignRight: false },
    { id: 'phone', label: 'Phone Number', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'action', label: 'Action', alignRight: true },
];

const HeaderCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(3),
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    borderRadius: 16,
    marginBottom: theme.spacing(4),
}));

export default function Customers() {
    const { themeStretch } = useSettings();
    const { enqueueSnackbar } = useSnackbar();
    const navigate = useNavigate();
    
    const [customers, setCustomers] = useState([]);
    const [page, setPage] = useState(0);
    const [load, setLoad] = useState(true);
    const [totalPage, setTotal] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [totalCustomers, setTotalCustomers] = useState(0);

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        initialize(page, rowsPerPage, filterName);
    }, []);

    const initialize = async (pag, limit = 10, search = '') => {
        const Habukhan_page = pag + 1;
        setLoad(true);

        try {
            const response = await axios.get(
                `/api/customers/${AccessToken}/secure?page=${Habukhan_page}&limit=${limit}&search=${search}`
            );

            const allCustomers = response.data?.customers?.data || [];
            setCustomers(allCustomers);
            setTotal(response.data?.customers?.total || 0);
            setTotalCustomers(response.data?.total_count || 0);
            setLoad(false);
            setPage(pag);
        } catch (error) {
            enqueueSnackbar('Error fetching customers', { variant: 'error' });
            setLoad(false);
        }
    };

    const handleViewCustomer = (customerId) => {
        navigate(`${PATH_DASHBOARD.general.customers}/${customerId}`);
    };

    const handleExport = async () => {
        try {
            const response = await axios.get(
                `/api/customers/${AccessToken}/secure/export`,
                { responseType: 'blob' }
            );
            
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `customers-${new Date().getTime()}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            enqueueSnackbar('Export successful', { variant: 'success' });
        } catch (error) {
            enqueueSnackbar('Export failed', { variant: 'error' });
        }
    };

    return (
        <Page title="Customers">
            <Container maxWidth={themeStretch ? false : 'lg'}>
                <HeaderCardStyle>
                    <Box>
                        <Typography variant="h4" sx={{ fontWeight: 800, color: 'white', mb: 0.5 }}>
                            Customers
                        </Typography>
                        <Typography variant="body2" sx={{ color: 'rgba(255, 255, 255, 0.8)' }}>
                            Overview &nbsp;&bull;&nbsp; Customers
                        </Typography>
                    </Box>
                </HeaderCardStyle>

                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
                    <Box>
                        <Typography variant="subtitle2" sx={{ color: 'text.secondary' }}>
                            Total Customers
                        </Typography>
                        <Typography variant="h3" sx={{ fontWeight: 800, color: 'primary.main' }}>
                            {totalCustomers}
                        </Typography>
                    </Box>
                    <Button
                        variant="contained"
                        startIcon={<Iconify icon="eva:plus-fill" />}
                        onClick={() => navigate(PATH_DASHBOARD.general.createCustomer)}
                    >
                        Create New Customer
                    </Button>
                </Box>

                <Card sx={{ borderRadius: 2 }}>
                    <Box sx={{ p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <PlanToolbar filterName={filterName} onFilterName={(val) => {
                            setFilterName(val);
                            initialize(0, rowsPerPage, val);
                        }} />
                        <Button
                            variant="contained"
                            startIcon={<Iconify icon="eva:download-fill" />}
                            onClick={handleExport}
                        >
                            Export CSV
                        </Button>
                    </Box>

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 800 }}>
                            <Table>
                                <TransHead
                                    order={'desc'}
                                    orderBy={'date'}
                                    headLabel={TABLE_HEAD}
                                    rowCount={customers.length}
                                />
                                <TableBody>
                                    {!load && customers.map((customer, index) => (
                                        <TableRow hover key={customer.id || index}>
                                            <TableCell>{customer.customer_id || customer.id}</TableCell>
                                            <TableCell sx={{ fontWeight: 600 }}>{customer.name}</TableCell>
                                            <TableCell>{customer.email}</TableCell>
                                            <TableCell>{customer.phone}</TableCell>
                                            <TableCell>{customer.created_at}</TableCell>
                                            <TableCell align="right">
                                                <IconButton 
                                                    color="primary" 
                                                    onClick={() => handleViewCustomer(customer.id)}
                                                >
                                                    <Iconify icon="eva:eye-fill" />
                                                </IconButton>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {load && (
                                        <TableRow>
                                            <TableCell align="center" colSpan={6} sx={{ py: 8 }}>
                                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>
                                                    Loading customers...
                                                </Typography>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </Scrollbar>

                    <TablePagination
                        rowsPerPageOptions={[5, 10, 25, 100]}
                        component="div"
                        count={totalPage}
                        rowsPerPage={rowsPerPage}
                        page={page}
                        onPageChange={(e, p) => initialize(p, rowsPerPage, filterName)}
                        onRowsPerPageChange={(e) => {
                            setRowsPerPage(parseInt(e.target.value, 10));
                            initialize(0, e.target.value, filterName);
                            setPage(0);
                        }}
                    />
                </Card>
            </Container>
        </Page>
    );
}
```

---

## 3. WALLET PAGE

### Required Features:
- Large balance display card (purple/blue gradient)
- "Withdraw" button
- "Generate Account" button (if no account number)
- Transaction History section with:
  - Search bar
  - Filter dropdown
  - Transaction table with columns:
    - Transaction Ref
    - Amount
    - Type (Withdrawal/Deposit)
    - Status
    - Old Balance
    - New Balance
    - Date
    - Action (View icon)

### Implementation:

**File: `frontend/src/pages/dashboard/WalletSummary.js`** (update existing or create)

```javascript
import { useState, useEffect } from 'react';
import {
    Card,
    Table,
    TableRow,
    TableBody,
    TableCell,
    Container,
    Typography,
    TableContainer,
    TablePagination,
    Box,
    Button,
    IconButton,
    styled,
    alpha,
    MenuItem,
    Select,
    FormControl
} from '@mui/material';
import { useSnackbar } from 'notistack';
import Page from '../../components/Page';
import Label from '../../components/Label';
import Scrollbar from '../../components/Scrollbar';
import Iconify from '../../components/Iconify';
import { TransHead, PlanToolbar } from '../../sections/admin/user/list';
import axios from '../../utils/axios';
import { fCurrency } from '../../utils/formatNumber';
import useSettings from '../../hooks/useSettings';

const TABLE_HEAD = [
    { id: 'ref', label: 'Transaction Ref', alignRight: false },
    { id: 'amount', label: 'Amount', alignRight: false },
    { id: 'type', label: 'Type', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'old_balance', label: 'Old Balance', alignRight: false },
    { id: 'new_balance', label: 'New Balance', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'action', label: 'Action', alignRight: true },
];

const BalanceCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(4),
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    borderRadius: 16,
    marginBottom: theme.spacing(4),
    color: 'white',
}));

const AlertCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(2),
    background: alpha(theme.palette.warning.main, 0.1),
    border: `1px solid ${theme.palette.warning.main}`,
    borderRadius: 12,
    marginBottom: theme.spacing(4),
}));

export default function WalletSummary() {
    const { themeStretch } = useSettings();
    const { enqueueSnackbar } = useSnackbar();
    
    const [balance, setBalance] = useState(0);
    const [accountNumber, setAccountNumber] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [page, setPage] = useState(0);
    const [load, setLoad] = useState(true);
    const [totalPage, setTotal] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [filterStatus, setFilterStatus] = useState('all');
    const [rowsPerPage, setRowsPerPage] = useState(10);

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        fetchWalletData();
        initialize(page, rowsPerPage, filterName, filterStatus);
    }, []);

    const fetchWalletData = async () => {
        try {
            const response = await axios.get(`/api/wallet/${AccessToken}/secure`);
            setBalance(response.data?.balance || 0);
            setAccountNumber(response.data?.account_number);
        } catch (error) {
            enqueueSnackbar('Error fetching wallet data', { variant: 'error' });
        }
    };

    const initialize = async (pag, limit = 10, search = '', status = 'all') => {
        const Habukhan_page = pag + 1;
        setLoad(true);

        try {
            const response = await axios.get(
                `/api/wallet/transactions/${AccessToken}/secure?page=${Habukhan_page}&limit=${limit}&search=${search}&status=${status}`
            );

            const allTransactions = response.data?.transactions?.data || [];
            setTransactions(allTransactions);
            setTotal(response.data?.transactions?.total || 0);
            setLoad(false);
            setPage(pag);
        } catch (error) {
            enqueueSnackbar('Error fetching transactions', { variant: 'error' });
            setLoad(false);
        }
    };

    const handleGenerateAccount = async () => {
        try {
            const response = await axios.post(`/api/wallet/generate-account/${AccessToken}/secure`);
            setAccountNumber(response.data?.account_number);
            enqueueSnackbar('Account generated successfully', { variant: 'success' });
        } catch (error) {
            enqueueSnackbar('Failed to generate account', { variant: 'error' });
        }
    };

    return (
        <Page title="Wallet Account">
            <Container maxWidth={themeStretch ? false : 'lg'}>
                <HeaderCardStyle>
                    <Box>
                        <Typography variant="h4" sx={{ fontWeight: 800, color: 'white', mb: 0.5 }}>
                            Wallet Account
                        </Typography>
                        <Typography variant="body2" sx={{ color: 'rgba(255, 255, 255, 0.8)' }}>
                            Overview &nbsp;&bull;&nbsp; Wallet Account
                        </Typography>
                    </Box>
                </HeaderCardStyle>

                <BalanceCardStyle>
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Box>
                            <Typography variant="subtitle2" sx={{ color: 'rgba(255, 255, 255, 0.8)', mb: 1 }}>
                                Available Balance
                            </Typography>
                            <Typography variant="h3" sx={{ fontWeight: 800, color: 'white' }}>
                                ₦{fCurrency(balance)}
                            </Typography>
                        </Box>
                        <Button
                            variant="contained"
                            sx={{ bgcolor: 'white', color: 'primary.main', '&:hover': { bgcolor: 'grey.100' } }}
                            startIcon={<Iconify icon="eva:arrow-upward-fill" />}
                        >
                            Withdraw
                        </Button>
                    </Box>
                </BalanceCardStyle>

                {!accountNumber && (
                    <AlertCardStyle>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <Box>
                                <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
                                    Account Setup Required
                                </Typography>
                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>
                                    You need to generate an account number to enable wallet funding
                                </Typography>
                            </Box>
                            <Button
                                variant="contained"
                                color="primary"
                                onClick={handleGenerateAccount}
                            >
                                Generate Account
                            </Button>
                        </Box>
                    </AlertCardStyle>
                )}

                <Card sx={{ borderRadius: 2 }}>
                    <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
                        <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                            Transaction History
                        </Typography>
                    </Box>

                    <Box sx={{ p: 2, display: 'flex', gap: 2, alignItems: 'center' }}>
                        <PlanToolbar 
                            filterName={filterName} 
                            onFilterName={(val) => {
                                setFilterName(val);
                                initialize(0, rowsPerPage, val, filterStatus);
                            }} 
                        />
                        <FormControl sx={{ minWidth: 120 }}>
                            <Select
                                value={filterStatus}
                                onChange={(e) => {
                                    setFilterStatus(e.target.value);
                                    initialize(0, rowsPerPage, filterName, e.target.value);
                                }}
                                size="small"
                            >
                                <MenuItem value="all">All</MenuItem>
                                <MenuItem value="successful">Successful</MenuItem>
                                <MenuItem value="pending">Pending</MenuItem>
                                <MenuItem value="failed">Failed</MenuItem>
                            </Select>
                        </FormControl>
                    </Box>

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 800 }}>
                            <Table>
                                <TransHead
                                    order={'desc'}
                                    orderBy={'date'}
                                    headLabel={TABLE_HEAD}
                                    rowCount={transactions.length}
                                />
                                <TableBody>
                                    {!load && transactions.map((tx, index) => (
                                        <TableRow hover key={tx.id || index}>
                                            <TableCell sx={{ fontWeight: 600 }}>
                                                {tx.reference || tx.transaction_id}
                                            </TableCell>
                                            <TableCell sx={{ fontWeight: 800 }}>
                                                ₦{fCurrency(tx.amount)}
                                            </TableCell>
                                            <TableCell>
                                                <Label
                                                    variant="soft"
                                                    color={tx.type === 'credit' ? 'success' : 'error'}
                                                >
                                                    {tx.type === 'credit' ? 'Deposit' : 'Withdrawal'}
                                                </Label>
                                            </TableCell>
                                            <TableCell>
                                                <Label
                                                    variant="soft"
                                                    color={
                                                        tx.status === 'successful' || tx.status === 'success'
                                                            ? 'success'
                                                            : tx.status === 'failed'
                                                                ? 'error'
                                                                : 'warning'
                                                    }
                                                >
                                                    {tx.status}
                                                </Label>
                                            </TableCell>
                                            <TableCell>₦{fCurrency(tx.balance_before || 0)}</TableCell>
                                            <TableCell>₦{fCurrency(tx.balance_after || 0)}</TableCell>
                                            <TableCell>{tx.created_at}</TableCell>
                                            <TableCell align="right">
                                                <IconButton color="primary">
                                                    <Iconify icon="eva:eye-fill" />
                                                </IconButton>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {load && (
                                        <TableRow>
                                            <TableCell align="center" colSpan={8} sx={{ py: 8 }}>
                                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>
                                                    Loading transactions...
                                                </Typography>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </Scrollbar>

                    <TablePagination
                        rowsPerPageOptions={[5, 10, 25, 100]}
                        component="div"
                        count={totalPage}
                        rowsPerPage={rowsPerPage}
                        page={page}
                        onPageChange={(e, p) => initialize(p, rowsPerPage, filterName, filterStatus)}
                        onRowsPerPageChange={(e) => {
                            setRowsPerPage(parseInt(e.target.value, 10));
                            initialize(0, e.target.value, filterName, filterStatus);
                            setPage(0);
                        }}
                    />
                </Card>
            </Container>
        </Page>
    );
}
```

---

## BACKEND API ENDPOINTS NEEDED

### 1. Refund Endpoint
```php
// POST /api/transactions/{id}/refund
Route::post('transactions/{id}/refund', [TransactionController::class, 'initiateRefund']);
```

### 2. Resend Notification Endpoint
```php
// POST /api/transactions/{id}/resend-notification
Route::post('transactions/{id}/resend-notification', [TransactionController::class, 'resendNotification']);
```

### 3. Export Endpoints
```php
// GET /api/system/all/ra-history/records/{id}/secure/export
Route::get('system/all/ra-history/records/{id}/secure/export', [Trans::class, 'exportRATransactions']);

// GET /api/customers/{id}/secure/export
Route::get('customers/{id}/secure/export', [CustomerController::class, 'exportCustomers']);
```

---

## DEPLOYMENT CHECKLIST

### Backend:
- [ ] Add refund endpoint
- [ ] Add resend notification endpoint
- [ ] Add export endpoints
- [ ] Test all endpoints with Postman
- [ ] Push to GitHub
- [ ] Deploy to production server

### Frontend:
- [ ] Update RATransactions.js with refund/notification buttons
- [ ] Update/Create Customers.js page
- [ ] Update/Create WalletSummary.js page
- [ ] Test all pages locally
- [ ] Build frontend: `npm run build`
- [ ] Upload build folder to server
- [ ] Test on production

### Testing:
- [ ] Test RA Transactions page - all features work
- [ ] Test Customers page - search, export, view customer
- [ ] Test Wallet page - balance display, transaction history
- [ ] Test refund functionality
- [ ] Test notification resend
- [ ] Test export functionality
- [ ] Test on mobile devices
- [ ] Check for console errors

---

## NOTES

1. **Error Handling**: All API calls have try-catch blocks to prevent crashes
2. **Loading States**: All buttons show loading state during API calls
3. **Success Messages**: User gets feedback for every action
4. **Professional Design**: Matches reference images with gradients and proper spacing
5. **Responsive**: All pages work on mobile and desktop
6. **Search**: Real-time search filtering
7. **Export**: CSV download functionality
8. **Pagination**: Proper pagination on all tables

---

## SUPPORT

If you encounter any errors:
1. Check browser console for error messages
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify API endpoints are working with Postman
4. Ensure frontend build is uploaded correctly
5. Clear browser cache and try again

