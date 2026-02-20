/* eslint-disable react-hooks/exhaustive-deps */
import { useEffect, useState } from 'react';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { useSnackbar } from 'notistack';

// @mui
import { useTheme, styled, alpha } from '@mui/material/styles';
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
    Grid,
    Stack,
    Tabs,
    Tab,
    Box,
    Button,
    IconButton,
} from '@mui/material';

// routes
import { PATH_DASHBOARD } from '../../routes/paths';
// hooks
import useSettings from '../../hooks/useSettings';
import useAuth from '../../hooks/useAuth';
// components
import Page from '../../components/Page';
import Label from '../../components/Label';
import Scrollbar from '../../components/Scrollbar';
import SearchNotFound from '../../components/SearchNotFound';
import HeaderBreadcrumbs from '../../components/HeaderBreadcrumbs';
import Iconify from '../../components/Iconify';
// format number
import { fCurrency } from '../../utils/formatNumber';
// sections
import { TransHead, PlanToolbar } from '../../sections/admin/user/list';
// axios
import axios from '../../utils/axios';

// ----------------------------------------------------------------------

const TABLE_HEAD = [
    { id: 'transaction_ref', label: 'Transaction Ref', alignRight: false },
    { id: 'session_id', label: 'Session ID', alignRight: false },
    { id: 'transaction_type', label: 'Transaction Type', alignRight: false },
    { id: 'amount', label: 'Amount (₦)', alignRight: false },
    { id: 'fee', label: 'Fee (₦)', alignRight: false },
    { id: 'net_amount', label: 'Net Amount (₦)', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'settlement', label: 'Settlement', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'action', label: 'Actions', alignRight: true },
];

const WALLET_TABS = [
    { value: 'all', label: 'All' },
    { value: 'deposits', label: 'Deposits' },
    { value: 'payments', label: 'Payments' }
];

const BalanceCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(4),
    background: 'linear-gradient(135deg, #01b875 0%, #018F5D 100%)', // Brand Green Gradient
    color: theme.palette.common.white,
    borderRadius: 16,
    position: 'relative',
    overflow: 'hidden',
    boxShadow: `0 8px 32px 0 ${alpha('#01b875', 0.24)}`,
    '&:before': {
        content: '""',
        position: 'absolute',
        top: -20,
        right: -20,
        width: 140,
        height: 140,
        background: 'radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%)',
        borderRadius: '50%',
    },
    '&:after': {
        content: '""',
        position: 'absolute',
        bottom: -30,
        left: -30,
        width: 100,
        height: 100,
        background: 'radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%)',
        borderRadius: '50%',
    }
}));

const DetailBox = styled(Box)(({ theme }) => ({
    padding: theme.spacing(2, 3),
    borderRadius: 12,
    backgroundColor: alpha(theme.palette.background.neutral, 0.8),
    backdropFilter: 'blur(10px)',
    border: `1px solid ${theme.palette.divider}`,
    height: '100%',
    transition: theme.transitions.create(['all'], {
        duration: theme.transitions.duration.shorter,
    }),
    '&:hover': {
        boxShadow: theme.customShadows.z12,
        transform: 'translateY(-2px)',
        borderColor: theme.palette.primary.main,
    },
}));

// ----------------------------------------------------------------------

