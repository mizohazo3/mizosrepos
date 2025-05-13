import React from 'react';
import { Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import { AudioSettingsProvider } from './contexts/AudioSettingsContext'; // Import the provider
import HomePage from './pages/HomePage';
import AdminPage from './pages/AdminPage';
import TimerDetailPage from './pages/TimerDetailPage';
import BankPage from './pages/BankPage';
import MarketplacePage from './pages/MarketplacePage';
import NotesPage from './pages/NotesPage';
import TodoPage from './pages/TodoPage';

function App() {
  return (
    <AudioSettingsProvider> {/* Wrap the routes with the provider */}
      <Routes>
        <Route path="/" element={<Layout />}>
          {/* Child routes are rendered inside Layout's <Outlet /> */}
          <Route index element={<HomePage />} />
        <Route path="admin" element={<AdminPage />} />
        <Route path="timer/:timerId" element={<TimerDetailPage />} />
        <Route path="bank" element={<BankPage />} />
        <Route path="marketplace" element={<MarketplacePage />} />
        <Route path="notes" element={<NotesPage />} />
        <Route path="todos" element={<TodoPage />} />
        {/* Add other routes here */}
      </Route>
    </Routes>
  </AudioSettingsProvider>
  );
}

export default App;
