
import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Post, UserPreferences, Page } from '../types';
import { MOCK_POSTS, DEFAULT_CATEGORIES } from '../constants';

interface LayoutProps {
  children: React.ReactNode;
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [pages, setPages] = useState<Page[]>([]);
  const [categories, setCategories] = useState<string[]>([]);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [currentTime, setCurrentTime] = useState(new Date());
  const location = useLocation();

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    
    const storedPosts = localStorage.getItem('site_posts');
    setPosts(storedPosts ? JSON.parse(storedPosts) : MOCK_POSTS);

    const storedPages = localStorage.getItem('site_pages');
    setPages(storedPages ? JSON.parse(storedPages).filter((p: Page) => p.isVisible) : []);

    const storedCats = localStorage.getItem('site_categories');
    setCategories(storedCats ? JSON.parse(storedCats) : DEFAULT_CATEGORIES);

    return () => clearInterval(timer);
  }, [location.pathname]);

  const navItems = [
    { name: 'HOME', path: '/', icon: 'bi-house-door' },
    { name: 'WATCH', path: '/watch', icon: 'bi-play-circle' },
    { name: 'BETTING', path: '/betting', icon: 'bi-currency-dollar' },
    { name: 'TABLES', path: '/tables', icon: 'bi-table' },
    { name: 'STORIES', path: '/stories', icon: 'bi-newspaper' },
  ];

  const isActive = (path: string) => location.pathname === path;

  return (
    <div className="d-flex flex-column min-vh-100 w-100 bg-black">
      
      {/* GLOBAL SHARE BUTTONS */}
      <div className="sharethis-floating-bar d-none d-md-flex flex-column position-fixed end-0 top-50 translate-middle-y z-[2000] gap-1 pr-1">
        <button className="btn btn-primary rounded-start-pill border-0 p-3 shadow-lg" style={{backgroundColor: '#1DA1F2'}}><i className="bi bi-twitter"></i></button>
        <button className="btn btn-primary rounded-start-pill border-0 p-3 shadow-lg" style={{backgroundColor: '#4267B2'}}><i className="bi bi-facebook"></i></button>
        <button className="btn btn-primary rounded-start-pill border-0 p-3 shadow-lg" style={{backgroundColor: '#E4405F'}}><i className="bi bi-instagram"></i></button>
        <button className="btn btn-primary rounded-start-pill border-0 p-3 shadow-lg" style={{backgroundColor: '#25D366'}}><i className="bi bi-whatsapp"></i></button>
      </div>

      {/* TOP BROADCAST BAR */}
      <div className="bg-dark border-bottom border-white border-opacity-5 py-2 px-4 d-none d-md-flex justify-content-between align-items-center" style={{fontSize: '11px'}}>
        <div className="d-flex gap-5">
          <span className="text-white-50 font-black uppercase tracking-widest d-flex align-items-center">
            <i className="bi bi-clock-fill text-electric-red me-2"></i>
            {currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })} UTC
          </span>
          <span className="text-white-50 font-black uppercase tracking-widest d-flex align-items-center">
            <i className="bi bi-calendar-check-fill text-electric-red me-2"></i>
            {currentTime.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}
          </span>
        </div>
        <div className="text-electric-red font-black uppercase tracking-[0.4em] italic shadow-sm">Global Football Wire v6.0 • PRIME BROADCAST</div>
      </div>

      <nav className="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-white border-opacity-10 sticky-top z-[1050] py-0">
        <div className="container-fluid px-0">
          <div className="bg-black border-end border-white border-opacity-10 d-flex align-items-center px-4" style={{ height: '70px' }}>
            <Link to="/" className="text-decoration-none d-flex align-items-center gap-2">
              <span className="bg-electric-red text-white px-2 py-1 font-condensed fw-black italic fs-5">GFW</span>
              <div className="lh-1 d-none d-sm-block">
                <span className="text-white font-condensed fw-black italic ls-tight block" style={{ fontSize: '18px' }}>GLOBAL FOOTBALL</span>
                <span className="text-white-50 font-condensed fw-bold italic block" style={{ fontSize: '10px', letterSpacing: '0.2em' }}>WATCH MEDIA</span>
              </div>
            </Link>
          </div>

          <div className="collapse navbar-collapse h-100">
            <ul className="navbar-nav me-auto mb-2 mb-lg-0 h-100 align-items-stretch">
              {navItems.map((item) => (
                <li key={item.name} className="nav-item d-flex align-items-stretch">
                  <Link to={item.path} className={`nav-link px-3 d-flex align-items-center font-condensed fw-black italic tracking-widest border-bottom border-3 transition-all ${isActive(item.path) ? 'text-white border-electric-red bg-white bg-opacity-5' : 'text-secondary border-transparent hover:text-white hover:bg-white/5'}`} style={{ fontSize: '11px' }}>
                    {item.name}
                  </Link>
                </li>
              ))}
              {pages.map(page => (
                <li key={page.id} className="nav-item d-flex align-items-stretch">
                   <Link to={`/page/${page.slug}`} className="nav-link px-3 d-flex align-items-center font-condensed fw-black italic tracking-widest text-secondary hover:text-white transition-all" style={{ fontSize: '11px' }}>
                     {page.title.toUpperCase()}
                   </Link>
                </li>
              ))}
            </ul>
          </div>

          <div className="d-flex align-items-center px-3 gap-2">
            <Link to="/admin/login" className="btn btn-outline-secondary btn-sm rounded-0 border-opacity-20 font-condensed fw-black italic px-3" style={{fontSize: '11px'}}>ADMIN HUB</Link>
          </div>
        </div>
      </nav>

      <div className="bg-electric-red overflow-hidden position-relative mb-0 shadow-lg" style={{ height: '35px', zIndex: 1000 }}>
        <div className="news-marquee h-100 d-flex align-items-center">
          <div className="marquee-container d-flex">
             {posts.concat(posts).map((p, idx) => (
                <Link key={`${p.id}-${idx}`} to={`/post/${p.id}`} className="marquee-item text-decoration-none d-flex align-items-center px-5">
                  <span className="text-black font-condensed fw-black italic text-uppercase text-nowrap mb-0 hover:text-white transition-all" style={{ fontSize: '12px' }}>
                    <span className="me-3 opacity-30">●</span> BREAKING: {p.title}
                  </span>
                </Link>
             ))}
          </div>
        </div>
      </div>

      <main className="flex-grow-1 bg-black min-vh-100 pt-0">
        {children}
      </main>

      <footer className="bg-black border-top border-white border-opacity-10 pt-5 pb-4 mt-auto">
        <div className="container-fluid px-5">
           <div className="row g-5 mb-5">
             <div className="col-lg-4">
                <h2 className="h4 font-condensed fw-black italic text-white mb-3 d-flex align-items-center">
                   <span className="text-electric-red me-2">GLOBAL</span> FOOTBALL WATCH
                </h2>
                <p className="text-white-50 small mb-4 font-monospace uppercase opacity-70 leading-relaxed max-w-sm">
                  The world's premier broadcast hub for elite football reporting, market price analysis, and tactical intelligence.
                </p>
                <div className="d-flex gap-3">
                  {['twitter', 'youtube', 'instagram', 'facebook'].map(s => (
                    <a key={s} href="#" className="bg-white bg-opacity-5 border border-white border-opacity-10 w-10 h-10 d-flex align-items-center justify-content-center text-white-50 hover:bg-electric-red hover:text-white transition-all rounded-circle"><i className={`bi bi-${s}`}></i></a>
                  ))}
                </div>
             </div>
             
             <div className="col-lg-8">
                <div className="row g-4">
                  <div className="col-md-6 col-lg-5">
                    <h4 className="text-white font-condensed fw-black italic uppercase tracking-widest fs-6 mb-4">CATEGORIES REGISTRY</h4>
                    <div className="row g-2">
                       {categories.map(cat => (
                         <div key={cat} className="col-6">
                           <Link to={`/category/${cat}`} className="text-white-50 text-decoration-none font-condensed fw-bold italic uppercase hover:text-electric-red transition-all" style={{fontSize: '11px'}}>• {cat}</Link>
                         </div>
                       ))}
                    </div>
                  </div>
                  <div className="col-md-6 col-lg-3">
                    <h4 className="text-white font-condensed fw-black italic uppercase tracking-widest fs-6 mb-4">SYSTEM</h4>
                    <div className="d-flex flex-column gap-2">
                       {navItems.map(item => (
                         <Link key={item.name} to={item.path} className="text-white-50 text-decoration-none font-condensed fw-bold italic uppercase hover:text-white transition-all" style={{fontSize: '11px'}}>{item.name}</Link>
                       ))}
                       {pages.map(page => (
                         <Link key={page.id} to={`/page/${page.slug}`} className="text-white-50 text-decoration-none font-condensed fw-bold italic uppercase hover:text-white transition-all" style={{fontSize: '11px'}}>{page.title}</Link>
                       ))}
                    </div>
                  </div>
                  <div className="col-md-12 col-lg-4">
                    <h4 className="text-white font-condensed fw-black italic uppercase tracking-widest fs-6 mb-4">BROADCAST STATUS</h4>
                    <div className="bg-white bg-opacity-5 p-3 border border-white border-opacity-10 rounded-3">
                       <div className="d-flex align-items-center gap-2 mb-2">
                         <div className="bg-success rounded-circle animate-pulse" style={{width: '6px', height: '6px'}}></div>
                         <span className="text-success font-black uppercase italic" style={{fontSize: '10px'}}>ENCRYPTED UPLINK LIVE</span>
                       </div>
                       <p className="text-white-50 font-monospace uppercase mb-0" style={{fontSize: '9px'}}>SECURITY: TLS_1.3_AES_256</p>
                       <p className="text-white-50 font-monospace uppercase mb-0" style={{fontSize: '9px'}}>REGION: GLOBAL_CLUSTER</p>
                    </div>
                  </div>
                </div>
             </div>
           </div>
           
           <div className="border-top border-white border-opacity-5 pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
             <p className="text-white-50 small mb-0 font-monospace uppercase opacity-50" style={{fontSize: '10px'}}>© {new Date().getFullYear()} GFW BROADCAST MEDIA | ALL RIGHTS RESERVED</p>
             <p className="text-white-50 small mb-0 font-monospace uppercase opacity-50" style={{fontSize: '10px'}}>BROADCAST STACK V6.0_STABLE</p>
           </div>
        </div>
      </footer>

      <style>{`
        .marquee-container { animation: marquee-scroll 60s linear infinite; white-space: nowrap; }
        .marquee-container:hover { animation-play-state: paused; }
        @keyframes marquee-scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .border-transparent { border-color: transparent !important; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
      `}</style>
    </div>
  );
};

export default Layout;
