/* eslint-disable react-hooks/exhaustive-deps */
import { useState, useEffect } from 'react';
import { useSnackbar } from 'notistack';
// @mui
import { useTheme, styled } from '@mui/material/styles';
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
    Stack,
    Box,
    Grid,
    TextField,
    Button,
    CircularProgress,
    IconButton
} from '@mui/material';

// hooks
import useSettings from '../../hooks/useSettings';
// components
import Page from '../../components/Page';
import Label from '../../components/Label';
import Scrollbar from '../../components/Scrollbar';
import SearchNotFound from '../../components/SearchNotFound';
import Iconify from '../../components/Iconify';
// format number
import { fCurrency } from '../../utils/formatNumber';
// sections
import { TransHead, PlanToolbar } from '../../sections/admin/user/list';
// axios
import axios from '../../utils/axios';

// ----------------------------------------------------------------------

const HeaderCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(4),
    background: 'linear-gradient(135deg, #1E1B4B 0%, #312E81 100%)',
    borderRadius: 24,
    border: 'none',
    boxShadow: '0 20px 40px 0 rgba(30, 27, 75, 0.2)',
    marginBottom: theme.spacing(4),
    color: '#fff',
}));

const MetricCard = styled(Card)(({ theme }) => ({
    padding: theme.spacing(3),
    borderRadius: 16,
    boxShadow: theme.customShadows.z12,
    height: '100%'
}));

const TABLE_HEAD = [
    { id: 'transaction_ref', label: 'Transaction Ref', alignRight: false },
    { id: 'session_id', label: 'Session ID', alignRight: false },
    { id: 'transaction_type', label: 'Transaction Type', alignRight: false },
    { id: 'company', label: 'Company', alignRight: false },
    { id: 'customer', label: 'Customer', alignRight: false },
    { id: 'amount', label: 'Amount (₦)', alignRight: false },
    { id: 'fee', label: 'Fee (₦)', alignRight: false },
    { id: 'net_amount', label: 'Net Amount (₦)', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'settlement', label: 'Settlement', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'actions', label: 'Actions', alignRight: true },
];

// ----------------------------------------------------------------------

