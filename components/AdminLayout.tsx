
import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom';

interface AdminLayoutProps {
  children: React.ReactNode;
}

const AdminLayout: React.FC<AdminLayoutProps> = ({ children }) => {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [searchParams, setSearchParams] = useSearchParams();
  const location = useLocation();
  const navigate = useNavigate();

  const searchQuery = searchParams.get('search') || '';

  useEffect(() => {
    const isAuth = localStorage.getItem('is_admin_auth');
    if (!isAuth && location.pathname !== '/admin/login') {
      navigate('/admin/login');
    }
  }, [location, navigate]);

  const menuItems = [
    { name: 'Editorial', path: '/admin/posts', icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l4 4v12a2 2 0 01-2 2' },
    { name: 'Subscribers', path: '/admin/subscribers', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' },
    { name: 'Moderation', path: '/admin/comments', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { name: 'Systems', path: '/admin/settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066' },
    { name: 'Identity', path: '/admin/profile', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  ];

  const handleLogout = () => {
    localStorage.removeItem('is_admin_auth');
    navigate('/admin/login');
  };

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    if (value) {
      setSearchParams({ ...Object.fromEntries(searchParams.entries()), search: value });
    } else {
      const nextParams = new URLSearchParams(searchParams);
      nextParams.delete('search');
      setSearchParams(nextParams);
    }
  };

  if (location.pathname === '/admin/login') return <>{children}</>;

  return (
    <div className="min-h-screen bg-[#05070a] flex flex-col md:flex-row text-gray-300">
      
      {/* MOBILE TOP BAR */}
      <div className="md:hidden bg-[#0a0e17] border-b border-white/5 p-4 flex items-center justify-between sticky top-0 z-[110]">
        <Link to="/" className="text-xl font-condensed font-black text-white italic tracking-tighter flex items-center">
          <span className="bg-[#ff3e3e] text-white px-1.5 py-0.5 rounded mr-2 not-italic text-sm">GFW</span>
          ADMIN
        </Link>
        <button onClick={() => setIsSidebarOpen(!isSidebarOpen)} className="p-2 text-gray-400 hover:text-white">
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
        </button>
      </div>

      {/* ADMIN SIDEBAR */}
      <aside className={`fixed inset-y-0 left-0 w-64 bg-[#0a0e17] border-r border-white/5 flex-shrink-0 flex flex-col z-[120] transition-transform duration-300 md:translate-x-0 md:static ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className="p-8 hidden md:block">
          <Link to="/" className="text-2xl font-condensed font-black text-white italic tracking-tighter flex items-center">
            <span className="bg-[#ff3e3e] text-white px-2 py-0.5 rounded mr-2 not-italic">GFW</span>
            ADMIN
          </Link>
        </div>
        
        <nav className="mt-4 flex-grow px-4 overflow-y-auto">
          <p className="text-[10px] font-black text-gray-600 uppercase tracking-[0.3em] mb-6 pl-4">Management</p>
          <div className="space-y-2">
            {menuItems.map((item) => (
              <Link
                key={item.name}
                to={item.path}
                onClick={() => setIsSidebarOpen(false)}
                className={`flex items-center px-4 py-3 rounded-xl transition-all duration-300 group ${
                  location.pathname === item.path 
                    ? 'bg-[#ff3e3e] text-white shadow-[0_0_20px_rgba(255,62,62,0.3)]' 
                    : 'hover:bg-white/5 hover:text-white'
                }`}
              >
                <svg className="w-5 h-5 mr-3 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d={item.icon} />
                </svg>
                <span className="text-xs font-bold uppercase tracking-widest">{item.name}</span>
              </Link>
            ))}
          </div>
        </nav>

        <div className="p-6 md:p-8 border-t border-white/5 space-y-4">
          <Link to="/" className="flex items-center text-[10px] font-black text-gray-500 hover:text-white transition-colors uppercase tracking-widest">
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Public Site
          </Link>
          <button onClick={handleLogout} className="flex items-center text-[10px] font-black text-[#ff3e3e] hover:text-white transition-colors uppercase tracking-widest w-full text-left">
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Logout
          </button>
        </div>
      </aside>

      {/* OVERLAY FOR MOBILE SIDEBAR */}
      {isSidebarOpen && (
        <div className="fixed inset-0 bg-black/60 z-[115] md:hidden" onClick={() => setIsSidebarOpen(false)} />
      )}

      <div className="flex-grow flex flex-col min-w-0">
        {/* TOP BAR WITH SEARCH */}
        <header className="hidden md:flex h-20 items-center justify-between px-12 border-b border-white/5 bg-[#0a0e17] sticky top-0 z-[100]">
          <div className="relative w-full max-w-xl">
            <span className="absolute inset-y-0 left-4 flex items-center pointer-events-none text-gray-500">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input 
              type="text" 
              placeholder="Quick search posts, comments, or fans..." 
              className="w-full bg-white/5 border border-white/10 rounded-2xl py-2.5 pl-12 pr-4 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#ff3e3e]/30 transition-all font-bold placeholder:text-gray-600"
              value={searchQuery}
              onChange={handleSearch}
            />
          </div>
          <div className="flex items-center gap-6 ml-4">
            <div className="text-right">
              <p className="text-[10px] font-black text-white uppercase tracking-widest mb-0">ADMIN ROOT</p>
              <p className="text-[8px] font-black text-gray-500 uppercase tracking-widest mb-0">SESSION SECURE</p>
            </div>
            <div className="w-10 h-10 rounded-xl bg-[#ff3e3e] flex items-center justify-center text-white font-black italic">AR</div>
          </div>
        </header>

        <main className="flex-grow p-4 md:p-12 overflow-y-auto bg-gradient-to-br from-[#0a0e17] to-[#05070a]">
          <div className="max-w-6xl mx-auto">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
