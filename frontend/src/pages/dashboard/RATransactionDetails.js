/* eslint-disable react-hooks/exhaustive-deps */
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { sentenceCase } from 'change-case';

// @mui
import { useTheme } from '@mui/material/styles';
import {
    Container,
    Typography,
    Box,
    Button,
    Grid,
    Stack,
    styled,
    alpha,
    Paper,
    IconButton,
    Alert
} from '@mui/material';
import { useSnackbar } from 'notistack';

// hooks
import useSettings from '../../hooks/useSettings';
import useSystemName from '../../hooks/useSystemName';
// components
import Page from '../../components/Page';
import Label from '../../components/Label';
import Iconify from '../../components/Iconify';
import Image from '../../components/Image';
// format number
import { fCurrency } from '../../utils/formatNumber';
// axios
import axios from '../../utils/axios';

// ----------------------------------------------------------------------

const ReceiptPaper = styled(Paper)(({ theme }) => ({
    padding: theme.spacing(4),
    maxWidth: 600,
    margin: 'auto',
    borderRadius: 20,
    boxShadow: `0 24px 48px -12px ${alpha(theme.palette.grey[500], 0.16)}`,
    position: 'relative',
    overflow: 'hidden',
    border: `1px solid ${theme.palette.divider}`,
    [theme.breakpoints.down('sm')]: {
        padding: theme.spacing(3),
    }
}));

const ReceiptHeader = styled(Box)(({ theme }) => ({
    textAlign: 'center',
    marginBottom: theme.spacing(4),
    borderBottom: `2px dashed ${theme.palette.divider}`,
    paddingBottom: theme.spacing(4),
}));

const SectionTitle = styled(Typography)(({ theme }) => ({
    fontSize: '0.75rem',
    fontWeight: 800,
    textTransform: 'uppercase',
    color: theme.palette.text.secondary,
    letterSpacing: 1.2,
    marginBottom: theme.spacing(1.5),
}));

const DetailRow = styled(Box)(({ theme }) => ({
    display: 'flex',
    justifyContent: 'space-between',
    padding: theme.spacing(1.5, 0),
    borderBottom: `1px solid ${alpha(theme.palette.divider, 0.5)}`,
    '&:last-of-type': {
        borderBottom: 'none',
    }
}));

const ValueText = styled(Typography)(({ theme }) => ({
    fontWeight: 700,
    color: theme.palette.text.primary,
}));

// ----------------------------------------------------------------------

