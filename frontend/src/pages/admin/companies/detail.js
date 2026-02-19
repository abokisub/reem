/* eslint-disable react-hooks/exhaustive-deps */
import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

// @mui
import { styled } from '@mui/material/styles';
import {
    Card,
    Container,
    Typography,
    Box,
    Stack,
    Grid,
    Button,
    Chip,
    Divider,
    CircularProgress,
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    TextField,
    Alert,
    IconButton,
    Avatar,
    ListItem,
    ListItemText,
    ListItemAvatar,
    List,
    Switch,
    FormControlLabel
} from '@mui/material';

// hooks
import useSettings from '../../../hooks/useSettings';
// components
import Page from '../../../components/Page';
import Label from '../../../components/Label';
import Iconify from '../../../components/Iconify';
import Image from '../../../components/Image';
import { useSnackbar } from 'notistack';

// axios
import axios from '../../../utils/axios';

// ----------------------------------------------------------------------

const HeaderCardStyle = styled(Card)(({ theme }) => ({
    padding: theme.spacing(3),
    marginBottom: theme.spacing(3),
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    [theme.breakpoints.down('sm')]: {
        flexDirection: 'column',
        alignItems: 'flex-start',
        gap: theme.spacing(2)
    }
}));

const DocumentCard = styled(Card)(({ theme, status }) => {
    let borderColor = theme.palette.divider;
    let bgColor = theme.palette.background.paper;

    if (status === 'verified' || status === 'approved') {
        borderColor = theme.palette.success.main;
        bgColor = theme.palette.success.lighter;
    } else if (status === 'rejected') {
        borderColor = theme.palette.error.main;
        bgColor = theme.palette.error.lighter;
    }

    return {
        padding: theme.spacing(2),
        border: `1px solid ${borderColor}`,
        backgroundColor: bgColor,
        transition: 'all 0.3s ease',
        '&:hover': {
            boxShadow: theme.shadows[10]
        }
    };
});

// ----------------------------------------------------------------------

