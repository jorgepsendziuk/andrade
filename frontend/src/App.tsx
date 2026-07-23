import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { CmsProvider } from './context/CmsContext';
import { HomePage } from './pages/HomePage';
import { AdminPage } from './pages/AdminPage';

export default function App() {
  return (
    <CmsProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/admin" element={<AdminPage />} />
        </Routes>
      </BrowserRouter>
    </CmsProvider>
  );
}
