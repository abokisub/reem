/* eslint-disable react-hooks/exhaustive-deps */
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { sentenceCase } from 'change-case';

// @mui
import { useTheme, styled, alpha } from '@mui/material/styles';
import {
    Container,
    Typography,
    Box,
    Button,
    Grid,
    Stack,
    Paper,
    IconButton,
    Divider,
} from '@mui/material';
import { useSnackbar } from 'notistack';

// hooks
import useSystemName from '../../hooks/useSystemName';
// components
import Page from '../../components/Page';
import Label from '../../components/Label';
import Iconify from '../../components/Iconify';
// format number
import { fCurrency } from '../../utils/formatNumber';
// axios
import axios from '../../utils/axios';

// ----------------------------------------------------------------------

const DetailPaper = styled(Paper)(({ theme }) => ({
    padding: theme.spacing(4),
    maxWidth: 900,
    margin: 'auto',
    borderRadius: 20,
    boxShadow: `0 24px 48px -12px ${alpha(theme.palette.grey[500], 0.16)}`,
    border: `1px solid ${theme.palette.divider}`,
}));

const SectionBox = styled(Box)(({ theme }) => ({
    padding: theme.spacing(3),
    borderRadius: 16,
    backgroundColor: theme.palette.background.neutral,
    height: '100%',
}));

const InfoRow = ({ label, value, isCopyable, onCopy }) => (
    <Stack direction="row" alignItems="center" justifyContent="space-between" sx={{ mb: 2 }}>
        <Typography variant="body2" sx={{ color: 'text.secondary', fontWeight: 600 }}>
            {label}
        </Typography>
        <Stack direction="row" alignItems="center" spacing={1}>
            <Typography variant="subtitle2" sx={{ fontWeight: 700, textAlign: 'right' }}>
                {value || '—'}
            </Typography>
            {isCopyable && value && (
                <IconButton size="small" onClick={() => onCopy(value)}>
                    <Iconify icon="eva:copy-outline" width={16} height={16} />
                </IconButton>
            )}
        </Stack>
    </Stack>
);

// ----------------------------------------------------------------------

