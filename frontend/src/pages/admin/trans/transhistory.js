/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable no-restricted-globals */
/* eslint-disable camelcase */
import { sentenceCase, capitalCase } from 'change-case';
import { useState, useEffect } from 'react';
import { useSnackbar } from 'notistack';
import swal from 'sweetalert';

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
  Tabs,
  Tab,
  Box,
  Stack,
  Divider,
  IconButton,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Grid,
  TextField,
  MenuItem,
  Select,
  FormControl,
  InputLabel,
  Menu
} from '@mui/material';

// routes
import { PATH_ADMIN } from '../../../routes/paths';
// hooks
import useSettings from '../../../hooks/useSettings';
// components
import Page from '../../../components/Page';
import Label from '../../../components/Label';
import SearchNotFound from '../../../components/SearchNotFound';
import Iconify from '../../../components/Iconify';
import LogoLoader from '../../../components/LogoLoader';

// format number 
import { fCurrency } from '../../../utils/formatNumber';
// sections
import { TransHead, PlanToolbar, Payment } from '../../../sections/admin/user/list';

// axios
import axios from '../../../utils/axios';

// ----------------------------------------------------------------------


const StyledCard = styled(Card)(({ theme }) => ({
  borderRadius: 20,
  border: `1px solid ${theme.palette.divider}`,
  boxShadow: 'none',
  backgroundColor: '#fff',
  overflow: 'hidden',
}));

const TABLE_HEAD = [
  { id: 'date', label: 'Create Time', alignRight: false },
  { id: 'category', label: 'Order Type', alignRight: false },
  { id: 'merchant_ref', label: 'Merchant Order No.', alignRight: false },
  { id: 'currency', label: 'Currency', alignRight: false },
  { id: 'transid', label: 'Transaction Order No.', alignRight: false },
  { id: 'status', label: 'Status', alignRight: false },
  { id: 'amount', label: 'Amount', alignRight: false },
  { id: 'settlement_status', label: 'Settlement Status', alignRight: false },
  { id: 'settlement_time', label: 'Settlement Time', alignRight: false },
  { id: 'refund_status', label: 'Refund Status', alignRight: false },
  { id: 'session_id', label: 'Pay ID', alignRight: false },
  { id: 'settlement_amount', label: 'Settlement Amount', alignRight: false },
  { id: 'actions', label: 'Operation', alignRight: false },
];

const STATUS_TABS = [
  { value: 'ALL', label: 'All Traffic' },
  { value: 'success', label: 'Success' },
  { value: 'pending', label: 'Processing' },
  { value: 'failed', label: 'Failed' }
];

// ----------------------------------------------------------------------

const DetailRow = ({ label, value, isCopyable = false }) => {
  const { enqueueSnackbar } = useSnackbar();

  const handleCopy = (text) => {
    if (!text || text === 'N/A') return;
    navigator.clipboard.writeText(text);
    enqueueSnackbar('Copied to clipboard', { variant: 'success', autoHideDuration: 2000 });
  };

  return (
    <Stack direction="row" justifyContent="space-between" alignItems="flex-start" sx={{ width: '100%' }}>
      <Typography variant="body2" sx={{ color: 'text.secondary', fontWeight: 600, minWidth: 120 }}>
        {label}
      </Typography>
      <Stack direction="row" alignItems="center" spacing={0.5} sx={{ textAlign: 'right' }}>
        <Typography variant="subtitle2" sx={{ fontWeight: 700, wordBreak: 'break-all' }}>
          {value}
        </Typography>
        {isCopyable && value && value !== 'N/A' && (
          <IconButton size="small" onClick={() => handleCopy(value)} sx={{ p: 0.5 }}>
            <Iconify icon="eva:copy-fill" width={14} height={14} />
          </IconButton>
        )}
      </Stack>
    </Stack>
  );
};

// ----------------------------------------------------------------------

