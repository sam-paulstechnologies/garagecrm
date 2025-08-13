import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import TemplatesPage from './Pages/TemplatesPage';

const MainApp = () => (
  <BrowserRouter>
    <Routes>
      <Route path="/admin/templates" element={<TemplatesPage />} />
    </Routes>
  </BrowserRouter>
);

export default MainApp;