export default function CompanyDetail() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { themeStretch } = useSettings();
    const { enqueueSnackbar } = useSnackbar();

    const [company, setCompany] = useState(null);
    const [user, setUser] = useState(null);
    const [documents, setDocuments] = useState([]);
    const [ownerBvn, setOwnerBvn] = useState(null);
    const [loading, setLoading] = useState(true);

    // Review Dialog State
    const [openDialog, setOpenDialog] = useState(false);
    const [selectedDoc, setSelectedDoc] = useState(null);
    const [actionType, setActionType] = useState(null); // 'approved' | 'rejected'
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    // Image Preview State
    const [previewImage, setPreviewImage] = useState(null);
    const [viewDataDoc, setViewDataDoc] = useState(null);


    // Edit Profile State
    const [openEditDialog, setOpenEditDialog] = useState(false);
    const [editForm, setEditForm] = useState({
        business_name: '',
        email: '',
        phone: '',
        rc_number: '',
        password: '',
        pin: ''
    });
    const [editSubmitting, setEditSubmitting] = useState(false);

    // Status Toggle State
    const [statusSubmitting, setStatusSubmitting] = useState(false);

    // Delete State
    const [openDeleteDialog, setOpenDeleteDialog] = useState(false);
    const [deleteSubmitting, setDeleteSubmitting] = useState(false);

    useEffect(() => {
        const fetchDetails = async () => {
            try {
                // Using the NEW endpoint for granular details
                const response = await axios.get(`/api/system/admin/company/verification/${id}`, {
                    params: {
                        admin_id: window.localStorage.getItem('accessToken')
                    }
                });
                if (response.data.status === 200) {
                    setCompany(response.data.company);
                    setUser(response.data.user);
                    setDocuments(response.data.documents);
                    setOwnerBvn(response.data.owner_bvn);
                } else {
                    enqueueSnackbar(response.data.message || 'Failed to load details', { variant: 'error' });
                }
            } catch (error) {
                console.error(error);
                enqueueSnackbar('Network error fetching details', { variant: 'error' });
            } finally {
                setLoading(false);
            }
        };

        fetchDetails();
    }, [id]);

    const handleReviewClick = (doc, action) => {
        setSelectedDoc(doc);
        setActionType(action);
        setReason(''); // Reset reason
        setOpenDialog(true);
    };

    const submitReview = async () => {
        if (actionType === 'rejected' && !reason) {
            enqueueSnackbar('Please enter a rejection reason', { variant: 'warning' });
            return;
        }

        setSubmitting(true);
        try {
            const payload = {
                document_id: selectedDoc.id,
                status: actionType,
                reason: reason,
                admin_id: window.localStorage.getItem('accessToken')
            };

            const response = await axios.post('/api/system/admin/company/document/review', payload);

            if (response.data.status === 'success') {
                enqueueSnackbar(response.data.message, { variant: 'success' });

                // Update local state
                setDocuments(docs => docs.map(d =>
                    d.id === selectedDoc.id ? { ...d, status: actionType, rejection_reason: reason } : d
                ));

                // Update company status if it changed
                if (response.data.company_status) {
                    setCompany(prev => ({ ...prev, kyc_status: response.data.company_status }));
                }

                setOpenDialog(false);
            } else {
                enqueueSnackbar('Failed to update status', { variant: 'error' });
            }
        } catch (error) {
            console.error(error);
            enqueueSnackbar('Error submitting review', { variant: 'error' });
        } finally {
            setSubmitting(false);
        }
    };

    const handleEditClick = () => {
        setEditForm({
            business_name: company.name || '',
            email: company.email || '',
            phone: company.phone_number || user?.phone_number || user?.phone || '',
            rc_number: company.rc_number || '',
            password: '',
            pin: ''
        });
        setOpenEditDialog(true);
    };

    const submitEditProfile = async () => {
        setEditSubmitting(true);
        try {
            const payload = {
                id: window.localStorage.getItem('accessToken'), // or wherever it's stored
                company_id: company.id,
                ...editForm
            };

            const response = await axios.post('/api/system/admin/company/update-profile/secure', payload);

            if (response.data.status === 'success') {
                enqueueSnackbar('Profile updated successfully', { variant: 'success' });
                setCompany(prev => ({
                    ...prev,
                    name: editForm.business_name,
                    email: editForm.email,
                    phone_number: editForm.phone,
                    rc_number: editForm.rc_number
                }));
                setOpenEditDialog(false);
            } else {
                enqueueSnackbar(response.data.message || 'Update failed', { variant: 'error' });
            }
        } catch (error) {
            console.error(error);
            enqueueSnackbar('Error updating profile', { variant: 'error' });
        } finally {
            setEditSubmitting(false);
        }
    };

    const submitStatusChange = async (newStatus) => {
        setStatusSubmitting(true);
        try {
            const payload = {
                id: window.localStorage.getItem('accessToken'),
                company_id: company.id,
                status: newStatus
            };

            const response = await axios.post('/api/system/admin/company/toggle-status/secure', payload);

            if (response.data.status === 'success') {
                enqueueSnackbar(response.data.message, { variant: 'success' });
                setCompany(prev => ({ ...prev, status: newStatus }));
            } else {
                enqueueSnackbar(response.data.message || 'Status toggle failed', { variant: 'error' });
            }
        } catch (error) {
            console.error(error);
            enqueueSnackbar('Error toggling status', { variant: 'error' });
        } finally {
            setStatusSubmitting(false);
        }
    };

    const handleDeleteClick = () => {
        setOpenDeleteDialog(true);
    };

    const submitDelete = async () => {
        setDeleteSubmitting(true);
        try {
            const response = await axios.delete(`/api/system/admin/company/${company.id}`, {
                params: {
                    admin_id: window.localStorage.getItem('accessToken')
                }
            });

            if (response.data.status === 'success') {
                enqueueSnackbar('Company deleted successfully', { variant: 'success' });
                navigate('/secure/companies', { replace: true });
            } else {
                enqueueSnackbar(response.data.message || 'Deletion failed', { variant: 'error' });
            }
        } catch (error) {
            console.error(error);
            enqueueSnackbar(error.response?.data?.message || 'Error deleting company', { variant: 'error' });
        } finally {
            setDeleteSubmitting(false);
            setOpenDeleteDialog(false);
        }
    };

    const handleSwitchToggle = () => {
        submitStatusChange(company.status === 'suspended' ? 'active' : 'suspended');
    };

    const getStatusLabel = (status) => {
        switch (status) {
            case 'verified':
            case 'approved': return <Label color="success">Verified</Label>;
            case 'rejected': return <Label color="error">Rejected</Label>;
            default: return <Label color="warning">Pending</Label>;
        }
    };

    const getDocLabel = (type) => {
        const labels = {
            cac: 'CAC Certificate',
            director_id: 'Director ID',
            utility_bill: 'Utility Bill',
            business_data: 'Business Data Verification',
            address_data: 'Address Verification',
            owner_data: 'Owner Info Verification',
            account_data: 'Account Detail Verification',
            bvn_data: 'BVN Verification Data'
        };
        return labels[type] || type.replace('_', ' ').toUpperCase();

    };

    const getDocUrl = (path) => {
        if (!path) return '';
        if (path.startsWith('http')) return path;

        // If the path starts with 'kyc_documents/', it's served via the kyc_documents link
        if (path.startsWith('kyc_documents/')) {
            return `/${path}`;
        }

        // Remove 'public/' if present and prepend '/storage/'
        const cleanPath = path.replace(/^public\//, '');
        return `/storage/${cleanPath}`;
    };

    if (loading) return <CircularProgress sx={{ display: 'block', mx: 'auto', mt: 10 }} />;
    if (!company) return <Alert severity="error">Company not found</Alert>;

    return (
        <Page title="Company Verification">
            <Container maxWidth={themeStretch ? false : 'xl'}>

                {/* HEADLINE */}
                <HeaderCardStyle>
                    <Box>
                        <Stack direction="row" alignItems="center" spacing={2}>
                            <Typography variant="h3">{company.name || 'No Name'}</Typography>
                            <Label color={company.kyc_status === 'verified' ? 'success' : company.kyc_status === 'rejected' ? 'error' : 'warning'}>
                                {company.kyc_status?.toUpperCase() || 'PENDING'}
                            </Label>
                            <Label color={company.status === 'suspended' ? 'error' : 'info'}>
                                {company.status === 'suspended' ? 'Account Banned / Locked' : 'Live'}
                            </Label>
                        </Stack>
                        <Stack direction="row" alignItems="center" spacing={1} sx={{ mt: 0.5 }}>
                            <Typography variant="body2" sx={{ color: 'text.secondary' }}>
                                RC: {company.rc_number} | Submitted: {new Date(company.created_at).toLocaleDateString()}
                            </Typography>
                            <Divider orientation="vertical" flexItem sx={{ mx: 1 }} />
                            <FormControlLabel
                                control={
                                    <Switch
                                        size="small"
                                        color="error"
                                        checked={company.status === 'suspended'}
                                        onChange={handleSwitchToggle}
                                        disabled={statusSubmitting}
                                    />
                                }
                                label={company.status === 'suspended' ? "Frozen" : "Account Active"}
                            />
                        </Stack>
                    </Box>
                    <Stack direction="row" spacing={1}>
                        <Button variant="contained" color="primary" startIcon={<Iconify icon="eva:edit-fill" />} onClick={handleEditClick}>
                            Edit Profile
                        </Button>
                        {company.kyc_status === 'verified' && company.status !== 'active' && (
                            <Button variant="contained" color="success" startIcon={<Iconify icon="eva:checkmark-circle-2-fill" />} onClick={() => submitStatusChange('active')} disabled={statusSubmitting}>
                                Activate Business
                            </Button>
                        )}
                        <Button variant="outlined" startIcon={<Iconify icon="eva:arrow-back-fill" />} onClick={() => navigate(-1)}>
                            Back
                        </Button>
                        <Button variant="outlined" color="error" startIcon={<Iconify icon="eva:trash-2-fill" />} onClick={handleDeleteClick}>
                            Delete
                        </Button>
                    </Stack>
                </HeaderCardStyle>

                <Grid container spacing={3}>

                    {/* LEFT COLUMN: COMPANY INFO */}
                    <Grid item xs={12} md={4}>
                        <Stack spacing={3}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 2, fontWeight: 700 }}>Company Information</Typography>
                                <Stack spacing={2}>
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Email:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.email}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Phone:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.phone_number || user?.phone_number || user?.phone || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Box>
                                        <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Address:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.address || 'No Address Provided'}</Typography>
                                    </Box>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">State/LGA:</Typography>
                                        <Typography variant="body2">{company.state || user?.state || 'N/A'} / {company.lga || user?.lga || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Box>
                                        <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Category:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.business_category || user?.business_category || 'N/A'}</Typography>
                                    </Box>
                                    <Divider />
                                    <Box>
                                        <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>Description:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.description || user?.description || 'N/A'}</Typography>
                                    </Box>
                                </Stack>
                            </Card>

                            {/* OWNER INFORMATION */}
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 2, fontWeight: 700 }}>Owner Information</Typography>
                                <Stack spacing={2}>
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Full Name:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{user ? `${user.first_name} ${user.last_name}` : 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Phone:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{user?.phone_number || user?.phone || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">BVN:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{user?.bvn || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">NIN:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{user?.nin || 'N/A'}</Typography>
                                    </Stack>
                                </Stack>

                            </Card>

                            {/* ACCOUNT INFORMATION */}
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 2, fontWeight: 700 }}>Account Information</Typography>
                                <Stack spacing={2}>
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Bank Name:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.bank_name || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Account Number:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.account_number || 'N/A'}</Typography>
                                    </Stack>
                                    <Divider />
                                    <Stack direction="row" justifyContent="space-between">
                                        <Typography variant="body2" color="text.secondary">Account Name:</Typography>
                                        <Typography variant="body2" fontWeight="bold">{company.account_name || 'N/A'}</Typography>
                                    </Stack>
                                </Stack>
                            </Card>
                        </Stack>
                    </Grid>

                    {/* RIGHT COLUMN: DOCUMENTS */}
                    <Grid item xs={12} md={8}>
                        <Typography variant="h5" sx={{ mb: 2, fontWeight: 700 }}>Documents Verification</Typography>

                        <Stack spacing={2}>
                            {documents.length === 0 ? (
                                <Alert severity="info">No documents uploaded yet.</Alert>
                            ) : (
                                documents.map((doc) => (
                                    <DocumentCard key={doc.id} status={doc.status}>
                                        <Grid container alignItems="center" spacing={2}>
                                            <Grid item xs={12} sm={2}>
                                                {/* Thumbnail / Icon */}
                                                <Box
                                                    sx={{
                                                        width: 64, height: 64, borderRadius: 1, overflow: 'hidden',
                                                        bgcolor: 'grey.200', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: doc.file_path !== 'virtual' ? 'pointer' : 'default'
                                                    }}
                                                    onClick={() => doc.file_path !== 'virtual' && setPreviewImage(getDocUrl(doc.file_path))}
                                                >
                                                    <Iconify icon={doc.file_path === 'virtual' ? "eva:file-text-fill" : "eva:image-fill"} width={32} height={32} color="grey" />
                                                </Box>
                                            </Grid>

                                            <Grid item xs={12} sm={5}>
                                                <Typography variant="subtitle1" fontWeight="bold">
                                                    {getDocLabel(doc.document_type)}
                                                </Typography>
                                                <Box sx={{ mt: 0.5 }}>{getStatusLabel(doc.status)}</Box>
                                                {doc.rejection_reason && (
                                                    <Typography variant="caption" color="error" sx={{ display: 'block', mt: 0.5 }}>
                                                        Reason: {doc.rejection_reason}
                                                    </Typography>
                                                )}
                                            </Grid>

                                            <Grid item xs={12} sm={5} sx={{ textAlign: 'right' }}>
                                                <Stack direction="row" spacing={1} justifyContent="flex-end">
                                                    {doc.file_path !== 'virtual' ? (
                                                        <Button variant="outlined" size="small" startIcon={<Iconify icon="eva:eye-fill" />} onClick={() => setPreviewImage(getDocUrl(doc.file_path))}>
                                                            Preview
                                                        </Button>
                                                    ) : (
                                                        <Button variant="outlined" size="small" startIcon={<Iconify icon="eva:file-text-fill" />} onClick={() => setViewDataDoc(doc)}>
                                                            View Data
                                                        </Button>
                                                    )}

                                                    {doc.status !== 'approved' && (
                                                        <Button
                                                            variant="contained"
                                                            color="success"
                                                            size="small"
                                                            startIcon={<Iconify icon="eva:checkmark-fill" />}
                                                            onClick={() => handleReviewClick(doc, 'approved')}
                                                        >
                                                            Approve
                                                        </Button>
                                                    )}

                                                    {doc.status !== 'rejected' && (
                                                        <Button
                                                            variant="outlined"
                                                            color="error"
                                                            size="small"
                                                            startIcon={<Iconify icon="eva:close-fill" />}
                                                            onClick={() => handleReviewClick(doc, 'rejected')}
                                                        >
                                                            Reject
                                                        </Button>
                                                    )}
                                                </Stack>
                                            </Grid>
                                        </Grid>
                                    </DocumentCard>
                                ))
                            )}
                        </Stack>
                    </Grid>
                </Grid>

                {/* APPROVE/REJECT DIALOG */}
                <Dialog open={openDialog} onClose={() => setOpenDialog(false)}>
                    <DialogTitle>
                        {actionType === 'approved' ? 'Approve Document' : 'Reject Document'}
                    </DialogTitle>
                    <DialogContent>
                        <Box sx={{ pt: 1 }}>
                            {actionType === 'approved' ? (
                                <Typography>Are you sure you want to approve this document?</Typography>
                            ) : (
                                <TextField
                                    autoFocus
                                    margin="dense"
                                    label="Rejection Reason"
                                    type="text"
                                    fullWidth
                                    multiline
                                    rows={3}
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="E.g. Document is blurred or expired."
                                    variant="outlined"
                                    error={submitting && !reason}
                                    helperText={submitting && !reason ? "Reason is required" : ""}
                                />
                            )}
                        </Box>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setOpenDialog(false)} color="inherit">Cancel</Button>
                        <Button onClick={submitReview} variant="contained" color={actionType === 'approved' ? 'success' : 'error'} disabled={submitting}>
                            {submitting ? 'Processing...' : 'Confirm'}
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* IMAGE PREVIEW DIALOG */}
                <Dialog open={!!previewImage} onClose={() => setPreviewImage(null)} maxWidth="lg">
                    <Box sx={{ position: 'relative', bgcolor: 'black' }}>
                        <IconButton
                            onClick={() => setPreviewImage(null)}
                            sx={{ position: 'absolute', top: 5, right: 5, color: 'white', bgcolor: 'rgba(0,0,0,0.5)' }}
                        >
                            <Iconify icon="eva:close-fill" />
                        </IconButton>
                        <img src={previewImage} alt="Preview" style={{ maxWidth: '100%', maxHeight: '90vh', display: 'block' }} />
                    </Box>
                </Dialog>

                {/* DATA VIEW DIALOG */}
                <Dialog open={!!viewDataDoc} onClose={() => setViewDataDoc(null)} maxWidth="sm" fullWidth>
                    <DialogTitle>{viewDataDoc ? getDocLabel(viewDataDoc.document_type) : 'Data Verification'}</DialogTitle>
                    <DialogContent dividers>
                        <Stack spacing={2}>
                            {viewDataDoc?.document_type === 'business_data' && (
                                <>
                                    <Box><Typography variant="caption" color="text.secondary">Name</Typography><Typography variant="body1" fontWeight="bold">{company.name}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Email</Typography><Typography variant="body1" fontWeight="bold">{company.email}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Phone</Typography><Typography variant="body1" fontWeight="bold">{company.phone_number || user?.phone_number || user?.phone || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">RC Number</Typography><Typography variant="body1" fontWeight="bold">{company.rc_number || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">BVN</Typography><Typography variant="body1" fontWeight="bold">{user?.bvn || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Category</Typography><Typography variant="body1" fontWeight="bold">{company.business_category || user?.business_category || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Description</Typography><Typography variant="body1" fontWeight="bold">{company.description || user?.description || 'N/A'}</Typography></Box>
                                </>
                            )}
                            {viewDataDoc?.document_type === 'address_data' && (
                                <>
                                    <Box><Typography variant="caption" color="text.secondary">Address</Typography><Typography variant="body1" fontWeight="bold">{company.address || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">State</Typography><Typography variant="body1" fontWeight="bold">{company.state || user?.state || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">LGA</Typography><Typography variant="body1" fontWeight="bold">{company.lga || user?.lga || 'N/A'}</Typography></Box>
                                </>
                            )}
                            {viewDataDoc?.document_type === 'owner_data' && (
                                <>
                                    <Box><Typography variant="caption" color="text.secondary">Full Name</Typography><Typography variant="body1" fontWeight="bold">{user ? `${user.first_name} ${user.last_name}` : 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Phone</Typography><Typography variant="body1" fontWeight="bold">{user?.phone_number || user?.phone || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">BVN</Typography><Typography variant="body1" fontWeight="bold">{user?.bvn || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">NIN</Typography><Typography variant="body1" fontWeight="bold">{user?.nin || 'N/A'}</Typography></Box>
                                </>
                            )}
                            {viewDataDoc?.document_type === 'account_data' && (
                                <>
                                    <Box><Typography variant="caption" color="text.secondary">Bank Name</Typography><Typography variant="body1" fontWeight="bold">{company.bank_name || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Account Number</Typography><Typography variant="body1" fontWeight="bold">{company.account_number || 'N/A'}</Typography></Box>
                                    <Box><Typography variant="caption" color="text.secondary">Account Name</Typography><Typography variant="body1" fontWeight="bold">{company.account_name || 'N/A'}</Typography></Box>
                                </>
                            )}
                            {viewDataDoc?.document_type === 'bvn_data' && (
                                <Stack spacing={2} alignItems="center">
                                    {ownerBvn?.photo && (
                                        <Box
                                            component="img"
                                            src={`data:image/jpeg;base64,${ownerBvn.photo}`}
                                            sx={{
                                                width: 120,
                                                height: 120,
                                                borderRadius: '50%',
                                                objectFit: 'cover',
                                                border: (theme) => `solid 2px ${theme.palette.divider}`,
                                                mb: 1
                                            }}
                                        />
                                    )}
                                    <Stack spacing={1} sx={{ width: '100%' }}>
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Typography variant="body2" color="text.secondary">Full Name:</Typography>
                                            <Typography variant="body2" fontWeight="bold">{ownerBvn ? `${ownerBvn.firstName} ${ownerBvn.middleName || ''} ${ownerBvn.lastName}` : 'N/A'}</Typography>
                                        </Box>
                                        <Divider />
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Typography variant="body2" color="text.secondary">BVN:</Typography>
                                            <Typography variant="body2" fontWeight="bold">{ownerBvn?.bvn || 'N/A'}</Typography>
                                        </Box>
                                        <Divider />
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Typography variant="body2" color="text.secondary">Gender:</Typography>
                                            <Typography variant="body2" fontWeight="bold">{ownerBvn?.gender || 'N/A'}</Typography>
                                        </Box>
                                        <Divider />
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Typography variant="body2" color="text.secondary">Date of Birth:</Typography>
                                            <Typography variant="body2" fontWeight="bold">{ownerBvn?.birthday || 'N/A'}</Typography>
                                        </Box>
                                        <Divider />
                                        <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <Typography variant="body2" color="text.secondary">Phone:</Typography>
                                            <Typography variant="body2" fontWeight="bold">{ownerBvn?.phoneNumber || 'N/A'}</Typography>
                                        </Box>
                                    </Stack>
                                </Stack>
                            )}
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setViewDataDoc(null)}>Close</Button>
                    </DialogActions>
                </Dialog>

                {/* EDIT PROFILE DIALOG */}
                <Dialog open={openEditDialog} onClose={() => setOpenEditDialog(false)} fullWidth maxWidth="sm">
                    <DialogTitle>Update Company Profile</DialogTitle>
                    <DialogContent dividers>
                        <Stack spacing={2} sx={{ pt: 1 }}>
                            <TextField
                                label="Trading/Business Name"
                                fullWidth
                                value={editForm.business_name}
                                onChange={(e) => setEditForm({ ...editForm, business_name: e.target.value })}
                            />
                            <TextField
                                label="Login Email"
                                fullWidth
                                value={editForm.email}
                                onChange={(e) => setEditForm({ ...editForm, email: e.target.value })}
                            />
                            <TextField
                                label="Phone Number"
                                fullWidth
                                value={editForm.phone}
                                onChange={(e) => setEditForm({ ...editForm, phone: e.target.value })}
                            />
                            <TextField
                                label="RC Number"
                                fullWidth
                                value={editForm.rc_number}
                                onChange={(e) => setEditForm({ ...editForm, rc_number: e.target.value })}
                            />
                            <Divider sx={{ my: 1 }} />
                            <Typography variant="overline" color="text.secondary">Override Credentials (Optional)</Typography>
                            <TextField
                                label="New Password"
                                type="password"
                                fullWidth
                                value={editForm.password}
                                onChange={(e) => setEditForm({ ...editForm, password: e.target.value })}
                                placeholder="Leave blank to keep current"
                            />
                            <TextField
                                label="New Transaction PIN"
                                type="text"
                                fullWidth
                                inputProps={{ maxLength: 4 }}
                                value={editForm.pin}
                                onChange={(e) => setEditForm({ ...editForm, pin: e.target.value })}
                                placeholder="4 digit PIN"
                            />
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setOpenEditDialog(false)} color="inherit">Cancel</Button>
                        <Button onClick={submitEditProfile} variant="contained" disabled={editSubmitting}>
                            {editSubmitting ? 'Updating...' : 'Save Changes'}
                        </Button>
                    </DialogActions>
                </Dialog>

                {/* DELETE CONFIRMATION DIALOG */}
                <Dialog open={openDeleteDialog} onClose={() => setOpenDeleteDialog(false)}>
                    <DialogTitle>Delete Company</DialogTitle>
                    <DialogContent>
                        <Typography variant="body1" sx={{ color: 'error.main', mb: 2 }}>
                            Are you sure you want to delete <strong>{company.name}</strong>?
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            This action is irreversible. All wallets, virtual accounts, and transaction history associated with this company will be permanently deleted.
                        </Typography>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={() => setOpenDeleteDialog(false)} color="inherit">Cancel</Button>
                        <Button onClick={submitDelete} variant="contained" color="error" disabled={deleteSubmitting}>
                            {deleteSubmitting ? 'Deleting...' : 'Delete Permanently'}
                        </Button>
                    </DialogActions>
                </Dialog>

            </Container >
        </Page >
    );
}