export default function AdminTransactionHistory() {
  const theme = useTheme();
  const { themeStretch } = useSettings();
  const [userList, setUserList] = useState([]);
  const [page, setPage] = useState(0);
  const [load, setLoad] = useState(true);
  const [totalRecords, setTotalRecords] = useState(0);
  const [filterName, setFilterName] = useState('');
  const [status, setStatus] = useState('ALL');
  const [filters, setFilters] = useState({
    startDate: '',
    endDate: '',
    orderType: '',
    transOrderNo: '',
    merchantOrderNo: '',
    payId: '',
    payAccount: '',
    payeeAccount: ''
  });
  const [selectedTransaction, setSelectedTransaction] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [exportAnchor, setExportAnchor] = useState(null);
  const openExport = Boolean(exportAnchor);
  const AccessToken = window.localStorage.getItem('accessToken');
  const isNotFound = userList.length === 0 && !load;

  useEffect(() => {
    initialize(0, 10, status, filters);
  }, []);

  const initialize = async (pag, limit = 10, statusVal, filtersVal = filters, searchVal = filterName) => {
    const api_page = pag + 1;
    setLoad(true);
    try {
      const { startDate, endDate, orderType, transOrderNo, merchantOrderNo, payId, payAccount, payeeAccount } = filtersVal;

      const queryParams = new URLSearchParams({
        page: api_page,
        limit,
        status: statusVal,
        start_date: startDate,
        end_date: endDate,
        category: orderType,
        trans_order_no: transOrderNo,
        merchant_order_no: merchantOrderNo,
        session_id: payId,
        payer_account: payAccount,
        payee_account: payeeAccount,
        search: searchVal
      }).toString();

      const response = await axios.get(`/api/admin/all/transaction/history/${AccessToken}/secure?${queryParams}`);
      setUserList(response.data?.all_summary?.data || []);
      setTotalRecords(response.data?.all_summary?.total || 0);
      setLoad(false);
      setPage(pag);
    } catch (_) {
      setLoad(false);
      setUserList([]);
    }
  };

  const handleApplyFilters = () => {
    setPage(0);
    initialize(0, 10, status, filters, filterName);
  };

  const handleResetFilters = () => {
    const resetValues = {
      startDate: '',
      endDate: '',
      orderType: '',
      transOrderNo: '',
      merchantOrderNo: '',
      payId: '',
      payAccount: '',
      payeeAccount: ''
    };
    setFilters(resetValues);
    setStatus('ALL');
    setFilterName('');
    setPage(0);
    initialize(0, 10, 'ALL', resetValues, '');
  };

  const handleFilterByName = (name) => {
    setFilterName(name);
    setPage(0);
    initialize(0, 10, status, name);
  };

  const handleViewDetails = (transaction) => {
    setSelectedTransaction(transaction);
    setDetailsOpen(true);
  };

  const handleCloseDetails = () => {
    setDetailsOpen(false);
    setSelectedTransaction(null);
  };

  const handleOpenExport = (event) => {
    setExportAnchor(event.currentTarget);
  };

  const handleCloseExport = () => {
    setExportAnchor(null);
  };

  const { enqueueSnackbar } = useSnackbar();

  const onFail = (reference, action = 'refund') => {
    setLoad(true)
    axios.post(`/api/admin/trans/action/${AccessToken}/secure`, {
      action: action,
      reference: reference
    }).then((res) => {
      setLoad(false)
      enqueueSnackbar(res.data.message || 'Action Completed')
      initialize(page, 10, status, filters);
    }).catch((error) => {
      setLoad(false)
      initialize(page, 10, status, filters);
      const message = error.response?.data?.message || error.message || 'An Error Occurred';
      enqueueSnackbar(message, { variant: 'error' })
    })
  }

  const onSuccess = (reference, action = 'notify_credit') => {
    setLoad(true)
    axios.post(`/api/admin/trans/action/${AccessToken}/secure`, {
      action: action,
      reference: reference
    }).then((res) => {
      setLoad(false)
      enqueueSnackbar(res.data.message || 'Action Completed')
      initialize(page, 10, status, filters);
    }).catch((error) => {
      setLoad(false)
      initialize(page, 10, status, filters);
      const message = error.response?.data?.message || error.message || 'An Error Occurred';
      enqueueSnackbar(message, { variant: 'error' })
    })
  }

  const handleExport = async (format = 'csv') => {
    handleCloseExport();
    setExporting(true);
    try {
      const { startDate, endDate, orderType, transOrderNo, merchantOrderNo, payId, payAccount, payeeAccount } = filters;

      const queryParams = new URLSearchParams({
        status,
        format,
        start_date: startDate,
        end_date: endDate,
        category: orderType,
        trans_order_no: transOrderNo,
        merchant_order_no: merchantOrderNo,
        session_id: payId,
        payer_account: payAccount,
        payee_account: payeeAccount,
        search: filterName
      }).toString();

      const response = await axios.get(
        `/api/admin/all/transaction/history/${AccessToken}/secure/export?${queryParams}`,
        { responseType: 'blob' }
      );

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      const fileName = `transaction_history_${new Date().getTime()}.${format}`;
      link.setAttribute('download', fileName);
      document.body.appendChild(link);
      link.click();
      link.remove();
      enqueueSnackbar(`Export as ${format.toUpperCase()} successful`, { variant: 'success' });
    } catch (error) {
      console.error(error);
      enqueueSnackbar('Failed to export transaction history', { variant: 'error' });
    } finally {
      setExporting(false);
    }
  };

  return (
    <Page title="Admin: Transaction Hub">
      <Container maxWidth={themeStretch ? false : 'xl'}>
        <Stack direction="row" alignItems="center" justifyContent="space-between" sx={{ mb: 3 }}>
          <Typography variant="h4" sx={{ fontWeight: 900 }}>
            Transaction Hub
          </Typography>
          <Box>
            <Button
              variant="contained"
              color="primary"
              startIcon={exporting ? <LogoLoader size={16} /> : <Iconify icon="eva:download-fill" />}
              onClick={handleOpenExport}
              disabled={exporting}
              sx={{ fontWeight: 800 }}
            >
              {exporting ? 'Exporting...' : 'Export History'}
            </Button>
            <Menu
              anchorEl={exportAnchor}
              open={openExport}
              onClose={handleCloseExport}
              PaperProps={{
                sx: { width: 140, mt: 1, boxShadow: theme.customShadows.z20 }
              }}
            >
              <MenuItem onClick={() => handleExport('csv')} sx={{ fontWeight: 700 }}>
                <Iconify icon="eva:file-text-fill" sx={{ mr: 1 }} /> CSV Format
              </MenuItem>
              <MenuItem onClick={() => handleExport('pdf')} sx={{ fontWeight: 700 }}>
                <Iconify icon="eva:file-fill" sx={{ mr: 1, color: 'error.main' }} /> PDF Report
              </MenuItem>
            </Menu>
          </Box>
        </Stack>

        <StyledCard>
          <Box sx={{ px: 2, pt: 2, bgcolor: '#F9FAFB' }}>
            <Tabs
              value={status}
              onChange={(e, value) => {
                setStatus(value);
                initialize(0, 10, value, filters);
                setPage(0);
              }}
              sx={{
                '& .MuiTabs-indicator': { height: 3, borderRadius: '3px 3px 0 0' }
              }}
            >
              {STATUS_TABS.map((tab) => (
                <Tab
                  key={tab.value}
                  label={tab.label}
                  value={tab.value}
                  sx={{ fontWeight: 700, textTransform: 'none', minWidth: 120 }}
                />
              ))}
            </Tabs>
          </Box>

          <Divider />

          <Box sx={{ p: 3, bgcolor: '#fff' }}>
            <Grid container spacing={2}>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Start Date"
                  type="date"
                  InputLabelProps={{ shrink: true }}
                  value={filters.startDate}
                  onChange={(e) => setFilters({ ...filters, startDate: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="End Date"
                  type="date"
                  InputLabelProps={{ shrink: true }}
                  value={filters.endDate}
                  onChange={(e) => setFilters({ ...filters, endDate: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Order Type</InputLabel>
                  <Select
                    label="Order Type"
                    value={filters.orderType}
                    onChange={(e) => setFilters({ ...filters, orderType: e.target.value })}
                  >
                    <MenuItem value="">All Types</MenuItem>
                    <MenuItem value="va_deposit">VA Deposit</MenuItem>
                    <MenuItem value="transfer">Transfer</MenuItem>
                    <MenuItem value="airtime">Airtime</MenuItem>
                    <MenuItem value="data">Data</MenuItem>
                    <MenuItem value="bill">Bill Payment</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Transaction Order No."
                  placeholder="Reference ID"
                  value={filters.transOrderNo}
                  onChange={(e) => setFilters({ ...filters, transOrderNo: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Merchant Order No."
                  placeholder="External ID"
                  value={filters.merchantOrderNo}
                  onChange={(e) => setFilters({ ...filters, merchantOrderNo: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Pay ID (Session ID)"
                  placeholder="Network Session"
                  value={filters.payId}
                  onChange={(e) => setFilters({ ...filters, payId: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Pay Account No."
                  placeholder="Sender's Account"
                  value={filters.payAccount}
                  onChange={(e) => setFilters({ ...filters, payAccount: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sm={6} md={3}>
                <TextField
                  fullWidth
                  size="small"
                  label="Payee Account No."
                  placeholder="Receiver's Account"
                  value={filters.payeeAccount}
                  onChange={(e) => setFilters({ ...filters, payeeAccount: e.target.value })}
                />
              </Grid>
              <Grid item xs={12} sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
                <Button variant="outlined" color="inherit" onClick={handleResetFilters}>Reset</Button>
                <Button variant="contained" color="primary" onClick={handleApplyFilters}>Query</Button>
              </Grid>
            </Grid>
          </Box>

          <Divider />

          <Box sx={{ p: 2 }}>
            <PlanToolbar
              filterName={filterName}
              onFilterName={handleFilterByName}
              placeholder="Full Search Search by any keyword..."
              onApply={handleApplyFilters}
              onReset={handleResetFilters}
            />
          </Box>

          <Box sx={{ overflowX: 'auto' }}>
            <TableContainer sx={{ minWidth: 1000 }}>
              <Table>
                <TransHead headLabel={TABLE_HEAD} />
                <TableBody>
                  {!load ? (
                    userList.map((row, index) => {
                      return (
                        <TableRow hover key={row.id || index}>
                          <TableCell sx={{ whiteSpace: 'nowrap', fontSize: '0.85rem' }}>{row.plan_date}</TableCell>
                          <TableCell sx={{ whiteSpace: 'nowrap' }}>
                            <Label variant="ghost" color="info" sx={{ textTransform: 'capitalize' }}>
                              {sentenceCase(row.category || 'Payment')}
                            </Label>
                          </TableCell>
                          <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.85rem' }}>{row.transaction_ref || 'N/A'}</TableCell>
                          <TableCell>NGN</TableCell>
                          <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.85rem' }}>{row.reference}</TableCell>
                          <TableCell>
                            <Stack direction="row" alignItems="center" spacing={1}>
                              <Box sx={{
                                width: 8,
                                height: 8,
                                borderRadius: '50%',
                                bgcolor: (row.plan_status === 'success' ? 'success.main' : (row.plan_status === 'pending' ? 'warning.main' : 'error.main'))
                              }} />
                              <Typography variant="subtitle2" sx={{
                                fontWeight: 700,
                                fontSize: '0.85rem',
                                color: (row.plan_status === 'success' ? 'success.main' : (row.plan_status === 'pending' ? 'warning.main' : 'error.main')),
                                textTransform: 'capitalize'
                              }}>
                                {row.plan_status === 'success' ? 'Successful' : (row.plan_status === 'pending' ? 'Processing' : 'Failed')}
                              </Typography>
                            </Stack>
                          </TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>₦{fCurrency(row.amount || 0)}</TableCell>
                          <TableCell>
                            <Label
                              variant="outlined"
                              color={row.settlement_status === 'settled' ? 'success' : 'warning'}
                              sx={{ textTransform: 'capitalize' }}
                            >
                              {row.settlement_status || 'Pending'}
                            </Label>
                          </TableCell>
                          <TableCell sx={{ whiteSpace: 'nowrap', fontSize: '0.85rem' }}>
                            {row.settlement_status === 'settled' ? (row.settlement_time || row.plan_date) : '-'}
                          </TableCell>
                          <TableCell>
                            <Label
                              variant="ghost"
                              color={row.refund_status === 'successful' ? 'success' : 'default'}
                            >
                              {!row.refund_status || row.refund_status === 'not_refunded' ? 'Not Refunded' : sentenceCase(row.refund_status)}
                            </Label>
                          </TableCell>
                          <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.85rem' }}>{row.session_id || 'N/A'}</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>
                            ₦{fCurrency(row.net_amount || ((row.amount || 0) - (row.fee || 0)))}
                          </TableCell>
                          <TableCell>
                            <Stack direction="row" spacing={0.5}>
                              <Tooltip title="View Details">
                                <IconButton size="small" onClick={() => handleViewDetails(row)} sx={{ color: 'primary.main' }}>
                                  <Iconify icon="eva:eye-fill" width={20} height={20} />
                                </IconButton>
                              </Tooltip>
                              <Payment plan_status={row.plan_status} onFail={() => onFail(row.transid)} onSuccess={() => onSuccess(row.transid)} />
                            </Stack>
                          </TableCell>
                        </TableRow>
                      );
                    })
                  ) : (
                    <TableRow>
                      <TableCell align="center" colSpan={13} sx={{ py: 10 }}>
                        <Box sx={{ display: 'flex', justifyContent: 'center' }}>
                          <LogoLoader size={80} />
                        </Box>
                      </TableCell>
                    </TableRow>
                  )}
                  {isNotFound && !load && (
                    <TableRow>
                      <TableCell colSpan={13} align="center" sx={{ py: 10 }}>
                        <SearchNotFound searchQuery={filterName} />
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </TableContainer>
          </Box>

          <TablePagination
            component="div"
            count={totalRecords}
            rowsPerPage={10}
            page={page}
            rowsPerPageOptions={[]}
            onPageChange={(e, pag) => initialize(pag, 10, status, filters)}
            sx={{ borderTop: `1px solid ${theme.palette.divider}` }}
          />
        </StyledCard>

        {/* Transaction Details Modal */}
        <Dialog open={detailsOpen} onClose={handleCloseDetails} maxWidth="md" fullWidth PaperProps={{ sx: { borderRadius: 3, p: 0, bgcolor: '#F4F6F8' } }}>
          <DialogTitle sx={{ bgcolor: '#fff', py: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <Typography variant="h6" sx={{ fontWeight: 800 }}>Transaction Detail</Typography>
            <IconButton onClick={handleCloseDetails} size="small"><Iconify icon="eva:close-fill" /></IconButton>
          </DialogTitle>
          <DialogContent sx={{ p: 3 }}>
            {selectedTransaction && (
              <Grid container spacing={3}>
                {/* 1. Order Information */}
                <Grid item xs={12} md={6}>
                  <Card sx={{ p: 2, borderRadius: 2, height: '100%' }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 800, mb: 2, color: 'primary.main', display: 'flex', alignItems: 'center' }}>
                      <Iconify icon="eva:shopping-cart-fill" sx={{ mr: 1 }} /> Order Information
                    </Typography>
                    <Stack spacing={1.5}>
                      <DetailRow label="Transaction Status" value={
                        <Stack direction="row" alignItems="center" spacing={1}>
                          <Box sx={{
                            width: 8,
                            height: 8,
                            borderRadius: '50%',
                            bgcolor: (selectedTransaction.plan_status === 'success' ? 'success.main' : (selectedTransaction.plan_status === 'pending' ? 'warning.main' : 'error.main'))
                          }} />
                          <Typography variant="subtitle2" sx={{ fontWeight: 700, textTransform: 'capitalize' }}>
                            {selectedTransaction.plan_status === 'success' ? 'Successful' : (selectedTransaction.plan_status === 'pending' ? 'Processing' : 'Failed')}
                          </Typography>
                        </Stack>
                      } />
                      <DetailRow label="Transaction Order No." value={selectedTransaction.reference} isCopyable />
                      <DetailRow label="Merchant Order No." value={selectedTransaction.transaction_ref || 'N/A'} isCopyable />
                      <DetailRow label="Order Amount" value={`₦${fCurrency(selectedTransaction.amount || 0)}`} />
                      <DetailRow label="Service Fee" value={`₦${fCurrency(selectedTransaction.fee || 0)}`} />
                      <DetailRow label="Settlement Amount" value={`₦${fCurrency(selectedTransaction.net_amount || ((selectedTransaction.amount || 0) - (selectedTransaction.fee || 0)))}`} />
                      <DetailRow label="Merchant Name" value={selectedTransaction.merchant_display} />
                      <DetailRow label="Create Time" value={selectedTransaction.plan_date} />
                    </Stack>
                  </Card>
                </Grid>

                {/* 2. Payer Information */}
                <Grid item xs={12} md={6}>
                  <Card sx={{ p: 2, borderRadius: 2, height: '100%' }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 800, mb: 2, color: 'info.main', display: 'flex', alignItems: 'center' }}>
                      <Iconify icon="eva:person-fill" sx={{ mr: 1 }} /> Payer Information
                    </Typography>
                    <Stack spacing={1.5}>
                      <DetailRow label="Payer Name" value={selectedTransaction.phone_account || 'N/A'} />
                      <DetailRow label="Payer Bank" value={selectedTransaction.payer_bank || 'N/A'} />
                      <DetailRow label="Payer Account No." value={selectedTransaction.payer_account || 'N/A'} isCopyable />
                      <DetailRow label="Payment ID (Session)" value={selectedTransaction.session_id || 'N/A'} isCopyable />
                    </Stack>
                  </Card>
                </Grid>

                {/* 3. Payee Information */}
                <Grid item xs={12} md={6}>
                  <Card sx={{ p: 2, borderRadius: 2, height: '100%' }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 800, mb: 2, color: 'warning.main', display: 'flex', alignItems: 'center' }}>
                      <Iconify icon="eva:account-book-fill" sx={{ mr: 1 }} /> Payee Information
                    </Typography>
                    <Stack spacing={1.5}>
                      <DetailRow label="Payee Name" value={selectedTransaction.payee_account_name || 'PointWave Technology'} />
                      <DetailRow label="Payee Bank" value={selectedTransaction.payee_bank || 'PalmPay'} />
                      <DetailRow label="Payee Account No." value={selectedTransaction.payee_account || 'N/A'} isCopyable />
                    </Stack>
                  </Card>
                </Grid>

                {/* 4. Settlement Information */}
                <Grid item xs={12} md={6}>
                  <Card sx={{ p: 2, borderRadius: 2, height: '100%' }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 800, mb: 2, color: 'success.main', display: 'flex', alignItems: 'center' }}>
                      <Iconify icon="eva:checkmark-circle-2-fill" sx={{ mr: 1 }} /> Settlement Information
                    </Typography>
                    <Stack spacing={1.5}>
                      <DetailRow label="Settlement Status" value={
                        <Label color={selectedTransaction.settlement_status === 'settled' ? 'success' : 'warning'} variant="outlined">
                          {(selectedTransaction.settlement_status || 'Pending').toUpperCase()}
                        </Label>
                      } />
                      <DetailRow label="Settlement Time" value={selectedTransaction.settlement_status === 'settled' ? (selectedTransaction.settlement_time || selectedTransaction.plan_date) : '-'} />
                      <DetailRow label="Refund Status" value={
                        <Label color={selectedTransaction.refund_status === 'successful' ? 'success' : 'default'} variant="ghost">
                          {!selectedTransaction.refund_status || selectedTransaction.refund_status === 'not_refunded' ? 'Not Refunded' : sentenceCase(selectedTransaction.refund_status)}
                        </Label>
                      } />
                      <DetailRow label="Dispute Status" value={selectedTransaction.dispute_status || 'NONE'} />
                    </Stack>
                  </Card>
                </Grid>
              </Grid>
            )}
          </DialogContent>
          <DialogActions sx={{ px: 3, pb: 3, pt: 2, bgcolor: '#fff', borderTop: '1px solid #eee' }}>
            <Button
              variant="outlined"
              color="error"
              startIcon={<Iconify icon="eva:undo-fill" />}
              onClick={() => {
                swal({
                  title: "Confirm Manual Refund?",
                  text: `Action will refund ₦${fCurrency(selectedTransaction.amount || 0)} to the user's balance. This cannot be undone.`,
                  icon: "warning",
                  buttons: ["Cancel", "Yes, Refund"],
                  dangerMode: true,
                }).then((willRefund) => {
                  if (willRefund) {
                    onFail(selectedTransaction.reference);
                  }
                });
              }}
              sx={{ borderRadius: 1, fontWeight: 700 }}
              disabled={selectedTransaction?.refund_status === 'successful'}
            >
              Manual Refund
            </Button>
            <Button
              variant="outlined"
              color="info"
              startIcon={<Iconify icon="eva:bell-fill" />}
              onClick={() => {
                swal({
                  title: "Resend Notification?",
                  text: "This will force-credit the merchant and mark the transaction as successful.",
                  icon: "info",
                  buttons: ["Cancel", "Yes, Notify"],
                }).then((willNotify) => {
                  if (willNotify) {
                    onSuccess(selectedTransaction.reference);
                  }
                });
              }}
              sx={{ borderRadius: 1, fontWeight: 700 }}
            >
              Resend Notification
            </Button>
            <Box sx={{ flexGrow: 1 }} />
            <Button onClick={handleCloseDetails} variant="contained" sx={{ borderRadius: 1.5, px: 4, fontWeight: 700 }}>
              Close
            </Button>
          </DialogActions>
        </Dialog>
      </Container>
    </Page>
  );
}
