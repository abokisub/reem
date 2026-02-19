/* eslint-disable react-hooks/exhaustive-deps */
import { useState, useEffect } from 'react';
import { sentenceCase, capitalCase } from 'change-case';
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
    { id: 'reference', label: 'Transaction Ref', alignRight: false },
    { id: 'amount', label: 'Amount', alignRight: false },
    { id: 'type', label: 'Type', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'oldbal', label: 'Old Balance', alignRight: false },
    { id: 'newbal', label: 'New Balance', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'action', label: 'Action', alignRight: false },
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
    const { user, setting } = useAuth();

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
                const payments = (transRes.data?.all_summary?.data || []).map(item => ({
                    ...item,
                    type: 'Payment',
                    transType: 'payment'
                }));
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

                {/* Transactions Table */}
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
                        <TableContainer sx={{ minWidth: 800 }}>
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
                                                transid,
                                                reference,
                                                type,
                                                amount,
                                                oldbal,
                                                newbal,
                                                plan_date,
                                                date,
                                                plan_status,
                                                status,
                                                transType
                                            } = row;

                                            // Standardize status and color
                                            let statusText = 'pending';
                                            let statusColor = 'warning';

                                            const displayStatus = transType === 'deposit' ? (plan_status || status) : status;
                                            const displayDate = transType === 'deposit' ? (plan_date || date) : date;

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
                                            }

                                            return (
                                                <TableRow hover key={transid || reference || index}>
                                                    <TableCell>
                                                        <Typography variant="subtitle2" sx={{ fontWeight: 700, fontFamily: 'monospace' }}>
                                                            {transid || reference || 'N/A'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Typography
                                                            variant="subtitle2"
                                                            sx={{ fontWeight: 800 }}
                                                            color={transType === 'deposit' ? 'success.main' : 'error.main'}
                                                        >
                                                            {transType === 'deposit' ? '+' : '-'}₦{fCurrency(amount)}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Label
                                                            variant="soft"
                                                            color={transType === 'deposit' ? 'success' : 'info'}
                                                            sx={{
                                                                textTransform: 'uppercase',
                                                                fontWeight: 800,
                                                                px: 1.2,
                                                                fontSize: '0.75rem',
                                                                borderRadius: 0.75
                                                            }}
                                                        >
                                                            {type}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell align="left">
                                                        <Label
                                                            variant="soft"
                                                            color={statusColor}
                                                            sx={{
                                                                textTransform: 'uppercase',
                                                                fontWeight: 800,
                                                                px: 1.2,
                                                                fontSize: '0.75rem',
                                                                borderRadius: 0.75
                                                            }}
                                                        >
                                                            {statusText}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ color: 'text.secondary', fontWeight: 600 }}>
                                                        {oldbal && oldbal !== '0.00' ? `₦${fCurrency(oldbal)}` : '-'}
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ color: 'text.primary', fontWeight: 700 }}>
                                                        {newbal && newbal !== '0.00' ? `₦${fCurrency(newbal)}` : '-'}
                                                    </TableCell>
                                                    <TableCell align="left" sx={{ color: 'text.secondary', fontWeight: 600 }}>{displayDate}</TableCell>
                                                    <TableCell align="right">
                                                        <IconButton
                                                            color="primary"
                                                            onClick={() => {
                                                                // Navigate to transaction details based on type
                                                                if (transType === 'deposit') {
                                                                    window.location.href = `/dashboard/invoice/${transid}/deposit`;
                                                                } else {
                                                                    // For other transactions, show alert for now
                                                                    enqueueSnackbar('Transaction details view coming soon', { variant: 'info' });
                                                                }
                                                            }}
                                                        >
                                                            <Iconify icon="eva:eye-fill" />
                                                        </IconButton>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                ) : (
                                    <TableBody>
                                        <TableRow>
                                            <TableCell align="center" colSpan={8} sx={{ py: 8 }}>
                                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>Loading transactions...</Typography>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                )}

                                {isNotFound && (
                                    <TableBody>
                                        <TableRow>
                                            <TableCell align="center" colSpan={8} sx={{ py: 8 }}>
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
            </Container>
        </Page>
    );
}
