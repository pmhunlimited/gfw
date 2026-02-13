
import React from 'react';
import { HashRouter as Router, Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import AdminLayout from './components/AdminLayout';
import Home from './pages/Home';
import PostDetail from './pages/PostDetail';
import Watch from './pages/Watch';
import Betting from './pages/Betting';
import Standings from './pages/Standings';
import AdminPosts from './pages/AdminPosts';
import AdminSettings from './pages/AdminSettings';
import AdminComments from './pages/AdminComments';
import AdminSubscribers from './pages/AdminSubscribers';
import AdminProfile from './pages/AdminProfile';
import AdminLogin from './pages/AdminLogin';
import NotFound from './pages/NotFound';

const App: React.FC = () => {
  return (
    <Router>
      <Routes>
        {/* Frontend Routes */}
        <Route path="/" element={<Layout><Home /></Layout>} />
        <Route path="/post/:id" element={<Layout><PostDetail /></Layout>} />
        <Route path="/watch" element={<Layout><Watch /></Layout>} />
        <Route path="/betting" element={<Layout><Betting /></Layout>} />
        <Route path="/tables" element={<Layout><Standings /></Layout>} />
        <Route path="/category/:category" element={<Layout><Home /></Layout>} />
        <Route path="/fixtures" element={<Layout><Home /></Layout>} />
        <Route path="/stories" element={<Layout><Home /></Layout>} />

        {/* Admin Routes */}
        <Route path="/admin/login" element={<AdminLayout><AdminLogin /></AdminLayout>} />
        <Route path="/admin" element={<AdminLayout><AdminPosts /></AdminLayout>} />
        <Route path="/admin/posts" element={<AdminLayout><AdminPosts /></AdminLayout>} />
        <Route path="/admin/subscribers" element={<AdminLayout><AdminSubscribers /></AdminLayout>} />
        <Route path="/admin/comments" element={<AdminLayout><AdminComments /></AdminLayout>} />
        <Route path="/admin/settings" element={<AdminLayout><AdminSettings /></AdminLayout>} />
        <Route path="/admin/profile" element={<AdminLayout><AdminProfile /></AdminLayout>} />

        {/* 404 Route */}
        <Route path="*" element={<Layout><NotFound /></Layout>} />
      </Routes>
    </Router>
  );
};

export default App;