export default function RATransactionDetails() {
    const theme = useTheme();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const { id } = useParams();
    const systemName = useSystemName();

    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);

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
            const foundTransaction = allTransactions.find(t => t.id === parseInt(id) || t.transid === id || t.transaction_ref === id);

            if (foundTransaction) {
                setTransaction(foundTransaction);
            } else {
                enqueueSnackbar('Transaction not found', { variant: 'error' });
                navigate('/dashboard/ra-transactions');
            }
        } catch (error) {
            console.error('Error fetching transaction:', error);
            enqueueSnackbar('Error loading details', { variant: 'error' });
            navigate('/dashboard/ra-transactions');
        } finally {
            setLoading(false);
        }
    };

    const handleCopy = (text) => {
        navigator.clipboard.writeText(text);
        enqueueSnackbar('Copied to clipboard', { variant: 'success', autoHideDuration: 1000 });
    };

    if (loading || !transaction) {
        return (
            <Page title="Transaction Details">
                <Container sx={{ py: 10, textAlign: 'center' }}>
                    <Iconify icon="eos-icons:loading" width={40} height={40} sx={{ color: 'primary.main', mb: 2 }} />
                    <Typography variant="body2" color="text.secondary">Loading Details...</Typography>
                </Container>
            </Page>
        );
    }

    const {
        transaction_ref,
        session_id,
        amount,
        fee,
        net_amount,
        status,
        created_at,
        transaction_type,
        customer_name,
        description,
        recipient_account_number,
        recipient_account_name,
        recipient_bank_name,
        va_account_number,
        va_account_name,
        va_bank_name,
        settlement_status,
        settlement_batch_no,
        palmpay_reference
    } = transaction;

    // Status Logic
    let statusColor = 'warning';
    if (['success', 'successful', '1'].includes(status?.toLowerCase())) statusColor = 'success';
    if (['failed', '2'].includes(status?.toLowerCase())) statusColor = 'error';

    // Settlement Logic
    let settlementColor = 'warning';
    if (settlement_status === 'settled') settlementColor = 'success';
    if (settlement_status === 'failed') settlementColor = 'error';

    const formatDate = (dateStr) => {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleString('en-GB', {
            year: 'numeric',
            month: 'long',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }) + ' WAT';
    };

    return (
        <Page title="Transaction Details">
            <Container maxWidth={false} sx={{ py: 5 }}>
                <Box sx={{ mb: 4, display: 'flex', alignItems: 'center', gap: 1 }}>
                    <IconButton onClick={() => navigate('/dashboard/ra-transactions')} sx={{ color: 'text.primary' }}>
                        <Iconify icon="eva:arrow-back-fill" />
                    </IconButton>
                    <Typography variant="h4" sx={{ fontWeight: 800 }}>Transaction Receipt</Typography>
                </Box>

                <DetailPaper>
                    {/* Header Section */}
                    <Box sx={{ textAlign: 'center', mb: 5 }}>
                        <Typography variant="overline" sx={{ color: 'text.secondary', fontWeight: 800, letterSpacing: 2 }}>
                            TRANSACTION AMOUNT
                        </Typography>
                        <Typography variant="h2" sx={{ fontWeight: 900, color: 'text.primary', my: 1 }}>
                            ₦{fCurrency(amount)}
                        </Typography>
                        <Stack direction="row" spacing={1} justifyContent="center" alignItems="center">
                            <Label color={statusColor} sx={{ textTransform: 'uppercase', fontWeight: 900, px: 2, py: 2 }}>
                                {status || 'PENDING'}
                            </Label>
                            <Label color={settlementColor} variant="soft" sx={{ textTransform: 'uppercase', fontWeight: 800 }}>
                                {settlement_status || 'UNSETTLED'}
                            </Label>
                        </Stack>
                    </Box>

                    <Grid container spacing={3}>
                        {/* Section 1: Transaction Metadata */}
                        <Grid item xs={12} md={6}>
                            <SectionBox>
                                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 3 }}>
                                    <Iconify icon="eva:info-fill" width={20} sx={{ color: 'primary.main' }} />
                                    <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>Basic Information</Typography>
                                </Stack>
                                <InfoRow label="Transaction ID" value={transaction_ref} isCopyable onCopy={handleCopy} />
                                <InfoRow label="Type" value={sentenceCase(transaction_type || 'transfer')} />
                                <InfoRow label="Date & Time" value={formatDate(created_at)} />
                                <InfoRow label="Description" value={description} />
                            </SectionBox>
                        </Grid>

                        {/* Section 2: Financials */}
                        <Grid item xs={12} md={6}>
                            <SectionBox>
                                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 3 }}>
                                    <Iconify icon="eva:pie-chart-fill" width={20} sx={{ color: 'success.main' }} />
                                    <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>Financial Breakdown</Typography>
                                </Stack>
                                <InfoRow label="Gross Amount" value={`₦${fCurrency(amount)}`} />
                                <InfoRow label="Service Fee" value={`₦${fCurrency(fee || 0)}`} />
                                <Divider sx={{ my: 1.5, borderStyle: 'dashed' }} />
                                <InfoRow label="Net Amount" value={`₦${fCurrency(net_amount || (amount - fee))}`} />
                                <InfoRow label="Palmpay Ref" value={palmpay_reference} isCopyable onCopy={handleCopy} />
                            </SectionBox>
                        </Grid>

                        {/* Section 3: Sender / Payer */}
                        <Grid item xs={12} md={6}>
                            <SectionBox>
                                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 3 }}>
                                    <Iconify icon="eva:person-fill" width={20} sx={{ color: 'warning.main' }} />
                                    <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>Payer Information</Typography>
                                </Stack>
                                <InfoRow label="Customer Name" value={customer_name} />
                                <InfoRow label="Session ID" value={session_id} isCopyable onCopy={handleCopy} />
                                <InfoRow label="Reserved Acc" value={va_account_number} isCopyable onCopy={handleCopy} />
                                <InfoRow label="Bank" value={va_bank_name || 'PalmPay'} />
                            </SectionBox>
                        </Grid>

                        {/* Section 4: Destination / Settlement */}
                        <Grid item xs={12} md={6}>
                            <SectionBox>
                                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 3 }}>
                                    <Iconify icon="eva:diagonal-arrow-right-up-fill" width={20} sx={{ color: 'info.main' }} />
                                    <Typography variant="subtitle1" sx={{ fontWeight: 800 }}>Destination Details</Typography>
                                </Stack>
                                <InfoRow label="Recipient" value={recipient_account_name || systemName} />
                                <InfoRow label="Account No" value={recipient_account_number} isCopyable onCopy={handleCopy} />
                                <InfoRow label="Bank" value={recipient_bank_name} />
                                <InfoRow label="Settlement Batch" value={settlement_batch_no} isCopyable onCopy={handleCopy} />
                            </SectionBox>
                        </Grid>
                    </Grid>

                    {/* Actions Footer */}
                    <Box sx={{ mt: 5, pt: 4, borderTop: `1px solid ${theme.palette.divider}`, textAlign: 'center' }}>
                        <Stack direction="row" spacing={2} justifyContent="center">
                            <Button
                                variant="contained"
                                size="large"
                                startIcon={<Iconify icon="eva:download-fill" />}
                                onClick={() => window.print()}
                                sx={{ borderRadius: 1.5, px: 4, py: 1.5, fontWeight: 900 }}
                            >
                                Download Receipt
                            </Button>
                            <Button
                                variant="soft"
                                color="inherit"
                                size="large"
                                onClick={() => navigate('/dashboard/ra-transactions')}
                                sx={{ borderRadius: 1.5, px: 4, py: 1.5, fontWeight: 700 }}
                            >
                                Close Receipt
                            </Button>
                        </Stack>
                        <Typography variant="caption" sx={{ color: 'text.disabled', display: 'block', mt: 3, fontWeight: 600 }}>
                            This is an official transaction summary generated by {systemName} Business Portal.
                        </Typography>
                    </Box>
                </DetailPaper>
            </Container>
        </Page>
    );
}
