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
    IconButton,
    alpha,
    Stack,
    Button,
    Menu,
    MenuItem,
    ListItemIcon,
    Divider,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    FormControl,
    InputLabel,
    Select
} from '@mui/material';
import { useSnackbar } from 'notistack';

// components
import Page from '../../components/Page';
import Label from '../../components/Label';
import Scrollbar from '../../components/Scrollbar';
import SearchNotFound from '../../components/SearchNotFound';
import Iconify from '../../components/Iconify';
import LogoLoader from '../../components/LogoLoader';
// format number
import { fCurrency } from '../../utils/formatNumber';
// utils
import { fDateTime } from '../../utils/formatTime';
import { exportPDF, exportCSV } from '../../utils/exportTransactions';
// sections
import { TransHead } from '../../sections/admin/user/list';
import RATransactionToolbar from '../../sections/@dashboard/general/RATransactionToolbar';
import RATransactionDetailModal from '../../sections/@dashboard/general/RATransactionDetailModal';
// hooks
import useAuth from '../../hooks/useAuth';
// axios
import axios from '../../utils/axios';

// ----------------------------------------------------------------------

const TABLE_HEAD = [
    { id: 'date', label: 'Date', alignRight: false },
    { id: 'transaction_ref', label: 'ID/Ref', alignRight: false },
    { id: 'customer_name', label: 'Payer', alignRight: false },
    { id: 'amount', label: 'Gross', alignRight: false },
    { id: 'fee', label: 'Fees', alignRight: false },
    { id: 'net_amount', label: 'Net', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'settlement_status', label: 'Settlement', alignRight: false },
    { id: 'settlement_time', label: 'Settled Date', alignRight: false },
    { id: 'action', label: 'Actions', alignRight: true },
];

// ----------------------------------------------------------------------

