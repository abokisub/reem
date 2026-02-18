/* eslint-disable react-hooks/exhaustive-deps */
// COMPLETE PROFESSIONAL RA TRANSACTIONS PAGE
// Copy this entire file to: frontend/src/pages/dashboard/RATransactions.js

import { useState, useEffect } from 'react';
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
    Stack,
    Box,
    Button,
    IconButton,
    styled,
    alpha,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Grid,
    Divider,
    Chip
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
    { id: 'transid', label: 'Transaction Ref', alignRight: false },
    { id: 'customer', label: 'Customer', alignRight: false },
    { id: 'amount', label: 'Amount', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'settlement', label: 'Settlement', alignRight: false },
    { id: 'charges', label: 'Fee', alignRight: false },
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
    const { enqueueSnackbar } = useSnackbar();
    const { themeStretch } = useSettings();

    const [transactions, setTransactions] = useState([]);
    const [page, setPage] = useState(0);
    const [load, SetLoad] = useState(true);
    const [totalPage, SetTotal] = useState(0);
    const [filterName, setFilterName] = useState('');
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [isNotFound, SetNotFound] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);
    const [openModal, setOpenModal] = useState(false);
    const [refundLoading, setRefundLoading] = useState(false);
    const [notificationLoading, setNotificationLoading] = useState(false);
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
        setSelectedTransaction(transaction);
        setOpenModal(true);
    };

    const handleCloseModal = () => {
        setOpenModal(false);
        setSelectedTransaction(null);
    };

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
                                                customer_name,
                                                amount,
                                                status,
                                                charges,
                                                created_at,
                                                date
                                            } = row;

                                            const displayDate = date || created_at || 'N/A';

                                            // Determine status string and color
                                            let statusText = 'processing';
                                            let statusColor = 'warning';

                                            if (status === 'successful' || status === 'success' || status === 1 || status === '1') {
                                                statusText = 'successful';
                                                statusColor = 'success';
                                            } else if (status === 'failed' || status === 2 || status === '2') {
                                                statusText = 'failed';
                                                statusColor = 'error';
                                            }

                                            // Settlement status
                                            let settlementText = statusText === 'successful' ? 'Successful' : statusText === 'failed' ? 'Failed' : 'Pending';

                                            return (
                                                <TableRow hover key={transid || index}>
                                                    <TableCell>
                                                        <Typography variant="subtitle2" sx={{ fontWeight: 600, fontFamily: 'monospace' }}>
                                                            {transid || 'N/A'}
                                                        </Typography>
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.primary', fontWeight: 600 }}>
                                                        {customer_name || 'Unknown'}
                                                    </TableCell>
                                                    <TableCell sx={{ fontWeight: 800, color: 'success.main', fontSize: '0.95rem' }}>
                                                        ₦{fCurrency(amount)}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Label
                                                            variant="soft"
                                                            color={statusColor}
                                                            sx={{ textTransform: 'capitalize', fontWeight: 700, px: 1.5 }}
                                                        >
                                                            {sentenceCase(statusText)}
                                                        </Label>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Chip 
                                                            label={settlementText}
                                                            size="small"
                                                            color={statusColor}
                                                            sx={{ fontWeight: 600 }}
                                                        />
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 600 }}>
                                                        ₦{fCurrency(charges || 0)}
                                                    </TableCell>
                                                    <TableCell sx={{ color: 'text.secondary', fontWeight: 500, fontSize: '0.875rem' }}>
                                                        {displayDate}
                                                    </TableCell>
                                                    <TableCell align="right">
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
                        onPageChange={(e, p) => initialize(p, rowsPerPage, filterName)}
                        onRowsPerPageChange={handleChangeRowsPerPage}
                    />
                </Card>

                {/* Transaction Details Modal */}
                <Dialog open={openModal} onClose={handleCloseModal} maxWidth="md" fullWidth>
                    <DialogTitle sx={{ pb: 2 }}>
                        <Typography variant="h5" sx={{ fontWeight: 700 }}>
                            Transaction Details
                        </Typography>
                        <Typography variant="body2" sx={{ color: 'text.secondary', mt: 0.5 }}>
                            View complete transaction information
                        </Typography>
                    </DialogTitle>
                    <Divider />
                    <DialogContent sx={{ py: 3 }}>
                        {selectedTransaction && (
                            <Box>
                                <Grid container spacing={3}>
                                    <Grid item xs={12}>
                                        <Box sx={{ 
                                            p: 2, 
                                            bgcolor: alpha(theme.palette.primary.main, 0.08),
                                            borderRadius: 2
                                        }}>
                                            <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                                Transaction Reference
                                            </Typography>
                                            <Typography variant="h6" sx={{ fontWeight: 700, fontFamily: 'monospace', mt: 0.5 }}>
                                                {selectedTransaction.transid || selectedTransaction.reference}
                                            </Typography>
                                        </Box>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Customer Name
                                        </Typography>
                                        <Typography variant="body1" sx={{ fontWeight: 600, mt: 0.5 }}>
                                            {selectedTransaction.customer_name || 'Unknown'}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Customer Account
                                        </Typography>
                                        <Typography variant="body1" sx={{ fontWeight: 600, mt: 0.5, fontFamily: 'monospace' }}>
                                            {selectedTransaction.customer_account || 'N/A'}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={4}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Amount
                                        </Typography>
                                        <Typography variant="h4" sx={{ fontWeight: 800, color: 'success.main', mt: 0.5 }}>
                                            ₦{fCurrency(selectedTransaction.amount)}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={4}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Fee
                                        </Typography>
                                        <Typography variant="h6" sx={{ fontWeight: 600, mt: 0.5 }}>
                                            ₦{fCurrency(selectedTransaction.charges || selectedTransaction.fee || 0)}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={4}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Net Amount
                                        </Typography>
                                        <Typography variant="h6" sx={{ fontWeight: 600, mt: 0.5 }}>
                                            ₦{fCurrency(selectedTransaction.net_amount || (selectedTransaction.amount - (selectedTransaction.charges || selectedTransaction.fee || 0)))}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Status
                                        </Typography>
                                        <Box sx={{ mt: 0.5 }}>
                                            <Label
                                                variant="soft"
                                                color={
                                                    selectedTransaction.status === 'successful' || selectedTransaction.status === 'success'
                                                        ? 'success'
                                                        : selectedTransaction.status === 'failed'
                                                            ? 'error'
                                                            : 'warning'
                                                }
                                                sx={{ textTransform: 'capitalize', fontWeight: 800, px: 2, py: 1 }}
                                            >
                                                {sentenceCase(selectedTransaction.status)}
                                            </Label>
                                        </Box>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Virtual Account
                                        </Typography>
                                        <Typography variant="body1" sx={{ fontWeight: 600, mt: 0.5, fontFamily: 'monospace' }}>
                                            {selectedTransaction.va_account_number || 'N/A'}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            PalmPay Reference
                                        </Typography>
                                        <Typography variant="body1" sx={{ fontWeight: 600, mt: 0.5, fontFamily: 'monospace' }}>
                                            {selectedTransaction.palmpay_reference || 'N/A'}
                                        </Typography>
                                    </Grid>

                                    <Grid item xs={12} md={6}>
                                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                            Date & Time
                                        </Typography>
                                        <Typography variant="body1" sx={{ fontWeight: 600, mt: 0.5 }}>
                                            {selectedTransaction.date || selectedTransaction.created_at}
                                        </Typography>
                                    </Grid>

                                    {selectedTransaction.description && (
                                        <Grid item xs={12}>
                                            <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 700 }}>
                                                Description
                                            </Typography>
                                            <Typography variant="body1" sx={{ fontWeight: 500, mt: 0.5 }}>
                                                {selectedTransaction.description}
                                            </Typography>
                                        </Grid>
                                    )}
                                </Grid>
                            </Box>
                        )}
                    </DialogContent>
                    <Divider />
                    <DialogActions sx={{ p: 2.5, gap: 1 }}>
                        <Button 
                            onClick={handleInitiateRefund}
                            variant="contained"
                            color="error"
                            disabled={refundLoading || selectedTransaction?.status !== 'successful'}
                            startIcon={<Iconify icon="eva:refresh-fill" />}
                            sx={{ borderRadius: 1.5 }}
                        >
                            {refundLoading ? 'Processing...' : 'Initiate Refund'}
                        </Button>
                        <Button 
                            onClick={handleResendNotification}
                            variant="contained"
                            color="info"
                            disabled={notificationLoading}
                            startIcon={<Iconify icon="eva:email-fill" />}
                            sx={{ borderRadius: 1.5 }}
                        >
                            {notificationLoading ? 'Sending...' : 'Resend Notification'}
                        </Button>
                        <Box sx={{ flex: 1 }} />
                        <Button onClick={handleCloseModal} variant="outlined" sx={{ borderRadius: 1.5 }}>
                            Close
                        </Button>
                    </DialogActions>
                </Dialog>
            </Container>
        </Page>
    );
}
