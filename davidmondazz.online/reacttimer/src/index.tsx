import React from 'react';
import ReactDOM from 'react-dom/client';
// import './index.css'; // Remove this import
import App from './App';
// import reportWebVitals from './reportWebVitals'; // Remove this import
import { ChakraProvider, ColorModeScript } from '@chakra-ui/react';
import theme from './theme';
import { BrowserRouter, useLocation } from 'react-router-dom';
import { AudioSettingsProvider } from './context/AudioSettingsContext';

// Page title management component
const PageTitleManager = () => {
  const location = useLocation();
  
  React.useEffect(() => {
    // Default title
    let title = 'Timer Tracker';
    
    // Update title based on current path
    const path = location.pathname;
    if (path === '/') {
      title = 'Timers | Timer Tracker';
    } else if (path === '/marketplace') {
      title = 'Shop | Timer Tracker';
    } else if (path === '/notes') {
      title = 'Notes | Timer Tracker';
    } else if (path === '/bank') {
      title = 'Bank | Timer Tracker';
    } else if (path === '/admin') {
      title = 'Admin | Timer Tracker';
    } else if (path.startsWith('/timer/')) {
      // Timer detail pages are handled separately in TimerDetailPage.tsx
      return;
    }
    
    document.title = title;
  }, [location]);
  
  return null;
};

const root = ReactDOM.createRoot(
  document.getElementById('root') as HTMLElement
);

root.render(
  <React.StrictMode>
    <BrowserRouter basename="/reacttimer/build/">
      <AudioSettingsProvider>
        <ChakraProvider theme={theme}>
          <ColorModeScript initialColorMode={theme.config.initialColorMode} />
          <PageTitleManager />
          <App />
        </ChakraProvider>
      </AudioSettingsProvider>
    </BrowserRouter>
  </React.StrictMode>
);

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
// reportWebVitals(); // Remove the function call if it exists (it might be commented out already) 