export default function RATransactions() {
    const theme = useTheme();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const { user } = useAuth();

    const [transactions, SetTransactions] = useState([]);
    const [load, SetLoading] = useState(true);
    const [page, setPage] = useState(0);
    const [totalPage, SetTotal] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(25);
    const [isNotFound, SetNotFound] = useState(false);

    // Modal States
    const [openDetail, setOpenDetail] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);

    // Export States
    const [exportDialogOpen, setExportDialogOpen] = useState(false);
    const [exportFormat, setExportFormat] = useState('pdf');
    const [exportStatus, setExportStatus] = useState('ALL');
    const [exporting, setExporting] = useState(false);

    // Filter States
    const [filterName, setFilterName] = useState('');
    const [filterStatus, setFilterStatus] = useState('ALL');
    const [filterType, setFilterType] = useState('ALL');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        fetchTransactions();
    }, [page, rowsPerPage, filterName, filterStatus, filterType, startDate, endDate]);

    const fetchTransactions = async () => {
        SetLoading(true);
        try {
            const response = await axios.get(
                `/api/system/all/ra-history/records/${AccessToken}/secure`,
                {
                    params: {
                        page: page + 1,
                        limit: rowsPerPage,
                        search: filterName,
                        status: filterStatus,
                        category: filterType,
                        start_date: startDate,
                        end_date: endDate
                    }
                }
            );

            const data = response.data?.ra_trans?.data || [];
            SetTransactions(data);
            SetTotal(response.data?.ra_trans?.total || 0);
            SetNotFound(data.length === 0);
        } catch (error) {
            console.error('Error fetching transactions:', error);
            enqueueSnackbar('Error loading transactions', { variant: 'error' });
        } finally {
            SetLoading(false);
        }
    };

    const handleChangeRowsPerPage = (event) => {
        setRowsPerPage(parseInt(event.target.value, 10));
        setPage(0);
    };

    const handleFilterName = (value) => {
        setFilterName(value);
        setPage(0);
    };

    const handleFilterStatus = (value) => {
        setFilterStatus(value);
        setPage(0);
    };

    const handleFilterType = (value) => {
        setFilterType(value);
        setPage(0);
    };

    const handleResetFilters = () => {
        setFilterName('');
        setFilterStatus('ALL');
        setFilterType('ALL');
        setStartDate('');
        setEndDate('');
    };

    const handleViewTransaction = (transaction) => {
        setSelectedTransaction(transaction);
        setOpenDetail(true);
    };

    const handleCloseDetail = () => {
        setOpenDetail(false);
        setSelectedTransaction(null);
    };

    const handleExport = async () => {
        setExporting(true);
        try {
            // Fetch ALL pages matching current filters but with export status override
            const response = await axios.get(
                `/api/system/all/ra-history/records/${AccessToken}/secure`,
                {
                    params: {
                        page: 1,
                        limit: 5000,
                        search: filterName,
                        status: exportStatus,
                        category: filterType,
                        start_date: startDate,
                        end_date: endDate
                    }
                }
            );

            const rows = response.data?.ra_trans?.data || [];

            // Read company info from useAuth hook
            const company = user?.company || {};

            const filters = { status: exportStatus, startDate, endDate };
            const filename = `ra_transactions_${new Date().toISOString().split('T')[0]}`;

            if (exportFormat === 'pdf') {
                exportPDF(rows, company, filters, filename);
            } else {
                exportCSV(rows, filename);
            }

            setExportDialogOpen(false);
        } catch (err) {
            console.error('Export error:', err);
            enqueueSnackbar('Export failed. Please try again.', { variant: 'error' });
        } finally {
            setExporting(false);
        }
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
            d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    };

    return (
        <Page title="RA Transactions">
            <Container maxWidth={false}>
                <Stack direction="row" alignItems="center" justifyContent="space-between" mb={5}>
                    <Typography variant="h4" gutterBottom sx={{ fontWeight: 800 }}>
                        RA Transactions
                    </Typography>
                    <Button
                        variant="contained"
                        startIcon={<Iconify icon="eva:download-fill" />}
                        onClick={() => setExportDialogOpen(true)}
                        sx={{ borderRadius: 2 }}
                    >
                        Export
                    </Button>
                </Stack>

                <Card sx={{ borderRadius: 2, boxShadow: theme.customShadows.z8 }}>
                    <RATransactionToolbar
                        filterName={filterName}
                        onFilterName={handleFilterName}
                        filterStatus={filterStatus}
                        onFilterStatus={handleFilterStatus}
                        filterType={filterType}
                        onFilterType={handleFilterType}
                        startDate={startDate}
                        onStartDate={setStartDate}
                        endDate={endDate}
                        onEndDate={setEndDate}
                        onResetFilters={handleResetFilters}
                    />

                    <Scrollbar>
                        <TableContainer sx={{ minWidth: 800, position: 'relative' }}>
                            <Table>
                                <TransHead
                                    order="desc"
                                    orderBy="date"
                                    headLabel={TABLE_HEAD}
                                    rowCount={transactions.length}
                                />
                                <TableBody>
                                    {!load ? (
                                        transactions.map((row, index) => {
                                            const {
                                                id,
                                                transaction_ref,
                                                status,
                                                amount,
                                                fee,
                                                net_amount,
                                                created_at,
                                                settlement_status,
                                                settlement_time,
                                                customer_name,
                                                reference
                                            } = row;

                                            // Status Logic
                                            let statusColor = 'warning';
                                            if (['success', 'successful', '1'].includes(status?.toLowerCase())) statusColor = 'success';
                                            if (['failed', '2'].includes(status?.toLowerCase())) statusColor = 'error';

                                            let settlementLabel = 'Unsettled';
                                            let settlementColor = 'warning';
                                            if (settlement_status === 'settled') {
                                                settlementColor = 'success';
                                                settlementLabel = 'Settled';
                                            } else if (settlement_status === 'failed') {
                                                settlementColor = 'error';
                                                settlementLabel = 'Failed';
                                            } else if (settlement_status === 'not_applicable') {
                                                settlementColor = 'default';
                                                settlementLabel = 'N/A';
                                            }

                                            return (
                                                <TableRow hover key={id || index} sx={{ height: 64 }}>
                                                    <TableCell sx={{ whiteSpace: 'nowrap', color: 'text.secondary', fontSize: '0.8rem' }}>
                                                        {formatDate(created_at)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Typography variant="subtitle2" sx={{ fontWeight: 700, fontFamily: 'monospace' }}>
                                                            {transaction_ref || reference || '—'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 600 }}>
                                                        {customer_name || '—'}
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 800, color: 'success.main' }}>
                                                        ₦{fCurrency(amount)}
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 600 }}>
                                                        ₦{fCurrency(fee || 0)}
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 800, color: 'primary.main' }}>
                                                        ₦{fCurrency(net_amount || (amount - (fee || 0)))}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Label
                                                            variant="soft"
                                                            color={statusColor}
                                                            sx={{ textTransform: 'uppercase', fontWeight: 800, fontSize: '0.65rem' }}
                                                        >
                                                            {status || 'pending'}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Stack direction="row" alignItems="center" spacing={1}>
                                                            <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: `${settlementColor}.main` }} />
                                                            <Typography variant="caption" sx={{ fontWeight: 700, color: 'text.secondary' }}>
                                                                {settlementLabel}
                                                            </Typography>
                                                        </Stack>
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.secondary', fontSize: '0.75rem' }}>
                                                        {settlement_time ? fDateTime(settlement_time) : '—'}
                                                    </TableCell>
                                                    <TableCell align="right">
                                                        <IconButton
                                                            color="primary"
                                                            onClick={() => handleViewTransaction(row)}
                                                            sx={{ '&:hover': { bgcolor: alpha(theme.palette.primary.main, 0.08) } }}
                                                        >
                                                            <Iconify icon="eva:eye-fill" />
                                                        </IconButton>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell align="center" colSpan={12} sx={{ py: 10 }}>
                                                <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                                                    <LogoLoader size={80} sx={{ mb: 2 }} />
                                                    <Typography variant="body2" color="text.secondary">Loading...</Typography>
                                                </Box>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {isNotFound && !load && (
                                        <TableRow>
                                            <TableCell align="center" colSpan={12} sx={{ py: 10 }}>
                                                <SearchNotFound searchQuery={filterName} />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </TableContainer>
                    </Scrollbar>

                    <TablePagination
                        rowsPerPageOptions={[5, 10, 25, 50, 100]}
                        component="div"
                        count={totalPage}
                        rowsPerPage={rowsPerPage}
                        page={page}
                        onPageChange={(e, p) => setPage(p)}
                        onRowsPerPageChange={handleChangeRowsPerPage}
                    />
                </Card>

                {/* View Modal */}
                <RATransactionDetailModal
                    transaction={selectedTransaction}
                    open={openDetail}
                    onClose={handleCloseDetail}
                />

                {/* Export Dialog */}
                <Dialog
                    open={exportDialogOpen}
                    onClose={() => !exporting && setExportDialogOpen(false)}
                    fullWidth
                    maxWidth="xs"
                >
                    <DialogTitle sx={{ pb: 2 }}>Export Transactions</DialogTitle>
                    <DialogContent dividers>
                        <Stack spacing={3} sx={{ mt: 1 }}>
                            <FormControl fullWidth size="small">
                                <InputLabel>Export Format</InputLabel>
                                <Select
                                    value={exportFormat}
                                    label="Export Format"
                                    onChange={(e) => setExportFormat(e.target.value)}
                                >
                                    <MenuItem value="pdf">
                                        <Stack direction="row" alignItems="center" spacing={1}>
                                            <Iconify icon="eva:file-text-fill" sx={{ color: 'error.main' }} />
                                            <span>PDF Document</span>
                                        </Stack>
                                    </MenuItem>
                                    <MenuItem value="csv">
                                        <Stack direction="row" alignItems="center" spacing={1}>
                                            <Iconify icon="eva:grid-fill" sx={{ color: 'success.main' }} />
                                            <span>CSV Spreadsheet</span>
                                        </Stack>
                                    </MenuItem>
                                </Select>
                            </FormControl>

                            <FormControl fullWidth size="small">
                                <InputLabel>Filter by Status</InputLabel>
                                <Select
                                    value={exportStatus}
                                    label="Filter by Status"
                                    onChange={(e) => setExportStatus(e.target.value)}
                                >
                                    <MenuItem value="ALL">All Statuses</MenuItem>
                                    <MenuItem value="success">Success</MenuItem>
                                    <MenuItem value="failed">Failed</MenuItem>
                                    <MenuItem value="pending">Pending</MenuItem>
                                </Select>
                            </FormControl>

                            {(startDate || endDate) && (
                                <Box sx={{ p: 1.5, bgcolor: 'background.neutral', borderRadius: 1 }}>
                                    <Typography variant="caption" sx={{ color: 'text.secondary', display: 'block' }}>
                                        Active Date Filter:
                                    </Typography>
                                    <Typography variant="subtitle2">
                                        {startDate || 'Start'} — {endDate || 'End'}
                                    </Typography>
                                </Box>
                            )}
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setExportDialogOpen(false)} color="inherit" disabled={exporting}>
                            Cancel
                        </Button>
                        <Button
                            onClick={handleExport}
                            variant="contained"
                            disabled={exporting}
                            startIcon={exporting ? <LogoLoader size={16} /> : <Iconify icon="eva:download-fill" />}
                        >
                            {exporting ? 'Generating...' : `Export to ${exportFormat.toUpperCase()}`}
                        </Button>
                    </DialogActions>
                </Dialog>
            </Container>
        </Page>
    );
}
