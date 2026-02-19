/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable no-restricted-globals */
/* eslint-disable camelcase */
import { sentenceCase, capitalCase } from 'change-case';
import { useState, useEffect } from 'react';
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
  Tabs,
  Tab,
  Box,
  Stack,
  Divider,
  CircularProgress,
  IconButton,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Grid
} from '@mui/material';

// routes
import { PATH_ADMIN } from '../../../routes/paths';
// hooks
import useSettings from '../../../hooks/useSettings';
// components
import Page from '../../../components/Page';
import Label from '../../../components/Label';
import Scrollbar from '../../../components/Scrollbar';
import SearchNotFound from '../../../components/SearchNotFound';
import Iconify from '../../../components/Iconify';

// format number 
import { fCurrency } from '../../../utils/formatNumber';
// sections
import { TransHead, PlanToolbar, Payment } from '../../../sections/admin/user/list';

// axios
import axios from '../../../utils/axios';

// ----------------------------------------------------------------------

const HeaderCardStyle = styled(Card)(({ theme }) => ({
  padding: theme.spacing(4),
  background: 'linear-gradient(135deg, #1E1B4B 0%, #312E81 100%)',
  borderRadius: 24,
  border: 'none',
  boxShadow: '0 20px 40px 0 rgba(30, 27, 75, 0.2)',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  marginBottom: theme.spacing(4),
  color: '#fff',
}));

const StyledCard = styled(Card)(({ theme }) => ({
  borderRadius: 20,
  border: `1px solid ${theme.palette.divider}`,
  boxShadow: 'none',
  backgroundColor: '#fff',
  overflow: 'hidden',
}));

const TABLE_HEAD = [
  { id: 'username', label: 'Merchant/User', alignRight: false },
  { id: 'referrence', label: 'Ref ID', alignRight: false },
  { id: 'description', label: 'Service Details', alignRight: false },
  { id: 'phone', label: 'Beneficiary', alignRight: false },
  { id: 'amount', label: 'Volume', alignRight: false },
  { id: 'date', label: 'Timestamp', alignRight: false },
  { id: 'plan_status', label: 'Fulfillment Status', alignRight: false },
  { id: 'actions', label: 'Actions', alignRight: false },
];