export default function WalletSummary() {
    const theme = useTheme();
    const { enqueueSnackbar } = useSnackbar();
    const { themeStretch } = useSettings();
    const { user } = useAuth();

    const [transactions, setTransactions] = useState([]);
    const [page, setPage] = useState(0);
    const [load, SetLoad] = useState(true);
    const [totalPage, SetTotal] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [isNotFound, SetNotFound] = useState(false);
    const [activeTab, setActiveTab] = useState('all');

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        initialize(page, rowsPerPage, filterName, activeTab);
    }, []);

    const initialize = async (pag, Habukhan = 10, search, tab) => {
        const Habukhan_page = pag + 1;
        SetLoad(true);

        try {
            let allTransactions = [];

            if (tab === 'all' || tab === 'deposits') {
                const depositRes = await axios.get(
                    `/api/system/all/deposit/trans/habukhan/${AccessToken}/secure?page=${Habukhan_page}&limit=${Habukhan}&status=ALL&search=${search}`
                );
                const deposits = (depositRes.data?.deposit_trans?.data || []).map(item => ({
                    ...item,
                    type: 'Deposit',
                    transType: 'deposit'
                }));
                allTransactions = [...allTransactions, ...deposits];
            }

            if (tab === 'all' || tab === 'payments') {
                const transRes = await axios.get(
                    `/api/system/all/history/records/${AccessToken}/secure?page=${Habukhan_page}&limit=${Habukhan}&status=ALL&search=${search}`
                );
                const payments = (transRes.data?.all_summary?.data || []).map(item => {
                    // Map transaction_type to display name
                    let displayType = 'Payment';
                    if (item.transaction_type) {
                        const typeMap = {
                            'transfer': 'Transfer',
                            'withdrawal': 'Withdrawal',
                            'settlement_withdrawal': 'Settlement Withdrawal',
                            'payment': 'Payment',
                            'refund': 'Refund'
                        };
                        displayType = typeMap[item.transaction_type] || item.transaction_type.charAt(0).toUpperCase() + item.transaction_type.slice(1);
                    }
                    return {
                        ...item,
                        type: displayType,
                        transType: 'payment'
                    };
                });
                allTransactions = [...allTransactions, ...payments];
            }

            allTransactions.sort((a, b) => new Date(b.plan_date || b.date) - new Date(a.plan_date || a.date));

            setTransactions(allTransactions);
            SetTotal(allTransactions.length);
            SetLoad(false);
            setPage(pag);
            SetNotFound(allTransactions.length === 0);
        } catch (error) {
            console.error('Error fetching wallet data:', error);
            const errorMessage = typeof error === 'string' ? error : (error.response?.data?.message || 'Error fetching wallet data');
            enqueueSnackbar(errorMessage, { variant: 'error' });
            SetLoad(false);
            setTransactions([]);
            SetNotFound(true);
        }
    };

    const handleChangeRowsPerPage = (event) => {
        setRowsPerPage(parseInt(event.target.value, 10));
        initialize(0, event.target.value, filterName, activeTab);
        setPage(0);
    };

    const handleFilterByName = async (filterName) => {
        setFilterName(filterName);
        setPage(0);
        initialize(page, rowsPerPage, filterName, activeTab);
    };

    const handleTabChange = (event, newValue) => {
        setActiveTab(newValue);
        setPage(0);
        initialize(0, rowsPerPage, filterName, newValue);
    };

    return (
        <Page title="Wallet Account">
            <Container maxWidth={themeStretch ? false : 'lg'}>
                <HeaderBreadcrumbs
                    heading="Wallet Account"
                    links={[
                        { name: 'Overview', href: PATH_DASHBOARD.general.app },
                        { name: 'Wallet Account' },
                    ]}
                />

                {/* Balance Card */}
                <BalanceCardStyle sx={{ mb: 4 }}>
                    <Stack direction="row" justifyContent="space-between" alignItems="center">
                        <Box>
                            <Typography variant="subtitle2" sx={{ opacity: 0.8, fontWeight: 700, mb: 0.5 }}>
                                Available Balance
                            </Typography>
                            <Typography variant="h2" sx={{ fontWeight: 800 }}>
                                ₦{fCurrency(user?.balance || 0)}
                            </Typography>
                        </Box>
                        <Button
                            variant="contained"
                            size="large"
                            startIcon={<Iconify icon="eva:diagonal-arrow-right-up-fill" />}
                            sx={{
                                bgcolor: alpha(theme.palette.common.white, 0.2),
                                color: 'common.white',
                                px: 4,
                                py: 1.5,
                                borderRadius: 1.5,
                                fontWeight: 700,
                                textTransform: 'none',
                                '&:hover': {
                                    bgcolor: alpha(theme.palette.common.white, 0.3),
                                }
                            }}
                        >
                            Withdraw
                        </Button>
                    </Stack>
                </BalanceCardStyle>

                {/* Account Details */}
                <Box sx={{ mb: 5 }}>
                    <Typography variant="subtitle1" sx={{ mb: 2, fontWeight: 700 }}>
                        Wallet Account Details
                    </Typography>
                    <Grid container spacing={3}>
                        <Grid item xs={12} md={6}>
                            <DetailBox sx={{ borderLeft: `4px solid ${theme.palette.primary.main}` }}>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', mb: 0.5, fontWeight: 600 }}>
                                    Account Number (Master Wallet)
                                </Typography>
                                {user?.palmpay_account_number ? (
                                    <Stack direction="row" alignItems="center" spacing={1}>
                                        <Typography variant="h4" sx={{ fontWeight: 800, color: 'text.primary' }}>
                                            {user?.palmpay_account_number}
                                        </Typography>
                                        <CopyToClipboard
                                            text={user?.palmpay_account_number}
                                            onCopy={() => enqueueSnackbar('Account Number Copied!')}
                                        >
                                            <IconButton size="small">
                                                <Iconify icon="eva:copy-fill" sx={{ color: 'text.secondary' }} />
                                            </IconButton>
                                        </CopyToClipboard>
                                    </Stack>
                                ) : (
                                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                                        PalmPay account will be generated when your business is activated by admin
                                    </Typography>
                                )}
                            </DetailBox>
                        </Grid>
                        <Grid item xs={12} md={6}>
                            <DetailBox>
                                <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block', mb: 0.5, fontWeight: 600 }}>
                                    Bank Details
                                </Typography>
                                <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                                    {user?.palmpay_bank_name || 'PalmPay'}
                                </Typography>
                                <Typography variant="caption" sx={{ color: 'text.secondary', fontWeight: 600 }}>
                                    {user?.palmpay_account_name || user?.name || user?.username}
                                </Typography>
                            </DetailBox>
                        </Grid>
                    </Grid>

                    <Box sx={{
                        mt: 2,
                        p: 1.5,
                        bgcolor: alpha(theme.palette.info.main, 0.05),
                        borderRadius: 1,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1
                    }}>
                        <Iconify icon="eva:info-fill" sx={{ color: 'info.main', width: 18 }} />
                        <Typography variant="caption" sx={{ color: 'info.main', fontWeight: 600 }}>
                            Transfer funds to this account from your mobile/Internet banking or via USSD to top up your wallet
                        </Typography>
                    </Box>
                </Box>

                {/* Transaction History - Hidden to avoid confusion, use RA Transactions page instead */}
                {false && (
                    <>
                        <Typography variant="subtitle1" sx={{ mb: 2, fontWeight: 700 }}>
                            Transaction History
                        </Typography>
                        <Card sx={{ borderRadius: 2, boxShadow: theme.customShadows.z8 }}>
                            <Tabs
                                value={activeTab}
                                onChange={handleTabChange}
                                sx={{ px: 2, pt: 1, borderBottom: `1px solid ${theme.palette.divider}` }}
                            >
                                {WALLET_TABS.map((tab) => (
                                    <Tab
                                        disableRipple
                                        key={tab.value}
                                        label={tab.label}
                                        value={tab.value}
                                        sx={{
                                            textTransform: 'none',
                                            fontWeight: 700,
                                            minWidth: 100
                                        }}
                                    />
                                ))}
                            </Tabs>

                            <PlanToolbar filterName={filterName} onFilterName={handleFilterByName} />

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 1400 }}>
                            <Table>
                                <TransHead
                                    order={'desc'}
                                    orderBy={'date'}
                                    headLabel={TABLE_HEAD}
                                    rowCount={transactions.length}
                                />

                                {!load ? (
                                    <TableBody>
                                        {transactions.map((row, index) => {
                                            const {
                                                id,
                                                transaction_ref,
                                                session_id,
                                                transaction_type,
                                                transid,
                                                reference,
                                                type,
                                                amount,
                                                fee,
                                                charges,
                                                net_amount,
                                                oldbal,
                                                newbal,
                                                plan_date,
                                                date,
                                                created_at,
                                                plan_status,
                                                status,
                                                settlement_status,
                                                transType
                                            } = row;

                                            // Use new fields with fallback to legacy
                                            const displayTransactionRef = transaction_ref || transid || reference || '';
                                            const displaySessionId = session_id || transid || reference || '';
                                            const displayFee = fee !== undefined ? fee : (charges || 0);
                                            const displayNetAmount = net_amount !== undefined ? net_amount : (amount - displayFee);
                                            const displayDate = created_at || date || plan_date || '';

                                            const formatDate = (dateStr) => {
                                                if (!dateStr) return '';
                                                const d = new Date(dateStr);
                                                const day = String(d.getDate()).padStart(2, '0');
                                                const month = String(d.getMonth() + 1).padStart(2, '0');
                                                const year = d.getFullYear();
                                                const hours = String(d.getHours()).padStart(2, '0');
                                                const minutes = String(d.getMinutes()).padStart(2, '0');
                                                const seconds = String(d.getSeconds()).padStart(2, '0');
                                                return `${day}/${month}/${year} ${hours}:${minutes}:${seconds} WAT`;
                                            };

                                            // Transaction type labels
                                            const typeLabels = {
                                                'va_deposit': 'VA Deposit',
                                                'api_transfer': 'Transfer',
                                                'company_withdrawal': 'Withdrawal',
                                                'refund': 'Refund'
                                            };
                                            const displayType = typeLabels[transaction_type] || (type || 'Transaction');

                                            // Transaction type colors
                                            const typeColors = {
                                                'va_deposit': 'success',
                                                'api_transfer': 'info',
                                                'company_withdrawal': 'warning',
                                                'refund': 'error'
                                            };
                                            const typeColor = typeColors[transaction_type] || (transType === 'deposit' ? 'success' : 'info');

                                            // Standardize status and color
                                            let statusText = 'pending';
                                            let statusColor = 'warning';

                                            const displayStatus = status || plan_status;
                                            const currentStatus = displayStatus?.toString().toLowerCase();
                                            
                                            if (['1', 'success', 'successful', 'completed'].includes(currentStatus)) {
                                                statusText = 'successful';
                                                statusColor = 'success';
                                            } else if (['2', 'failed', 'fail'].includes(currentStatus)) {
                                                statusText = 'failed';
                                                statusColor = 'error';
                                            } else if (['0', 'processing'].includes(currentStatus)) {
                                                statusText = 'processing';
                                                statusColor = 'info';
                                            } else if (['pending'].includes(currentStatus)) {
                                                statusText = 'pending';
                                                statusColor = 'warning';
                                            }

                                            // Settlement status
                                            let settlementText = 'Unsettled';
                                            let settlementColor = 'warning';
                                            
                                            if (settlement_status === 'settled') {
                                                settlementText = 'Settled';
                                                settlementColor = 'success';
                                            } else if (settlement_status === 'unsettled') {
                                                settlementText = 'Unsettled';
                                                settlementColor = 'warning';
                                            } else if (settlement_status === 'not_applicable') {
                                                settlementText = 'Not Applicable';
                                                settlementColor = 'default';
                                            } else if (settlement_status === 'failed') {
                                                settlementText = 'Failed';
                                                settlementColor = 'error';
                                            }

                                            const handleCopyTransactionRef = () => {
                                                navigator.clipboard.writeText(displayTransactionRef);
                                                enqueueSnackbar('Transaction reference copied', { variant: 'success' });
                                            };

                                            const handleCopySessionId = () => {
                                                navigator.clipboard.writeText(displaySessionId);
                                                enqueueSnackbar('Session ID copied', { variant: 'success' });
                                            };

                                            const handleDownloadReceipt = async () => {
                                                try {
                                                    const response = await axios.post(
                                                        `/api/transactions/${id}/receipt`,
                                                        {},
                                                        { 
                                                            responseType: 'blob',
                                                            headers: { Authorization: `Bearer ${AccessToken}` }
                                                        }
                                                    );
                                                    const url = window.URL.createObjectURL(new Blob([response.data]));
                                                    const link = document.createElement('a');
                                                    link.href = url;
                                                    link.setAttribute('download', `receipt-${displayTransactionRef}-${new Date().toISOString().split('T')[0]}.pdf`);
                                                    document.body.appendChild(link);
                                                    link.click();
                                                    link.remove();
                                                    enqueueSnackbar('Receipt downloaded successfully', { variant: 'success' });
                                                } catch (error) {
                                                    enqueueSnackbar('Failed to download receipt', { variant: 'error' });
                                                }
                                            };

                                            return (
                                                <TableRow hover key={displayTransactionRef + index}>
                                                    <TableCell>
                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                                            <Typography variant="subtitle2" sx={{ fontWeight: 600, fontFamily: 'monospace', fontSize: '0.8rem' }}>
                                                                {displayTransactionRef || '—'}
                                                            </Typography>
                                                            {displayTransactionRef && (
                                                                <IconButton size="small" onClick={handleCopyTransactionRef}>
                                                                    <Iconify icon="eva:copy-outline" width={14} />
                                                                </IconButton>
                                                            )}
                                                        </Box>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                                            <Typography variant="caption" sx={{ fontWeight: 600, fontFamily: 'monospace', fontSize: '0.75rem' }}>
                                                                {displaySessionId || '—'}
                                                            </Typography>
                                                            {displaySessionId && (
                                                                <IconButton size="small" onClick={handleCopySessionId}>
                                                                    <Iconify icon="eva:copy-outline" width={14} />
                                                                </IconButton>
                                                            )}
                                                        </Box>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Label
                                                            variant="soft"
                                                            color={typeColor}
                                                            sx={{
                                                                textTransform: 'capitalize',
                                                                fontWeight: 700,
                                                                px: 1,
                                                                fontSize: '0.7rem',
                                                                borderRadius: 0.75
                                                            }}
                                                        >
                                                            {displayType}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Typography
                                                            variant="subtitle2"
                                                            sx={{ fontWeight: 800, fontSize: '0.9rem' }}
                                                            color={transType === 'deposit' || transaction_type === 'va_deposit' ? 'success.main' : 'error.main'}
                                                        >
                                                            {transType === 'deposit' || transaction_type === 'va_deposit' ? '+' : '-'}₦{fCurrency(amount)}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ color: 'text.secondary', fontWeight: 600, fontSize: '0.85rem' }}>
                                                        ₦{fCurrency(displayFee)}
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ fontWeight: 700, color: 'primary.main', fontSize: '0.9rem' }}>
                                                        ₦{fCurrency(displayNetAmount)}
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Label
                                                            variant="soft"
                                                            color={statusColor}
                                                            sx={{
                                                                textTransform: 'uppercase',
                                                                fontWeight: 800,
                                                                px: 1.2,
                                                                fontSize: '0.7rem',
                                                                borderRadius: 0.75
                                                            }}
                                                        >
                                                            {statusText}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                                                            <Box sx={{
                                                                width: 8,
                                                                height: 8,
                                                                borderRadius: '50%',
                                                                bgcolor: `${settlementColor}.main`
                                                            }} />
                                                            <Typography variant="caption" sx={{ fontWeight: 700, color: 'text.secondary', fontSize: '0.75rem' }}>
                                                                {settlementText}
                                                            </Typography>
                                                        </Box>
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ color: 'text.secondary', fontWeight: 500, fontSize: '0.8rem' }}>
                                                        {formatDate(displayDate) || '—'}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <Box sx={{ display: 'flex', gap: 0.5, justifyContent: 'flex-end' }}>
                                                            <IconButton
                                                                color="primary"
                                                                onClick={() => {
                                                                    // Navigate to transaction details based on type
                                                                    if (transType === 'deposit' || transaction_type === 'va_deposit') {
                                                                        window.location.href = `/dashboard/invoice/${displayTransactionRef}/deposit`;
                                                                    } else {
                                                                        enqueueSnackbar('Transaction details view coming soon', { variant: 'info' });
                                                                    }
                                                                }}
                                                                sx={{
                                                                    '&:hover': {
                                                                        bgcolor: alpha(theme.palette.primary.main, 0.08)
                                                                    }
                                                                }}
                                                            >
                                                                <Iconify icon="eva:eye-fill" />
                                                            </IconButton>
                                                            <IconButton
                                                                color="success"
                                                                onClick={handleDownloadReceipt}
                                                                sx={{
                                                                    '&:hover': {
                                                                        bgcolor: alpha(theme.palette.success.main, 0.08)
                                                                    }
                                                                }}
                                                            >
                                                                <Iconify icon="eva:download-fill" />
                                                            </IconButton>
                                                        </Box>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                ) : (
                                    <TableBody>
                                        <TableRow>
                                            <TableCell align="center" colSpan={10} sx={{ py: 8 }}>
                                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>Loading transactions...</Typography>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                )}

                                {isNotFound && (
                                    <TableBody>
                                        <TableRow>
                                            <TableCell align="center" colSpan={10} sx={{ py: 8 }}>
                                                <SearchNotFound searchQuery={filterName} />
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                )}
                            </Table>
                        </TableContainer>
                    </Scrollbar>

                    <TablePagination
                        rowsPerPageOptions={[5, 10, 25, 100]}
                        component="div"
                        count={totalPage}
                        rowsPerPage={rowsPerPage}
                        page={page}
                        onPageChange={(e, page) => initialize(page, rowsPerPage, filterName, activeTab)}
                        onRowsPerPageChange={handleChangeRowsPerPage}
                    />
                        </Card>
                    </>
                )}
            </Container>
        </Page>
    );
}
