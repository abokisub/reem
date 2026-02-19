import PropTypes from 'prop-types';
// @mui
import { styled } from '@mui/material/styles';
//
import LogoLoader from './LogoLoader';

// ----------------------------------------------------------------------

const RootStyle = styled('div')(({ theme }) => ({
  right: 0,
  bottom: 0,
  zIndex: 99999,
  width: '100%',
  height: '100%',
  position: 'fixed',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  backgroundColor: theme.palette.background.default,
}));

// ----------------------------------------------------------------------

LoadingScreen.propTypes = {
  isDashboard: PropTypes.bool,
};

export default function LoadingScreen({ isDashboard, ...other }) {
  return (
    <>
      {!isDashboard && (
        <RootStyle {...other}>
          <LogoLoader size={120} />
        </RootStyle>
      )}
    </>
  );
}