const STATUS_TABS = [
  { value: 'ALL', label: 'All Traffic' },
  { value: 'success', label: 'Success' },
  { value: 'pending', label: 'Processing' },
  { value: 'failed', label: 'Failed' }
];

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
  const [selectedTransaction, setSelectedTransaction] = useState(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const AccessToken = window.localStorage.getItem('accessToken');
  const isNotFound = userList.length === 0 && !load;

  useEffect(() => {
    initialize(page, 10, status, filterName);
  }, []);

  const initialize = async (pag, limit = 10, statusVal, search) => {
    const api_page = pag + 1;
    setLoad(true);
    try {
      const response = await axios.get(`/api/admin/all/transaction/history/${AccessToken}/secure?page=${api_page}&limit=${limit}&status=${statusVal}&search=${search}`);
      setUserList(response.data?.all_summary?.data || []);
      setTotalRecords(response.data?.all_summary?.total || 0);
      setLoad(false);
      setPage(pag);
    } catch (_) {
      setLoad(false);
      setUserList([]);
    }
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

  const { enqueueSnackbar } = useSnackbar();

  const onFail = (reference, action = 'refund') => {
    setLoad(true)
    axios.post(`/api/admin/trans/action/${AccessToken}/secure`, {
      action: action,
      reference: reference
    }).then((res) => {
      setLoad(false)
      enqueueSnackbar(res.data.message || 'Action Completed')
      initialize(page, 10, status, filterName);
    }).catch((error) => {
      setLoad(false)
      initialize(page, 10, status, filterName);
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
      initialize(page, 10, status, filterName);
    }).catch((error) => {
      setLoad(false)
      initialize(page, 10, status, filterName);
      const message = error.response?.data?.message || error.message || 'An Error Occurred';
      enqueueSnackbar(message, { variant: 'error' })
    })
  }

  return (
    <Page title="Admin: Transaction Hub">
      <Container maxWidth={themeStretch ? false : 'xl'}>
        <HeaderCardStyle>
          <Box>
            <Typography variant="h3" sx={{ fontWeight: 900, mb: 1 }}>
              Global Transactions
            </Typography>
            <Typography variant="subtitle2" sx={{ opacity: 0.8, fontWeight: 600 }}>
              Monitor system-wide payment traffic and service fulfillment
            </Typography>
          </Box>
          <Iconify icon="eva:activity-fill" width={64} height={64} sx={{ opacity: 0.2 }} />
        </HeaderCardStyle>

        <StyledCard>
          <Box sx={{ px: 2, pt: 2, bgcolor: '#F9FAFB' }}>
            <Tabs
              value={status}
              onChange={(e, value) => {
                setStatus(value);
                initialize(0, 10, value, filterName);
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

          <Box sx={{ p: 2 }}>
            <PlanToolbar
              filterName={filterName}
              onFilterName={handleFilterByName}
              placeholder="Search by username, reference, or phone..."
            />
          </Box>

          <Scrollbar>
            <TableContainer sx={{ minWidth: 1000 }}>
              <Table>
                <TableBody>
                  <TableRow sx={{ bgcolor: '#F4F6F8' }}>
                    {TABLE_HEAD.map((head) => (
                      <TableCell key={head.id} sx={{ fontWeight: 800, color: 'text.secondary', py: 2 }}>
                        {head.label}
                      </TableCell>
                    ))}
                  </TableRow>
                  {!load ? (
                    userList.map((row, index) => {
                      const beneficiary = row.phone || row.phone_account || row.plan_phone || row.phone_number || row.iuc || row.meter_number || 'N/A';
                      const details = row.message || row.plan_name || row.name || row.exam_name || row.disco_name || row.bank_name || row.cable_name || 'Service Purchase';
                      const merchantName = row.merchant_display || row.business_name || row.company_name || row.username || 'N/A';

                      return (
                        <TableRow hover key={row.transid || index}>
                          <TableCell>
                            <Typography variant="subtitle2" sx={{ fontWeight: 800 }}>{merchantName}</Typography>
                            {row.customer_name && (
                              <Typography variant="caption" sx={{ color: 'text.disabled', display: 'block' }}>
                                Customer: {row.customer_name}
                              </Typography>
                            )}
                          </TableCell>
                          <TableCell>
                            <Typography variant="caption" sx={{ color: 'text.secondary', fontWeight: 600 }}>{row.transid}</Typography>
                          </TableCell>
                          <TableCell>
                            <Typography variant="body2" sx={{ fontWeight: 600 }}>{details}</Typography>
                          </TableCell>
                          <TableCell sx={{ fontWeight: 600 }}>{beneficiary}</TableCell>
                          <TableCell>
                            <Box>
                              <Typography variant="subtitle2" sx={{ fontWeight: 800, color: 'primary.main' }}>₦{fCurrency(row.amount)}</Typography>
                              <Typography variant="caption" sx={{ color: 'text.disabled' }}>{fCurrency(row.oldbal)} → {fCurrency(row.newbal)}</Typography>
                            </Box>
                          </TableCell>
                          <TableCell sx={{ color: 'text.secondary', fontWeight: 600, whiteSpace: 'nowrap' }}>
                            {row.plan_date || row.created_at || row.date || 'N/A'}
                          </TableCell>
                          <TableCell>
                            <Label
                              variant="soft"
                              color={(row.plan_status === 'success' ? 'success' : (row.plan_status === 'pending' ? 'info' : 'error'))}
                              sx={{
                                textTransform: 'uppercase',
                                fontWeight: 800,
                                px: 1.2,
                                fontSize: '0.75rem',
                                borderRadius: 0.75
                              }}
                            >
                              {row.plan_status === 'success' ? 'SUCCESSFUL' : (row.plan_status === 'pending' ? 'PROCESSING' : 'FAILED')}
                            </Label>
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
                      <TableCell colSpan={8} align="center" sx={{ py: 10 }}>
                        <CircularProgress />
                      </TableCell>
                    </TableRow>
                  )}
                  {isNotFound && !load && (
                    <TableRow>
                      <TableCell colSpan={8} align="center" sx={{ py: 10 }}>
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
            rowsPerPage={10}
            page={page}
            rowsPerPageOptions={[]}
            onPageChange={(e, pag) => initialize(pag, 10, status, filterName)}
            sx={{ borderTop: `1px solid ${theme.palette.divider}` }}
          />
        </StyledCard>

        {/* Transaction Details Modal */}
        <Dialog open={detailsOpen} onClose={handleCloseDetails} maxWidth="sm" fullWidth PaperProps={{ sx: { borderRadius: 3, p: 1 } }}>
          <DialogContent>
            {selectedTransaction && (
              <Box sx={{ p: 2 }}>
                <Box sx={{ textAlign: 'center', mb: 4, pb: 3, borderBottom: `2px dashed ${theme.palette.divider}` }}>
                  <Typography variant="h5" sx={{ fontWeight: 900, mb: 0.5 }}>Transaction Receipt</Typography>
                  <Typography variant="caption" sx={{ color: 'text.secondary', fontWeight: 600, fontFamily: 'monospace' }}>
                    {selectedTransaction.transid}
                  </Typography>

                  <Box sx={{ mt: 3, display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                    <Typography variant="h3" sx={{ fontWeight: 900, color: 'text.primary' }}>
                      ₦{fCurrency(selectedTransaction.amount)}
                    </Typography>
                    <Label
                      color={(selectedTransaction.plan_status === 'success' ? 'success' : (selectedTransaction.plan_status === 'pending' ? 'info' : 'error'))}
                      sx={{ mt: 1, px: 2, fontWeight: 800, textTransform: 'uppercase' }}
                    >
                      {selectedTransaction.plan_status === 'success' ? 'SUCCESSFUL' : (selectedTransaction.plan_status === 'pending' ? 'PROCESSING' : 'FAILED')}
                    </Label>
                  </Box>
                </Box>

                <Stack spacing={2.5}>
                  <Box>
                    <Typography variant="caption" sx={{ fontWeight: 800, color: 'text.secondary', textTransform: 'uppercase', letterSpacing: 1.1 }}>
                      Merchant Info
                    </Typography>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Username</Typography>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>{selectedTransaction.username || 'N/A'}</Typography>
                    </Stack>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Company</Typography>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>{selectedTransaction.business_name || selectedTransaction.company_name || 'Personal Account'}</Typography>
                    </Stack>
                    {selectedTransaction.customer_name && (
                      <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                        <Typography variant="body2" color="text.secondary">Customer</Typography>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>{selectedTransaction.customer_name}</Typography>
                      </Stack>
                    )}
                  </Box>

                  <Box>
                    <Typography variant="caption" sx={{ fontWeight: 800, color: 'text.secondary', textTransform: 'uppercase', letterSpacing: 1.1 }}>
                      Service Details
                    </Typography>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Description</Typography>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, textAlign: 'right' }}>{selectedTransaction.message || selectedTransaction.description}</Typography>
                    </Stack>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Beneficiary</Typography>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, fontFamily: 'monospace' }}>
                        {selectedTransaction.phone || selectedTransaction.phone_account || selectedTransaction.customer_phone || 'N/A'}
                      </Typography>
                    </Stack>
                    {(selectedTransaction.phone_account || selectedTransaction.customer_name) && (
                      <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                        <Typography variant="body2" color="text.secondary">Beneficiary Name</Typography>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                          {selectedTransaction.phone_account || selectedTransaction.customer_name}
                        </Typography>
                      </Stack>
                    )}
                  </Box>

                  <Box>
                    <Typography variant="caption" sx={{ fontWeight: 800, color: 'text.secondary', textTransform: 'uppercase', letterSpacing: 1.1 }}>
                      Payment Trace
                    </Typography>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Reference</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 700, fontFamily: 'monospace' }}>{selectedTransaction.reference || 'N/A'}</Typography>
                    </Stack>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Wallet Balance</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 700 }}>₦{fCurrency(selectedTransaction.oldbal)} → ₦{fCurrency(selectedTransaction.newbal)}</Typography>
                    </Stack>
                    <Stack direction="row" justifyContent="space-between" sx={{ mt: 1 }}>
                      <Typography variant="body2" color="text.secondary">Timestamp</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 700 }}>{selectedTransaction.plan_date || selectedTransaction.created_at}</Typography>
                    </Stack>
                  </Box>
                </Stack>

                <Box sx={{ mt: 4, pt: 3, borderTop: `1px solid ${theme.palette.divider}`, textAlign: 'center' }}>
                  <Typography variant="caption" color="text.disabled">
                    Internal Administrative Receipt
                  </Typography>
                </Box>
              </Box>
            )}
          </DialogContent>
          <DialogActions sx={{ px: 3, pb: 2 }}>
            <Button onClick={handleCloseDetails} fullWidth variant="contained" size="large" sx={{ borderRadius: 1.5, fontWeight: 700 }}>
              Close Receipt
            </Button>
          </DialogActions>
        </Dialog>
      </Container>
    </Page>
  );
}
