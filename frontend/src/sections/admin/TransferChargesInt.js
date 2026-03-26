import * as Yup from 'yup';
import PropTypes from 'prop-types';
import { useMemo, useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import swal from 'sweetalert';
import { LoadingButton } from '@mui/lab';
import { Card, Grid, Alert, Stack, Typography } from '@mui/material';
import axios from '../../utils/axios';
import { FormProvider, RHFTextField, RHFSelect, RHFSwitch } from '../../components/hook-form';
import useIsMountedRef from '../../hooks/useIsMountedRef';

TransferChargesInt.propTypes = { setting: PropTypes.object };

export default function TransferChargesInt({ setting }) {
    const isMountedRef = useIsMountedRef();
    const [palmPayVaCharge, setPalmPayVaCharge] = useState({ type: 'PERCENT', value: 0, cap: 0 });

    const UpdateSchema = Yup.object().shape({
        // 1. Virtual Account Deposit
        va_deposit_type:  Yup.string().required(),
        va_deposit_value: Yup.number().required(),
        va_deposit_cap:   Yup.number().required(),
        // 2. Settlement Withdrawal Fee
        payout_bank_type:  Yup.string().required(),
        payout_bank_value: Yup.number().required(),
        payout_bank_cap:   Yup.number().required(),
        // 3. Pay With Bank Transfer (Dynamic Account)
        transfer_type:  Yup.string().required(),
        transfer_value: Yup.number().required(),
        transfer_cap:   Yup.number().required(),
        // 4. External Transfer (Other Banks)
        payout_palmpay_type:  Yup.string().required(),
        payout_palmpay_value: Yup.number().required(),
        payout_palmpay_cap:   Yup.number().required(),
        // Settlement Rules
        auto_settlement_enabled:   Yup.boolean(),
        settlement_delay_hours:    Yup.number().min(0.0167).max(168),
        settlement_skip_weekends:  Yup.boolean(),
        settlement_skip_holidays:  Yup.boolean(),
        settlement_time:           Yup.string(),
        settlement_minimum_amount: Yup.number().min(0),
    });

    const defaultValues = useMemo(() => ({
        // 1. Virtual Account Deposit (from /api/secure/discount/other palmpay_va_charge)
        va_deposit_type:  (palmPayVaCharge?.type === 'PERCENT' ? 'PERCENTAGE' : palmPayVaCharge?.type) || 'PERCENTAGE',
        va_deposit_value: palmPayVaCharge?.value || 0,
        va_deposit_cap:   palmPayVaCharge?.cap   || 0,
        // 2. Settlement Withdrawal Fee
        payout_bank_type:  setting?.payout_bank_charge_type  || 'FLAT',
        payout_bank_value: setting?.payout_bank_charge_value || 0,
        payout_bank_cap:   setting?.payout_bank_charge_cap   || 0,
        // 3. Pay With Bank Transfer (Dynamic Account) — uses transfer_charge fields
        transfer_type:  setting?.transfer_charge_type  || 'FLAT',
        transfer_value: setting?.transfer_charge_value || 0,
        transfer_cap:   setting?.transfer_charge_cap   || 0,
        // 4. External Transfer (Other Banks) — uses payout_palmpay fields
        payout_palmpay_type:  setting?.payout_palmpay_charge_type  || 'FLAT',
        payout_palmpay_value: setting?.payout_palmpay_charge_value || 0,
        payout_palmpay_cap:   setting?.payout_palmpay_charge_cap   || 0,
        // Settlement Rules
        auto_settlement_enabled:   setting?.auto_settlement_enabled ?? true,
        settlement_delay_hours:    setting?.settlement_delay_hours != null ? parseFloat(setting.settlement_delay_hours) : 24,
        settlement_skip_weekends:  setting?.settlement_skip_weekends ?? true,
        settlement_skip_holidays:  setting?.settlement_skip_holidays ?? true,
        settlement_time:           setting?.settlement_time || '03:00:00',
        settlement_minimum_amount: setting?.settlement_minimum_amount != null ? parseFloat(setting.settlement_minimum_amount) : 100,
    }), [setting, palmPayVaCharge]);

    const AccessToken = window.localStorage.getItem('accessToken');
    const methods = useForm({ resolver: yupResolver(UpdateSchema), defaultValues });
    const { reset, setError, handleSubmit, formState: { errors, isSubmitting } } = methods;

    // Load VA charge from /other endpoint
    useEffect(() => {
        axios.get('/api/secure/discount/other').then(res => {
            if (res.data.status === 'success') {
                const va = res.data.palmpay_va_charge || { type: 'PERCENT', value: 0, cap: 0 };
                setPalmPayVaCharge(va);
            }
        }).catch(() => {});
    }, []);

    useEffect(() => {
        if (setting) reset(defaultValues);
    }, [setting, palmPayVaCharge, defaultValues, reset]);

    const onSubmit = async (data) => {
        try {
            // Save transfer/settlement charges
            await axios.post(`/api/secure/discount/other/${AccessToken}/habukhan/secure`, {
                id: AccessToken,
                transfer_type:  data.transfer_type,
                transfer_value: data.transfer_value,
                transfer_cap:   data.transfer_cap,
                payout_bank_type:  data.payout_bank_type,
                payout_bank_value: data.payout_bank_value,
                payout_bank_cap:   data.payout_bank_cap,
                payout_palmpay_type:  data.payout_palmpay_type,
                payout_palmpay_value: data.payout_palmpay_value,
                payout_palmpay_cap:   data.payout_palmpay_cap,
                auto_settlement_enabled:   data.auto_settlement_enabled,
                settlement_delay_hours:    data.settlement_delay_hours,
                settlement_skip_weekends:  data.settlement_skip_weekends,
                settlement_skip_holidays:  data.settlement_skip_holidays,
                settlement_time:           data.settlement_time,
                settlement_minimum_amount: data.settlement_minimum_amount,
            });

            // Save VA deposit charge separately
            await axios.post(`/api/secure/discount/service/${AccessToken}/habukhan/secure`, {
                id: AccessToken,
                palmpay_charge: {
                    type:  data.va_deposit_type === 'PERCENTAGE' ? 'PERCENT' : data.va_deposit_type,
                    value: data.va_deposit_value,
                    cap:   data.va_deposit_cap,
                },
            });

            swal('success', 'All charges updated successfully', 'success');
        } catch (error) {
            if (isMountedRef.current) {
                setError('afterSubmit', { message: error.message || 'Error updating charges' });
            }
        }
    };

    const ChargeCard = ({ title, subtitle, typeName, valueName, capName }) => (
        <Grid item xs={12} md={6}>
            <Card sx={{ p: 3, height: '100%' }}>
                <Typography variant="h6" sx={{ mb: 0.5 }}>{title}</Typography>
                {subtitle && <Typography variant="caption" color="text.secondary" sx={{ mb: 2, display: 'block' }}>{subtitle}</Typography>}
                <Stack spacing={2} sx={{ mt: 2 }}>
                    <RHFSelect name={typeName} label="Charge Type">
                        <option value="FLAT">Flat Fee (₦)</option>
                        <option value="PERCENTAGE">Percentage (%)</option>
                    </RHFSelect>
                    <RHFTextField name={valueName} label="Charge Value" type="number" />
                    <RHFTextField name={capName} label="Cap (Max Charge)" type="number" helperText="Set 0 for no cap" />
                </Stack>
            </Card>
        </Grid>
    );

    return (
        <FormProvider methods={methods} onSubmit={handleSubmit(onSubmit)}>
            <Grid container spacing={3}>
                <Grid item xs={12}>
                    {!!errors.afterSubmit && <Alert severity="error">{errors.afterSubmit.message}</Alert>}

                    <Grid container spacing={3}>
                        {/* 1. Virtual Account Deposit */}
                        <ChargeCard
                            title="Virtual Account Deposit Fee"
                            subtitle="Fee charged when aggregators receive money via their virtual account"
                            typeName="va_deposit_type"
                            valueName="va_deposit_value"
                            capName="va_deposit_cap"
                        />

                        {/* 2. Settlement Withdrawal Fee */}
                        <ChargeCard
                            title="Settlement Withdrawal Fee"
                            subtitle="Fee deducted when settling aggregator wallet balance to their bank"
                            typeName="payout_bank_type"
                            valueName="payout_bank_value"
                            capName="payout_bank_cap"
                        />

                        {/* 3. Pay With Bank Transfer (Dynamic Account) */}
                        <ChargeCard
                            title="Pay With Bank Transfer Fee"
                            subtitle="Fee for dynamic virtual account checkout (one-time account per order)"
                            typeName="transfer_type"
                            valueName="transfer_value"
                            capName="transfer_cap"
                        />

                        {/* 4. External Transfer (Other Banks) */}
                        <ChargeCard
                            title="External Transfer (Other Banks)"
                            subtitle="Fee charged when aggregators send money to other Nigerian banks"
                            typeName="payout_palmpay_type"
                            valueName="payout_palmpay_value"
                            capName="payout_palmpay_cap"
                        />

                        {/* Settlement Rules */}
                        <Grid item xs={12}>
                            <Card sx={{ p: 3 }}>
                                <Typography variant="h6" sx={{ mb: 1 }}>Settlement Rules</Typography>
                                <Alert severity="info" sx={{ mb: 3 }}>
                                    PalmPay settles you at 2am. Your users settle at the time configured below (default 3am = 1hr buffer).
                                </Alert>
                                <Grid container spacing={3}>
                                    <Grid item xs={12} md={6}>
                                        <Stack spacing={3}>
                                            <RHFSwitch name="auto_settlement_enabled" label="Enable Auto Settlement" />
                                            <RHFTextField name="settlement_delay_hours" label="Settlement Delay (Hours)" type="number"
                                                inputProps={{ step: '0.0001' }}
                                                helperText="0.0167 = 1min, 1 = 1hr, 24 = 1day" />
                                            <RHFTextField name="settlement_time" label="Settlement Time (HH:MM:SS)"
                                                placeholder="03:00:00" helperText="Time of day to process (e.g. 03:00:00)" />
                                        </Stack>
                                    </Grid>
                                    <Grid item xs={12} md={6}>
                                        <Stack spacing={3}>
                                            <RHFSwitch name="settlement_skip_weekends" label="Skip Weekends" />
                                            <RHFSwitch name="settlement_skip_holidays" label="Skip Holidays" />
                                            <RHFTextField name="settlement_minimum_amount" label="Minimum Settlement Amount (₦)"
                                                type="number" helperText="Minimum amount to trigger settlement" />
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
