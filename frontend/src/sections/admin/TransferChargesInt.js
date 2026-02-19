import * as Yup from 'yup';
import PropTypes from 'prop-types';
import { useMemo, useEffect } from 'react';
// form
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
// swal
import swal from 'sweetalert';
// @mui
import { LoadingButton } from '@mui/lab';
import { Box, Card, Grid, Alert, Stack, Typography, Switch, FormControlLabel } from '@mui/material';
import axios from '../../utils/axios';
import { FormProvider, RHFTextField, RHFSelect, RHFSwitch } from '../../components/hook-form';
import useIsMountedRef from '../../hooks/useIsMountedRef';

// ----------------------------------------------------------------------
TransferChargesInt.propTypes = {
    setting: PropTypes.object,
};

export default function TransferChargesInt({ setting }) {
    const isMountedRef = useIsMountedRef();
    const UpdateSchema = Yup.object().shape({
        // Transfer (Bank)
        transfer_type: Yup.string().required('Type is required'),
        transfer_value: Yup.number().required('Value is required'),
        transfer_cap: Yup.number().required('Cap is required'),
        // Wallet
        wallet_type: Yup.string().required('Type is required'),
        wallet_value: Yup.number().required('Value is required'),
        wallet_cap: Yup.number().required('Cap is required'),
        // Payout / Settlement (PalmPay)
        payout_palmpay_type: Yup.string().required('Type is required'),
        payout_palmpay_value: Yup.number().required('Value is required'),
        payout_palmpay_cap: Yup.number().required('Cap is required'),
        // Payout (Bank)
        payout_bank_type: Yup.string().required('Type is required'),
        payout_bank_value: Yup.number().required('Value is required'),
        payout_bank_cap: Yup.number().required('Cap is required'),
        // Settlement Rules
        auto_settlement_enabled: Yup.boolean(),
        settlement_delay_hours: Yup.number().min(0.0167).max(168), // Allow minimum 1 minute (0.0167 hours)
        settlement_skip_weekends: Yup.boolean(),
        settlement_skip_holidays: Yup.boolean(),
        settlement_time: Yup.string(),
        settlement_minimum_amount: Yup.number().min(0),
    });

    const defaultValues = useMemo(
        () => ({
            transfer_type: setting?.transfer_charge_type || 'FLAT',
            transfer_value: setting?.transfer_charge_value || 0,
            transfer_cap: setting?.transfer_charge_cap || 0,

            wallet_type: setting?.wallet_charge_type || 'FLAT',
            wallet_value: setting?.wallet_charge_value || 0,
            wallet_cap: setting?.wallet_charge_cap || 0,

            payout_palmpay_type: setting?.payout_palmpay_charge_type || 'FLAT',
            payout_palmpay_value: setting?.payout_palmpay_charge_value || 0,
            payout_palmpay_cap: setting?.payout_palmpay_charge_cap || 0,

            payout_bank_type: setting?.payout_bank_charge_type || 'FLAT',
            payout_bank_value: setting?.payout_bank_charge_value || 0,
            payout_bank_cap: setting?.payout_bank_charge_cap || 0,

            // Settlement Rules
            auto_settlement_enabled: setting?.auto_settlement_enabled ?? true,
            settlement_delay_hours: setting?.settlement_delay_hours !== undefined && setting?.settlement_delay_hours !== null ? parseFloat(setting.settlement_delay_hours) : 24,
            settlement_skip_weekends: setting?.settlement_skip_weekends ?? true,
            settlement_skip_holidays: setting?.settlement_skip_holidays ?? true,
            settlement_time: setting?.settlement_time || '02:00:00',
            settlement_minimum_amount: setting?.settlement_minimum_amount !== undefined && setting?.settlement_minimum_amount !== null ? parseFloat(setting.settlement_minimum_amount) : 100,
        }),
        [setting]
    );

    const AccessToken = window.localStorage.getItem('accessToken');
    const methods = useForm({
        resolver: yupResolver(UpdateSchema),
        defaultValues,
    });

    const {
        reset,
        setError,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = methods;

    useEffect(() => {
        if (setting) {
            reset(defaultValues);
        }
    }, [setting, defaultValues, reset]);

    const onSubmit = async (data) => {
        try {
            await axios.post(`/api/secure/discount/other/${AccessToken}/habukhan/secure`, {
                id: AccessToken,
                ...data
            });
            swal('success', `Transfer & Settlement Charges Updated Successfully`, 'success');
        } catch (error) {
            if (isMountedRef.current) {
                setError('afterSubmit', { ...error, message: error.message || 'Error updating charges' });
            }
        }
    };

    return (
        <FormProvider methods={methods} onSubmit={handleSubmit(onSubmit)}>
            <Grid container spacing={3}>
                <Grid item xs={12}>
                    {!!errors.afterSubmit && <Alert severity="error">{errors.afterSubmit.message}</Alert>}

                    <Grid container spacing={3}>
                        {/* SECTION 1: Bank Transfer (Inbound) */}
                        <Grid item xs={12} md={6}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 3 }}>Funding with Bank Transfer</Typography>
                                <Stack spacing={3}>
                                    <RHFSelect name="transfer_type" label="Charge Type">
                                        <option value="FLAT">Flat Fee (₦)</option>
                                        <option value="PERCENTAGE">Percentage (%)</option>
                                    </RHFSelect>
                                    <RHFTextField name="transfer_value" label="Charge Value" type="number" />
                                    <RHFTextField name="transfer_cap" label="Charge Cap (for %)" type="number" />
                                </Stack>
                            </Card>
                        </Grid>

                        {/* SECTION 2: Wallet to Wallet */}
                        <Grid item xs={12} md={6}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 3 }}>Internal Transfer (Wallet)</Typography>
                                <Stack spacing={3}>
                                    <RHFSelect name="wallet_type" label="Charge Type">
                                        <option value="FLAT">Flat Fee (₦)</option>
                                        <option value="PERCENTAGE">Percentage (%)</option>
                                    </RHFSelect>
                                    <RHFTextField name="wallet_value" label="Charge Value" type="number" />
                                    <RHFTextField name="wallet_cap" label="Charge Cap (for %)" type="number" />
                                </Stack>
                            </Card>
                        </Grid>

                        {/* SECTION 3: Settlement Withdrawal (PalmPay) */}
                        <Grid item xs={12} md={6}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 3 }}>Settlement Withdrawal (PalmPay)</Typography>
                                <Stack spacing={3}>
                                    <RHFSelect name="payout_palmpay_type" label="Charge Type">
                                        <option value="FLAT">Flat Fee (₦)</option>
                                        <option value="PERCENTAGE">Percentage (%)</option>
                                    </RHFSelect>
                                    <RHFTextField name="payout_palmpay_value" label="Charge Value" type="number" />
                                    <RHFTextField name="payout_palmpay_cap" label="Charge Cap (for %)" type="number" />
                                </Stack>
                            </Card>
                        </Grid>

                        {/* SECTION 4: External Payout (Bank) */}
                        <Grid item xs={12} md={6}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 3 }}>External Transfer (Other Banks)</Typography>
                                <Stack spacing={3}>
                                    <RHFSelect name="payout_bank_type" label="Charge Type">
                                        <option value="FLAT">Flat Fee (₦)</option>
                                        <option value="PERCENTAGE">Percentage (%)</option>
                                    </RHFSelect>
                                    <RHFTextField name="payout_bank_value" label="Charge Value" type="number" />
                                    <RHFTextField name="payout_bank_cap" label="Charge Cap (for %)" type="number" />
                                </Stack>
                            </Card>
                        </Grid>

                        {/* SECTION 5: Settlement Rules */}
                        <Grid item xs={12}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 2 }}>Settlement Rules</Typography>
                                <Alert severity="info" sx={{ mb: 3 }}>
                                    Transactions are visible immediately but funds settle after the configured delay. 
                                    PalmPay follows T+1 settlement (next business day at 2am, excluding weekends and holidays).
                                </Alert>
                                
                                <Grid container spacing={3}>
                                    <Grid item xs={12} md={6}>
                                        <Stack spacing={3}>
                                            <RHFSwitch 
                                                name="auto_settlement_enabled" 
                                                label="Enable Auto Settlement"
                                                helperText="When enabled, transactions will settle after the configured delay"
                                            />
                                            
                                            <RHFTextField 
                                                name="settlement_delay_hours" 
                                                label="Settlement Delay (Hours)" 
                                                type="number"
                                                inputProps={{ step: "0.0001" }}
                                                helperText="Hours to delay settlement (0.0167-168). Examples: 0.0167 = 1min, 1 = 1h, 24 = 1day"
                                            />
                                            
                                            <RHFTextField 
                                                name="settlement_time" 
                                                label="Settlement Time (HH:MM:SS)" 
                                                placeholder="02:00:00"
                                                helperText="Time of day to process settlements (e.g., 02:00:00 for 2am)"
                                            />
                                        </Stack>
                                    </Grid>
                                    
                                    <Grid item xs={12} md={6}>
                                        <Stack spacing={3}>
                                            <RHFSwitch 
                                                name="settlement_skip_weekends" 
                                                label="Skip Weekends"
                                                helperText="Move weekend settlements to Monday"
                                            />
                                            
                                            <RHFSwitch 
                                                name="settlement_skip_holidays" 
                                                label="Skip Holidays"
                                                helperText="Move holiday settlements to next business day"
                                            />
                                            
                                            <RHFTextField 
                                                name="settlement_minimum_amount" 
                                                label="Minimum Settlement Amount (₦)" 
                                                type="number"
                                                helperText="Minimum amount to trigger settlement"
                                            />
                                        </Stack>
                                    </Grid>
                                </Grid>
                            </Card>
                        </Grid>
                    </Grid>

                    <Stack alignItems="flex-end" sx={{ mt: 3 }}>
                        <LoadingButton size="large" type="submit" variant="contained" loading={isSubmitting}>
                            Save All Charges
                        </LoadingButton>
                    </Stack>
                </Grid>
            </Grid>
        </FormProvider>
    );
}