export default function AdminStatement() {
    const theme = useTheme();
    const { themeStretch } = useSettings();
    const { enqueueSnackbar } = useSnackbar();

    const [transactions, setTransactions] = useState([]);
    const [summary, setSummary] = useState(null);
    const [page, setPage] = useState(0);
    const [load, setLoad] = useState(true);
    const [totalRecords, setTotalRecords] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0].slice(0, 8) + '01');
    const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        initialize(page, 50, filterName, startDate, endDate);
    }, []);

    const initialize = async (pag, limit = 50, search, start, end) => {
        const api_page = pag + 1;
        setLoad(true);
        try {
            const response = await axios.get(
                `/api/secure/trans/statement/${AccessToken}/secure?page=${api_page}&limit=${limit}&search=${search}&start_date=${start}&end_date=${end}`
            );
            setTransactions(response.data?.statement?.data || []);
            setTotalRecords(response.data?.statement?.total || 0);
            setSummary(response.data?.summary || null);
            setLoad(false);
            setPage(pag);
        } catch (error) {
            console.error('Error fetching statement:', error);
            const errorMessage = typeof error === 'string' ? error : (error.response?.data?.message || 'Error fetching statement');
            enqueueSnackbar(errorMessage, { variant: 'error' });
            setLoad(false);
            setTransactions([]);
        }
    };

    const handleFilterByName = (name) => {
        setFilterName(name);
        setPage(0);
        initialize(0, 50, name, startDate, endDate);
    };

    const handleDateFilter = () => {
        setPage(0);
        initialize(0, 50, filterName, startDate, endDate);
    };

    const handleExport = async () => {
        try {
            const response = await axios.get(
                `/api/secure/trans/statement/${AccessToken}/secure/export?search=${filterName}&start_date=${startDate}&end_date=${endDate}`,
                { responseType: 'blob' }
            );
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `statement_${startDate}_to_${endDate}.csv`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error(error);
            enqueueSnackbar('Failed to export statement', { variant: 'error' });
        }
    };

    return (
        <Page title="Transaction Statement">
            <Container maxWidth={themeStretch ? false : 'xl'}>
                <HeaderCardStyle>
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                        <Box>
                            <Typography variant="h3" sx={{ fontWeight: 900, mb: 1 }}>
                                Transaction Statement
                            </Typography>
                            <Typography variant="subtitle2" sx={{ opacity: 0.8, fontWeight: 600 }}>
                                Financial statement and reconciliation records
                            </Typography>
                        </Box>
                        <Button
                            variant="contained"
                            color="warning"
                            startIcon={<Iconify icon="eva:cloud-download-fill" />}
                            onClick={handleExport}
                            sx={{ boxShadow: 'none', fontWeight: 700 }}
                        >
                            Export CSV
                        </Button>
                    </Box>
                </HeaderCardStyle>

                {summary && (
                    <Grid container spacing={3} sx={{ mb: 4 }}>
                        <Grid item xs={12} md={3}>
                            <MetricCard>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                                    Total Transactions
                                </Typography>
                                <Typography variant="h3" sx={{ fontWeight: 800 }}>
                                    {summary.total_count || 0}
                                </Typography>
                            </MetricCard>
                        </Grid>
                        <Grid item xs={12} md={3}>
                            <MetricCard>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                                    Total Inflow
                                </Typography>
                                <Typography variant="h4" sx={{ fontWeight: 800, color: 'success.main' }}>
                                    ₦{fCurrency(summary.total_credit || 0)}
                                </Typography>
                            </MetricCard>
                        </Grid>
                        <Grid item xs={12} md={3}>
                            <MetricCard>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                                    Total Outflow
                                </Typography>
                                <Typography variant="h4" sx={{ fontWeight: 800, color: 'error.main' }}>
                                    ₦{fCurrency(summary.total_debit || 0)}
                                </Typography>
                            </MetricCard>
                        </Grid>
                        <Grid item xs={12} md={3}>
                            <MetricCard>
                                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                                    Total Fees
                                </Typography>
                                <Typography variant="h4" sx={{ fontWeight: 800 }}>
                                    ₦{fCurrency(summary.total_charges || 0)}
                                </Typography>
                            </MetricCard>
                        </Grid>
                    </Grid>
                )}

                <Card sx={{ borderRadius: 2, boxShadow: 'none', border: `1px solid ${theme.palette.divider}` }}>
                    <Box sx={{ p: 2, borderBottom: `1px solid ${theme.palette.divider}` }}>
                        <Stack direction="row" spacing={2} alignItems="center">
                            <TextField
                                label="Start Date"
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                InputLabelProps={{ shrink: true }}
                                size="small"
                            />
                            <TextField
                                label="End Date"
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                InputLabelProps={{ shrink: true }}
                                size="small"
                            />
                            <Button
                                variant="contained"
                                onClick={handleDateFilter}
                                startIcon={<Iconify icon="eva:funnel-fill" />}
                            >
                                Filter
                            </Button>
                        </Stack>
                    </Box>

                    <Box sx={{ p: 2 }}>
                        <PlanToolbar
                            filterName={filterName}
                            onFilterName={handleFilterByName}
                            placeholder="Search by reference, customer, or account..."
                        />
                    </Box>

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 1800 }}>
                            <Table>
                                <TransHead headLabel={TABLE_HEAD} rowCount={transactions.length} />
                                <TableBody>
                                    {!load ? (
                                        transactions.map((row, index) => {
                                            // Use new fields with fallback to legacy
                                            const displayTransactionRef = row.transaction_ref || row.reference || '';
                                            const displaySessionId = row.session_id || '';
                                            const displayFee = row.fee !== undefined ? row.fee : (row.charges || 0);
                                            const displayNetAmount = row.net_amount !== undefined ? row.net_amount : (row.amount - displayFee);
                                            
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

                                            // Transaction type labels - ALL 7 types for admin
                                            const typeLabels = {
                                                'va_deposit': 'VA Deposit',
                                                'api_transfer': 'Transfer',
                                                'company_withdrawal': 'Withdrawal',
                                                'refund': 'Refund',
                                                'fee_charge': 'Fee Charge',
                                                'kyc_charge': 'KYC Charge',
                                                'manual_adjustment': 'Manual Adjustment'
                                            };
                                            const displayType = typeLabels[row.transaction_type] || (row.type || 'Transaction');

                                            // Transaction type colors
                                            const typeColors = {
                                                'va_deposit': 'success',
                                                'api_transfer': 'info',
                                                'company_withdrawal': 'warning',
                                                'refund': 'error',
                                                'fee_charge': 'default',
                                                'kyc_charge': 'secondary',
                                                'manual_adjustment': 'primary'
                                            };
                                            const typeColor = typeColors[row.transaction_type] || (row.type === 'credit' ? 'success' : 'warning');

                                            // Settlement status
                                            let settlementText = 'Unsettled';
                                            let settlementColor = 'warning';
                                            
                                            if (row.settlement_status === 'settled') {
                                                settlementText = 'Settled';
                                                settlementColor = 'success';
                                            } else if (row.settlement_status === 'unsettled') {
                                                settlementText = 'Unsettled';
                                                settlementColor = 'warning';
                                            } else if (row.settlement_status === 'not_applicable') {
                                                settlementText = 'Not Applicable';
                                                settlementColor = 'default';
                                            } else if (row.settlement_status === 'failed') {
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
                                                        `/api/admin/transactions/${row.id}/receipt`,
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
                                                <TableRow hover key={row.id || index}>
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
                                                    <TableCell>
                                                        <Label color={typeColor} variant="soft" sx={{ fontSize: '0.7rem', fontWeight: 700 }}>
                                                            {displayType}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                            {row.company_name || '—'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Box>
                                                            <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                                {row.customer_name || '—'}
                                                            </Typography>
                                                            <Typography variant="caption" color="text.secondary">
                                                                {row.customer_account_number || ''}
                                                            </Typography>
                                                        </Box>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="subtitle2" sx={{ fontWeight: 800, color: 'success.main' }}>
                                                            ₦{fCurrency(row.amount)}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="body2" sx={{ fontWeight: 600, color: 'text.secondary' }}>
                                                            ₦{fCurrency(displayFee)}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="subtitle2" sx={{ fontWeight: 700, color: 'primary.main' }}>
                                                            ₦{fCurrency(displayNetAmount)}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Label
                                                            variant="ghost"
                                                            color={row.status === 'success' || row.status === 'successful' ? 'success' : row.status === 'failed' ? 'error' : 'warning'}
                                                            sx={{ fontSize: '0.7rem', fontWeight: 800 }}
                                                        >
                                                            {row.status}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell>
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
                                                    <TableCell sx={{ whiteSpace: 'nowrap', fontSize: '0.8rem' }}>
                                                        {formatDate(row.created_at) || '—'}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <IconButton
                                                            color="success"
                                                            onClick={handleDownloadReceipt}
                                                            size="small"
                                                        >
                                                            <Iconify icon="eva:download-fill" />
                                                        </IconButton>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={12} align="center" sx={{ py: 10 }}>
                                                <CircularProgress />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {!load && transactions.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={12} align="center" sx={{ py: 10 }}>
                                                <SearchNotFound searchQuery={filterName} />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </Scrollbar>

                    <TablePagination
                        component="div"
                        count={totalRecords}
                        rowsPerPage={50}
                        page={page}
                        rowsPerPageOptions={[]}
                        onPageChange={(e, pag) => initialize(pag, 50, filterName, startDate, endDate)}
                        sx={{ borderTop: `1px solid ${theme.palette.divider}` }}
                    />
                </Card>
            </Container>
        </Page>
    );
}
