import PropTypes from 'prop-types';
import { useEffect } from 'react';
import { useLocation, Link as RouterLink } from 'react-router-dom';
import { styled, useTheme } from '@mui/material/styles';
import { Box, Stack, Drawer, Button, MenuItem, Select, FormControl } from '@mui/material';
// hooks
import useResponsive from '../../../hooks/useResponsive';
import useCollapseDrawer from '../../../hooks/useCollapseDrawer';
// utils
import cssStyles from '../../../utils/cssStyles';
// config
import { NAVBAR } from '../../../config';
// components
import Logo from '../../../components/Logo';
import Scrollbar from '../../../components/Scrollbar';
import { NavSectionVertical } from '../../../components/nav-section';

// routes
import * as Paths from '../../../routes/paths';
// components
import Label from '../../../components/Label';
//
import SvgIconStyle from '../../../components/SvgIconStyle';


import NavbarAccount from './NavbarAccount';
import CollapseButton from './CollapseButton';
import useAuth from '../../../hooks/useAuth';

const { PATH_DASHBOARD, PATH_ADMIN } = Paths;


// ----------------------------------------------------------------------

const RootStyle = styled('div')(({ theme }) => ({
  [theme.breakpoints.up('lg')]: {
    flexShrink: 0,
    transition: theme.transitions.create('width', {
      duration: theme.transitions.duration.shorter,
    }),
  },
}));

// ----------------------------------------------------------------------

NavbarVertical.propTypes = {
  isOpenSidebar: PropTypes.bool,
  onCloseSidebar: PropTypes.func,
};

