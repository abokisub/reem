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
    CircularProgress
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
    { id: 'reference', label: 'Reference', alignRight: false },
    { id: 'customer', label: 'Customer', alignRight: false },
    { id: 'type', label: 'Type', alignRight: false },
    { id: 'amount', label: 'Amount', alignRight: false },
    { id: 'charges', label: 'Charges', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'date', label: 'Date', alignRight: false },
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
                        <TableContainer sx={{ minWidth: 1000 }}>
                            <Table>
                                <TransHead headLabel={TABLE_HEAD} rowCount={transactions.length} />
                                <TableBody>
                                    {!load ? (
                                        transactions.map((row, index) => (
                                            <TableRow hover key={row.id || index}>
                                                <TableCell>
                                                    <Typography variant="subtitle2" sx={{ fontWeight: 600 }}>
                                                        {row.reference}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>
                                                    <Box>
                                                        <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                                            {row.customer_name || 'N/A'}
                                                        </Typography>
                                                        <Typography variant="caption" color="text.secondary">
                                                            {row.customer_account_number || ''}
                                                        </Typography>
                                                    </Box>
                                                </TableCell>
                                                <TableCell>
                                                    <Label color={row.type === 'credit' ? 'success' : 'warning'} variant="soft">
                                                        {row.type}
                                                    </Label>
                                                </TableCell>
                                                <TableCell>
                                                    <Typography variant="subtitle2" sx={{ fontWeight: 800 }}>
                                                        ₦{fCurrency(row.amount)}
                                                    </Typography>
                                                </TableCell>
                                                <TableCell>₦{fCurrency(row.charges || 0)}</TableCell>
                                                <TableCell>
                                                    <Label
                                                        variant="ghost"
                                                        color={row.status === 'success' ? 'success' : row.status === 'failed' ? 'error' : 'warning'}
                                                    >
                                                        {row.status}
                                                    </Label>
                                                </TableCell>
                                                <TableCell sx={{ whiteSpace: 'nowrap' }}>
                                                    {new Date(row.created_at).toLocaleString()}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} align="center" sx={{ py: 10 }}>
                                                <CircularProgress />
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {!load && transactions.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={7} align="center" sx={{ py: 10 }}>
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