export default function RATransactionDetails() {
    const theme = useTheme();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const { themeStretch } = useSettings();
    const { id } = useParams();
    const systemName = useSystemName();

    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refundLoading, setRefundLoading] = useState(false);
    const [notificationLoading, setNotificationLoading] = useState(false);

    const AccessToken = window.localStorage.getItem('accessToken');

    useEffect(() => {
        fetchTransactionDetails();
    }, [id]);

    const fetchTransactionDetails = async () => {
        setLoading(true);
        try {
            const response = await axios.get(
                `/api/system/all/ra-history/records/${AccessToken}/secure?page=1&limit=1000&status=ALL`
            );

            const allTransactions = response.data?.ra_trans?.data || [];
            const foundTransaction = allTransactions.find(t => t.id === parseInt(id) || t.transid === id);

            if (foundTransaction) {
                setTransaction(foundTransaction);
            } else {
                enqueueSnackbar('Transaction not found', { variant: 'error' });
                navigate('/dashboard/ra-transactions');
            }
        } catch (error) {
            console.error('Error fetching transaction:', error);
            enqueueSnackbar('Error loading transaction details', { variant: 'error' });
            navigate('/dashboard/ra-transactions');
        } finally {
            setLoading(false);
        }
    };

    const handleInitiateRefund = async () => {
        if (!transaction) return;
        setRefundLoading(true);
        try {
            await axios.post(`/api/transactions/${transaction.id}/refund`, {
                transaction_id: transaction.transaction_id,
                amount: transaction.amount
            });
            enqueueSnackbar('Refund initiated successfully', { variant: 'success' });
            fetchTransactionDetails();
        } catch (error) {
            enqueueSnackbar(error.response?.data?.message || 'Failed to initiate refund', { variant: 'error' });
        } finally {
            setRefundLoading(false);
        }
    };

    const handleResendNotification = async () => {
        if (!transaction) return;
        setNotificationLoading(true);
        try {
            await axios.post(`/api/transactions/${transaction.id}/resend-notification`, {
                transaction_id: transaction.transaction_id
            });
            enqueueSnackbar('Notification sent successfully', { variant: 'success' });
        } catch (error) {
            enqueueSnackbar(error.response?.data?.message || 'Failed to send notification', { variant: 'error' });
        } finally {
            setNotificationLoading(false);
        }
    };

    if (loading) {
        return (
            <Page title="Transaction Receipt">
                <Container sx={{ py: 10, textAlign: 'center' }}>
                    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2 }}>
                        <Iconify icon="eos-icons:loading" width={40} height={40} sx={{ color: 'primary.main' }} />
                        <Typography variant="body2" color="text.secondary">Fetching Receipt...</Typography>
                    </Box>
                </Container>
            </Page>
        );
    }

    const metadata = transaction.metadata ? (typeof transaction.metadata === 'string' ? JSON.parse(transaction.metadata) : transaction.metadata) : {};

    // Status Logic
    let statusText = 'PENDING';
    let statusColor = 'warning';
    const currentStatus = transaction.status?.toString().toLowerCase();

    if (['successful', 'success', '1', 'completed'].includes(currentStatus)) {
        statusText = 'SUCCESSFUL';
        statusColor = 'success';
    } else if (['failed', '2', 'fail'].includes(currentStatus)) {
        statusText = 'FAILED';
        statusColor = 'error';
    } else if (['0', 'processing'].includes(currentStatus)) {
        statusText = 'PROCESSING';
        statusColor = 'info';
    }

    // Determine if this is a credit (deposit) or debit (transfer/withdrawal) transaction
    const isCredit = transaction.type === 'credit';
    
    // For credit transactions (deposits), show sender info
    // For debit transactions (transfers), show company info as sender
    const senderName = isCredit 
        ? (metadata.sender_name || metadata.sender_account_name || transaction.customer_name || transaction.va_account_name || 'N/A')
        : (transaction.company_name || `${systemName} Business`);
    
    const senderAccount = isCredit 
        ? (metadata.sender_account || transaction.customer_account || 'N/A')
        : (transaction.company_account_number || transaction.va_account_number || 'N/A');
    
    const senderBank = isCredit 
        ? (metadata.sender_bank || metadata.sender_bank_name || transaction.customer_bank || 'N/A')
        : 'PalmPay';
    
    // Recipient information
    const recipientName = isCredit 
        ? (transaction.va_account_name || 'N/A')
        : (transaction.recipient_account_name || transaction.customer_name || 'N/A');
    
    const recipientAccount = isCredit 
        ? (transaction.va_account_number || 'N/A')
        : (transaction.recipient_account_number || transaction.customer_account || 'N/A');
    
    const recipientBank = isCredit 
        ? 'PalmPay'
        : (transaction.recipient_bank_name || transaction.customer_bank || 'PalmPay');
    
    // Balance information
    const oldBalance = transaction.oldbal || transaction.balance_before || 0;
    const newBalance = transaction.newbal || transaction.balance_after || 0;
    const fee = transaction.charges || transaction.fee || 0;
    const netAmount = transaction.net_amount || (transaction.amount - fee);
    
    // Format date to Nigerian time and format
    const formatNigerianDate = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        
        // Convert to Nigerian time (WAT - UTC+1)
        const options = {
            timeZone: 'Africa/Lagos',
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        
        return d.toLocaleString('en-NG', options) + ' WAT';
    };
    
    const isRefundable = statusText === 'SUCCESSFUL' && !transaction.is_refunded;

    return (
        <Page title="Transaction Receipt">
            <Container maxWidth={themeStretch ? false : 'lg'} sx={{ py: 5 }}>
                <Box sx={{ mb: 4, display: 'flex', alignItems: 'center', gap: 1 }}>
                    <IconButton onClick={() => navigate('/dashboard/ra-transactions')} sx={{ color: 'text.primary' }}>
                        <Iconify icon="eva:arrow-back-fill" />
                    </IconButton>
                    <Typography variant="h4" sx={{ fontWeight: 800 }}>Transaction Receipt</Typography>
                </Box>

                <ReceiptPaper>
                    <ReceiptHeader>
                        <Image src="/upload/welcome.png" sx={{ maxWidth: 120, mx: 'auto', mb: 2 }} />
                        <Typography variant="h5" sx={{ fontWeight: 900, mb: 0.5 }}>Transaction Receipt</Typography>
                        <Typography variant="body2" sx={{ color: 'text.secondary', fontWeight: 600 }}>{transaction.transid || transaction.reference}</Typography>

                        <Box sx={{ mt: 3, display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                            <Box sx={{
                                width: 64,
                                height: 64,
                                borderRadius: '50%',
                                bgcolor: alpha(theme.palette[statusColor].main, 0.1),
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                mb: 2
                            }}>
                                <Iconify
                                    icon={statusColor === 'success' ? 'eva:checkmark-circle-2-fill' : statusColor === 'error' ? 'eva:close-circle-fill' : 'eva:clock-fill'}
                                    width={40}
                                    height={40}
                                    sx={{ color: `${statusColor}.main` }}
                                />
                            </Box>
                            <Typography variant="h3" sx={{ fontWeight: 900, color: 'text.primary' }}>
                                ₦{fCurrency(transaction.amount)}
                            </Typography>
                            <Label color={statusColor} sx={{ mt: 1, px: 2, py: 2, fontWeight: 800, textTransform: 'uppercase' }}>
                                {statusText}
                            </Label>
                        </Box>
                    </ReceiptHeader>

                    <Box sx={{ mb: 4 }}>
                        <SectionTitle>Sender Details</SectionTitle>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Name</Typography>
                            <ValueText variant="body2">{senderName}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Account</Typography>
                            <ValueText variant="body2" sx={{ fontFamily: 'monospace' }}>{senderAccount}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Bank</Typography>
                            <ValueText variant="body2">{senderBank}</ValueText>
                        </DetailRow>
                    </Box>

                    <Box sx={{ mb: 4 }}>
                        <SectionTitle>Recipient Details</SectionTitle>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Account Name</Typography>
                            <ValueText variant="body2">{recipientName}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Account Number</Typography>
                            <ValueText variant="body2" sx={{ fontFamily: 'monospace' }}>{recipientAccount}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Bank</Typography>
                            <ValueText variant="body2">{recipientBank}</ValueText>
                        </DetailRow>
                    </Box>

                    <Box sx={{ mb: 4 }}>
                        <SectionTitle>Transaction Info</SectionTitle>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Date</Typography>
                            <ValueText variant="body2">{formatNigerianDate(transaction.date || transaction.created_at)}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Type</Typography>
                            <ValueText variant="body2">{sentenceCase(transaction.type || 'Transfer')}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Gross Amount</Typography>
                            <ValueText variant="body2">₦{fCurrency(transaction.amount)}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Fee</Typography>
                            <ValueText variant="body2" sx={{ color: 'error.main' }}>-₦{fCurrency(fee)}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Net Amount</Typography>
                            <ValueText variant="body2" sx={{ color: 'success.main', fontWeight: 900 }}>₦{fCurrency(netAmount)}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">Old Balance</Typography>
                            <ValueText variant="body2">₦{fCurrency(oldBalance)}</ValueText>
                        </DetailRow>
                        <DetailRow>
                            <Typography variant="body2" color="text.secondary">New Balance</Typography>
                            <ValueText variant="body2" sx={{ color: 'primary.main', fontWeight: 900 }}>₦{fCurrency(newBalance)}</ValueText>
                        </DetailRow>
                        {transaction.palmpay_reference && (
                            <DetailRow>
                                <Typography variant="body2" color="text.secondary">Provider Ref</Typography>
                                <ValueText variant="body2" sx={{ fontFamily: 'monospace' }}>{transaction.palmpay_reference}</ValueText>
                            </DetailRow>
                        )}
                    </Box>

                    <Box sx={{ textAlign: 'center', mt: 4, pt: 4, borderTop: `1px solid ${theme.palette.divider}` }}>
                        <Typography variant="caption" sx={{ color: 'text.disabled', display: 'block', mb: 2 }}>
                            This is an automated transaction receipt generated by {systemName}.
                        </Typography>

                        <Stack direction="row" spacing={2}>
                            <Button
                                fullWidth
                                variant="contained"
                                color="primary"
                                startIcon={<Iconify icon="eva:share-fill" />}
                                onClick={() => window.print()}
                                sx={{ borderRadius: 1.5, py: 1.5, fontWeight: 700 }}
                            >
                                Download Receipt
                            </Button>
                        </Stack>
                    </Box>
                </ReceiptPaper>

                {/* Admin/Special Actions */}
                <Box sx={{ mt: 5, maxWidth: 600, mx: 'auto' }}>
                    <Grid container spacing={2}>
                        <Grid item xs={6}>
                            <Button
                                fullWidth
                                variant="soft"
                                color="error"
                                disabled={refundLoading || !isRefundable}
                                onClick={handleInitiateRefund}
                                startIcon={<Iconify icon="eva:refresh-fill" />}
                                sx={{ py: 1.5, fontWeight: 700 }}
                            >
                                {refundLoading ? 'Wait...' : 'Refund'}
                            </Button>
                        </Grid>
                        <Grid item xs={6}>
                            <Button
                                fullWidth
                                variant="soft"
                                color="info"
                                disabled={notificationLoading}
                                onClick={handleResendNotification}
                                startIcon={<Iconify icon="eva:email-fill" />}
                                sx={{ py: 1.5, fontWeight: 700 }}
                            >
                                {notificationLoading ? 'Wait...' : 'Resend Mail'}
                            </Button>
                        </Grid>
                    </Grid>
                    {transaction.is_refunded && (
                        <Alert severity="warning" sx={{ mt: 2, borderRadius: 1.5, fontWeight: 600 }}>
                            This transaction has been refunded.
                        </Alert>
                    )}
                </Box>
            </Container>
        </Page>
    );
}