export default function NavbarVertical({ isOpenSidebar, onCloseSidebar }) {
  const theme = useTheme();
  const { user } = useAuth();
  const { pathname } = useLocation();

  const isDesktop = useResponsive('up', 'lg');

  const { isCollapse, collapseClick, collapseHover, onToggleCollapse, onHoverEnter, onHoverLeave } =
    useCollapseDrawer();
  const getIcon = (name) => <SvgIconStyle src={`/icons/${name}.svg`} sx={{ width: 1, height: 1 }} />;
  const ICONS = {
    user: getIcon('ic_user'),
    transaction: getIcon('transaction'),
    buy_cable: getIcon('Habukhan_cable'),
    calendar: getIcon('ic_calendar'),
    buy_data: getIcon('Habukhan_data'),
    buy_airtime: getIcon('Habukhan_phone'),
    dashboard: getIcon('Habukhan_home'),
    fund: getIcon('fund'),
    phone: getIcon('phone'),
    stock: getIcon('stock'),
    setting: getIcon('setting'),
    wallet: getIcon('wallet'),
    cal: getIcon('cal'),
    price: getIcon('price'),
    logout: getIcon('ic_logout')
  };


  // nav bar  
  const navConfig = [
    {
      subheader: 'DASHBOARD',
      items: [
        { title: 'Overview', path: PATH_DASHBOARD.general.app, icon: ICONS.dashboard },
        { title: 'Wallet', path: PATH_DASHBOARD.general.wallet, icon: ICONS.wallet },
      ],
    },

    {
      subheader: 'COLLECTIONS',
      items: [
        { title: 'R.A Transactions', path: PATH_DASHBOARD.general.ra_transactions, icon: ICONS.transaction },
        { title: 'Customers', path: PATH_DASHBOARD.general.customers, icon: ICONS.user },
        { title: 'Reserved Account', path: PATH_DASHBOARD.general.reserved_account, icon: getIcon('ic_banking') },
        { title: 'Cards', path: PATH_DASHBOARD.general.cards, icon: getIcon('card') },
      ],
    },
    {
      subheader: 'DISBURSEMENTS',
      items: [
        { title: 'Transfer', path: PATH_DASHBOARD.general.transfer, icon: getIcon('trans') },
      ],
    },
    {
      subheader: 'MERCHANT',
      items: [
        { title: 'Settings', path: PATH_DASHBOARD.general.settings, icon: ICONS.setting },
        { title: 'Webhook Event', path: PATH_DASHBOARD.general.webhook, icon: getIcon('ic_chat'), info: <Label color="info">New</Label> },
        { title: 'Developer API', path: PATH_DASHBOARD.general.developer, icon: getIcon('api') },
        { title: 'Support', path: PATH_DASHBOARD.general.support, icon: ICONS.user },
        { title: 'Documentation', path: '/documentation/home', icon: getIcon('ic_blog') },
      ],
    }
  ]

  const customerTrans = [
    {
      subheader: 'customer care',
      items: [
        {
          title: 'Transaction',
          icon: ICONS.transaction,
          path: PATH_DASHBOARD.customer.root,
          children: [
            { title: 'Transaction Summary', path: PATH_DASHBOARD.customer.history },
            { title: 'Deposit Summary', path: PATH_DASHBOARD.customer.deposit },
            { title: 'manual funding', path: PATH_DASHBOARD.customer.manualfunding },
            { title: 'Data Transaction', path: PATH_DASHBOARD.customer.data },
            { title: 'Airtime Transaction', path: PATH_DASHBOARD.customer.airtime },
            { title: 'Cable Transaction', path: PATH_DASHBOARD.customer.cable },
            { title: 'Electricity Transaction', path: PATH_DASHBOARD.customer.bill },
            { title: 'Bulk SMS Transaction', path: PATH_DASHBOARD.customer.bulksms },
            { title: 'Stock Summary', path: PATH_DASHBOARD.customer.stock },
            { title: 'Airtime 2 Cash', path: PATH_DASHBOARD.customer.airtimecash },
            { title: 'Result Checker', path: PATH_DASHBOARD.customer.result },
            { title: 'data-card', path: PATH_DASHBOARD.customer.data_card },
            { title: 'recharge-card', path: PATH_DASHBOARD.customer.recharge_card }
          ],
        },
        { path: PATH_DASHBOARD.credituser, title: 'credit / Debit', icon: ICONS.wallet }
      ],
    },
  ];

  const adminConfig = [
    {
      subheader: 'ADMIN DASHBOARD',
      items: [
        { title: 'Overview', path: PATH_ADMIN.general.app, icon: ICONS.dashboard },
        { title: 'System Info', path: PATH_ADMIN.general.info, icon: getIcon('ic_info') },
        { title: 'Feature Toggles', path: PATH_ADMIN.general.feature, icon: getIcon('ic_settings') },
      ],
    },
    {
      subheader: 'COMPANY MANAGEMENT',
      items: [
        { title: 'All Companies', path: PATH_ADMIN.companies.list, icon: getIcon('ic_briefcase') },
        { title: 'Pending KYC', path: PATH_ADMIN.companies.pendingKyc, icon: getIcon('ic_user'), info: <Label color="warning">Review</Label> },
      ],
    },
    {
      subheader: 'MERCHANT MGMT',
      items: [
        { title: 'Identity (KYC)', path: PATH_ADMIN.user.userskyc, icon: getIcon('ic_user'), info: <Label color="error">Pending</Label> },
        {
          title: 'Users & Businesses',
          icon: ICONS.user,
          path: PATH_ADMIN.user.root,
          children: [
            { title: 'All Users', path: PATH_ADMIN.user.all_user },
            { title: 'Reserved Accounts', path: PATH_ADMIN.user.automedaccount },
            { title: 'Debit / Credit', path: PATH_ADMIN.user.creditUser },
            { title: 'Reset Password', path: PATH_ADMIN.user.resetpassword },
            { title: 'Banned Numbers', path: PATH_ADMIN.user.bannednumber },
          ],
        },
      ],
    },
    {
      subheader: 'TRANSACTION MONITOR',
      items: [
        {
          title: 'All Records',
          icon: ICONS.transaction,
          path: PATH_ADMIN.trans.root,
          children: [
            { title: 'Transaction History', path: PATH_ADMIN.trans.history },
            { title: 'Transfer Requests', path: PATH_ADMIN.trans.transfer },
            { title: 'Card Operations', path: PATH_ADMIN.trans.cards },
            { title: 'Manual Funding', path: PATH_ADMIN.trans.manual },
          ],
        },
      ],
    },
    {
      subheader: 'SERVICE CONTROL',
      items: [
        {
          title: 'Plans & Pricing',
          icon: ICONS.price,
          path: PATH_ADMIN.plan.root,
          children: [
            { title: 'Data Plans', path: PATH_ADMIN.plan.data },
            { title: 'Network Config', path: PATH_ADMIN.plan.network },
            { title: 'Bill / Utility', path: PATH_ADMIN.plan.bill },
            { title: 'Cable TV', path: PATH_ADMIN.plan.cable },
            { title: 'Education', path: PATH_ADMIN.plan.exam },
          ],
        },
        {
          title: 'Configuration',
          icon: ICONS.setting,
          path: PATH_ADMIN.lock.root,
          children: [
            { title: 'Service Locks', path: PATH_ADMIN.lock.root },
            { title: 'Discounts', path: PATH_ADMIN.discount.root },
            { title: 'API Selection', path: PATH_ADMIN.selection.root },
          ]
        }
      ],
    },
    {
      subheader: 'SYSTEM INTEGRATION',
      items: [
        {
          title: 'API Gateways',
          icon: getIcon('api'),
          path: PATH_ADMIN.api.root,
          children: [
            { title: 'System API', path: PATH_ADMIN.api.Habukhan },
            { title: 'Adex Integration', path: PATH_ADMIN.api.adex },
            { title: 'Virus Gateway', path: PATH_ADMIN.api.virus },
            { title: 'MsOrg Integration', path: PATH_ADMIN.api.msorg },
          ],
        },
        { title: 'Payment Keys', path: PATH_ADMIN.general.payment_key, icon: getIcon('ic_key') },
      ],
    },
    {
      subheader: 'COMMUNICATION',
      items: [
        { title: 'Support Tickets', path: PATH_ADMIN.general.support, icon: getIcon('ic_chat') },
        { title: 'Gmail Logs', path: PATH_ADMIN.sendmessage.gmail, icon: getIcon('ic_mail') },
        { title: 'System Notifications', path: PATH_ADMIN.general.notif, icon: getIcon('ic_notification') },
      ],
    },
  ];

  useEffect(() => {
    if (isOpenSidebar) {
      onCloseSidebar();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname]);

  const renderContent = (
    <Scrollbar
      sx={{
        height: 1,
        '& .simplebar-content': { height: 1, display: 'flex', flexDirection: 'column' },
      }}
    >
      <Stack
        spacing={3}
        sx={{
          pt: 3,
          pb: 2,
          px: 2.5,
          flexShrink: 0,
          ...(isCollapse && { alignItems: 'center' }),
        }}
      >
        <Stack direction="row" alignItems="center" justifyContent="space-between">
          <Logo />

          {isDesktop && !isCollapse && (
            <CollapseButton onToggleCollapse={onToggleCollapse} collapseClick={collapseClick} />
          )}
        </Stack>

      </Stack>

      {user?.type !== 'ADMIN' && <NavSectionVertical navConfig={navConfig} isCollapse={isCollapse} />}
      {user?.type === 'CUSTOMER' && <NavSectionVertical navConfig={customerTrans} isCollapse={isCollapse} />}
      {user?.type === 'ADMIN' && <NavSectionVertical navConfig={adminConfig} isCollapse={isCollapse} />}

      <Box sx={{ flexGrow: 1 }} />

      {!isCollapse && (
        <Box sx={{ px: 2.5, pb: 3, mt: 10 }}>
          <NavbarAccount isCollapse={isCollapse} />
        </Box>
      )}
    </Scrollbar>
  );

  return (
    <RootStyle
      sx={{
        width: {
          lg: isCollapse ? NAVBAR.DASHBOARD_COLLAPSE_WIDTH : NAVBAR.DASHBOARD_WIDTH,
        },
        ...(collapseClick && {
          position: 'absolute',
        }),
      }}
    >
      {!isDesktop && (
        <Drawer
          open={isOpenSidebar}
          onClose={onCloseSidebar}
          PaperProps={{
            sx: {
              width: NAVBAR.DASHBOARD_WIDTH,
              bgcolor: 'background.paper',
            },
          }}
        >
          {renderContent}
        </Drawer>
      )}

      {isDesktop && (
        <Drawer
          open
          variant="persistent"
          onMouseEnter={onHoverEnter}
          onMouseLeave={onHoverLeave}
          PaperProps={{
            sx: {
              width: NAVBAR.DASHBOARD_WIDTH,
              borderRightStyle: 'dashed',
              bgcolor: 'background.paper',
              transition: (theme) =>
                theme.transitions.create('width', {
                  duration: theme.transitions.duration.standard,
                }),
              ...(isCollapse && {
                width: NAVBAR.DASHBOARD_COLLAPSE_WIDTH,
              }),
              ...(collapseHover && {
                ...cssStyles(theme).bgBlur(),
                boxShadow: (theme) => theme.customShadows.z24,
              }),
            },
          }}
        >
          {renderContent}
        </Drawer>
      )}
    </RootStyle>
  );
}
