/* eslint-disable react-hooks/exhaustive-deps */
import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { sentenceCase } from 'change-case';

// @mui
import { useTheme } from '@mui/material/styles';
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

// routes
import { PATH_DASHBOARD } from '../../routes/paths';
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

const TABLE_HEAD = [
    { id: 'transaction_ref', label: 'Transaction Ref', alignRight: false },
    { id: 'session_id', label: 'Session ID', alignRight: false },
    { id: 'type', label: 'Type', alignRight: false },
    { id: 'customer', label: 'Customer', alignRight: false },
    { id: 'amount', label: 'Amount (₦)', alignRight: false },
    { id: 'fee', label: 'Fee (₦)', alignRight: false },
    { id: 'net_amount', label: 'Net Amount (₦)', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'settlement', label: 'Settlement', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'action', label: 'Actions', alignRight: true },
];

const HeaderCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(3),
    background: 'linear-gradient(135deg, #01b875 0%, #018F5D 100%)',
    borderRadius: 16,
    border: 'none',
    boxShadow: `0 8px 32px 0 ${alpha('#01b875', 0.2)}`,
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: theme.spacing(4),
    position: 'relative',
    overflow: 'hidden',
    color: 'white',
}));

// ----------------------------------------------------------------------

export default function RATransactions() {
    const theme = useTheme();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const { themeStretch } = useSettings();

    const [transactions, setTransactions] = useState([]);
    const [page, setPage] = useState(0);
    const [load, SetLoad] = useState(true);
    const [totalPage, SetTotal] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [isNotFound, SetNotFound] = useState(false);
    const [exportLoading, setExportLoading] = useState(false);

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        initialize(page, rowsPerPage, filterName);
    }, []);

    const initialize = async (pag, limit = 10, search = '') => {
        const Habukhan_page = pag + 1;
        SetLoad(true);

        try {
            const response = await axios.get(
                `/api/system/all/ra-history/records/${AccessToken}/secure?page=${Habukhan_page}&limit=${limit}&status=ALL&search=${search}`
            );

            const allTransactions = response.data?.ra_trans?.data || [];

            setTransactions(allTransactions);
            SetTotal(response.data?.ra_trans?.total || 0);
            SetLoad(false);
            setPage(pag);
            SetNotFound(allTransactions.length === 0);
        } catch (error) {
            console.error('Error fetching RA transactions:', error);
            const errorMessage = typeof error === 'string' ? error : (error.response?.data?.message || 'Error fetching transactions');
            enqueueSnackbar(errorMessage, { variant: 'error' });
            SetLoad(false);
            setTransactions([]);
            SetNotFound(true);
        }
    };

    const handleChangeRowsPerPage = (event) => {
        setRowsPerPage(parseInt(event.target.value, 10));
        initialize(0, event.target.value, filterName);
        setPage(0);
    };

    const handleFilterByName = async (val) => {
        setFilterName(val);
        setPage(0);
        initialize(0, rowsPerPage, val);
    };

    const handleViewTransaction = (transaction) => {
        // Navigate to transaction details page
        navigate(`/dashboard/ra-transactions/${transaction.id}`);
    };

    const handleExport = async () => {
        setExportLoading(true);
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
        } finally {
            setExportLoading(false);
        }
    };

    return (
        <Page title="Reserved Account Transaction">
            <Container maxWidth={themeStretch ? false : 'lg'}>
                <HeaderCardStyle>
                    <Box>
                        <Typography variant="h4" sx={{ fontWeight: 800, color: 'white', mb: 0.5 }}>
                            Reserved Account Transaction
                        </Typography>
                        <Typography variant="body2" sx={{ color: 'rgba(255, 255, 255, 0.8)' }}>
                            Overview &nbsp;&bull;&nbsp; Reserved Account Transaction
                        </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', gap: 1 }}>
                        <Button
                            variant="contained"
                            startIcon={<Iconify icon="eva:download-fill" />}
                            onClick={handleExport}
                            disabled={exportLoading}
                            sx={{
                                borderRadius: 1.5,
                                bgcolor: 'white',
                                color: 'primary.main',
                                '&:hover': { bgcolor: 'grey.100' }
                            }}
                        >
                            {exportLoading ? 'Exporting...' : 'Export'}
                        </Button>
                    </Box>
                </HeaderCardStyle>

                <Card sx={{ borderRadius: 2, boxShadow: theme.customShadows.z8 }}>
                    <Box sx={{ p: 2, borderBottom: `1px solid ${theme.palette.divider}` }}>
                        <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                            Reserved Account Transaction
                        </Typography>
                    </Box>

                    <PlanToolbar filterName={filterName} onFilterName={handleFilterByName} />

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 1600 }}>
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
                                                customer_name,
                                                amount,
                                                status,
                                                fee,
                                                charges,
                                                net_amount,
                                                created_at,
                                                date,
                                                settlement_status
                                            } = row;

                                            // Use new fields with fallback to legacy
                                            const displayTransactionRef = transaction_ref || transid || '';
                                            const displaySessionId = session_id || '';
                                            const displayFee = fee !== undefined ? fee : (charges || 0);
                                            const displayNetAmount = net_amount !== undefined ? net_amount : (amount - displayFee);
                                            const displayDate = date || created_at || '';
                                            
                                            const formatDate = (dateStr) => {
                                                if (!dateStr) return '';
                                                const d = new Date(dateStr);
                                                if (isNaN(d.getTime())) return '';
                                                
                                                // Convert to Nigerian time (WAT - UTC+1)
                                                const options = {
                                                    timeZone: 'Africa/Lagos',
                                                    year: 'numeric',
                                                    month: '2-digit',
                                                    day: '2-digit',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                    second: '2-digit',
                                                    hour12: false
                                                };
                                                
                                                const formatted = d.toLocaleString('en-GB', options);
                                                return `${formatted} WAT`;
                                            };

                                            // Transaction type labels
                                            const typeLabels = {
                                                'va_deposit': 'VA Deposit',
                                                'api_transfer': 'Transfer',
                                                'company_withdrawal': 'Withdrawal',
                                                'refund': 'Refund'
                                            };
                                            const displayType = typeLabels[transaction_type] || sentenceCase(transaction_type || 'transaction');

                                            // Standardize status and color
                                            let statusText = 'pending';
                                            let statusColor = 'warning';

                                            const successStatus = ['successful', 'success', 1, '1', 'Completed', 'completed'];
                                            const failedStatus = ['failed', 2, '2', 'Failed'];
                                            const processingStatus = ['processing', 0, '0', 'Processing'];
                                            const pendingStatus = ['pending', 'Pending'];

                                            if (successStatus.includes(status)) {
                                                statusText = 'successful';
                                                statusColor = 'success';
                                            } else if (failedStatus.includes(status)) {
                                                statusText = 'failed';
                                                statusColor = 'error';
                                            } else if (processingStatus.includes(status)) {
                                                statusText = 'processing';
                                                statusColor = 'info';
                                            } else if (pendingStatus.includes(status)) {
                                                statusText = 'pending';
                                                statusColor = 'warning';
                                            }

                                            // Settlement status - use actual settlement_status from API
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
                                            } else {
                                                // Fallback for legacy data
                                                settlementText = 'Unsettled';
                                                settlementColor = 'warning';
                                            }

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
                                                    link.setAttribute('download', `receipt-${transid}-${new Date().toISOString().split('T')[0]}.pdf`);
                                                    document.body.appendChild(link);
                                                    link.click();
                                                    link.remove();
                                                    enqueueSnackbar('Receipt downloaded successfully', { variant: 'success' });
                                                } catch (error) {
                                                    enqueueSnackbar('Failed to download receipt', { variant: 'error' });
                                                }
                                            };

                                            const handleCopyTransactionRef = () => {
                                                navigator.clipboard.writeText(displayTransactionRef);
                                                enqueueSnackbar('Transaction reference copied', { variant: 'success' });
                                            };

                                            const handleCopySessionId = () => {
                                                navigator.clipboard.writeText(displaySessionId);
                                                enqueueSnackbar('Session ID copied', { variant: 'success' });
                                            };

                                            return (
                                                <TableRow hover key={displayTransactionRef || index}>
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
                                                        <Label
                                                            variant="soft"
                                                            color="info"
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
                                                    <TableCell sx={{ color: 'text.primary', fontWeight: 600 }}>
                                                        {customer_name || '—'}
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 800, color: 'success.main', fontSize: '0.9rem' }}>
                                                        ₦{fCurrency(amount)}
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 600, fontSize: '0.85rem' }}>
                                                        ₦{fCurrency(displayFee)}
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 700, color: 'primary.main', fontSize: '0.9rem' }}>
                                                        ₦{fCurrency(displayNetAmount)}
                                                    </TableCell>
                                                    <TableCell>
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
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 500, fontSize: '0.8rem' }}>
                                                        {formatDate(displayDate) || '—'}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <Box sx={{ display: 'flex', gap: 0.5, justifyContent: 'flex-end' }}>
                                                            <IconButton
                                                                color="primary"
                                                                onClick={() => handleViewTransaction(row)}
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
                                            <TableCell align="center" colSpan={11} sx={{ py: 8 }}>
                                                <Typography variant="body2" sx={{ color: 'text.secondary' }}>Loading transactions...</Typography>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                )}

                                {isNotFound && (
                                    <TableBody>
                                        <TableRow>
                                            <TableCell align="center" colSpan={11} sx={{ py: 8 }}>
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
                        onPageChange={(e, p) => initialize(p, rowsPerPage, filterName)}
                        onRowsPerPageChange={handleChangeRowsPerPage}
                    />
                </Card>
            </Container>
        </Page>
    );
}
