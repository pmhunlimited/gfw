
import React, { useState, useEffect, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Post, AIModel } from '../types';
import { fetchAndRefineNews, fetchSportsData, renderMarkdown } from '../services/geminiService';
import { INITIAL_SETTINGS, DEFAULT_CATEGORIES } from '../constants';

const COMPETITIONS = [
  'UEFA Champions League',
  'English Premier League',
  'UEFA Europa League',
  'Spanish La Liga',
  'Italian Serie A'
];

const DATA_TYPES = [
  { id: 'LIVESCORE', label: 'LIVE UPDATES', icon: 'bi-broadcast' },
  { id: 'RESULTS', label: 'FULL TIME', icon: 'bi-check-circle' },
  { id: 'SCHEDULE', label: 'UPCOMING', icon: 'bi-calendar-event' },
  { id: 'STATS', label: 'PERFORMANCE', icon: 'bi-graph-up' },
  { id: 'LINEUPS', label: 'TEAM SHEETS', icon: 'bi-people' },
  { id: 'ODDS', label: 'MARKET PRICES', icon: 'bi-coin' }
] as const;

const Home: React.FC = () => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [activeComp, setActiveComp] = useState(COMPETITIONS[1]); 
  const [activeDataType, setActiveDataType] = useState<typeof DATA_TYPES[number]['id']>('LIVSCORE');
  const [sportsData, setSportsData] = useState<string>('');
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [selectedModel, setSelectedModel] = useState<AIModel>(INITIAL_SETTINGS.selectedModel);
  
  const [currentSlide, setCurrentSlide] = useState(0);
  const sliderInterval = useRef<number | null>(null);
  const refreshTimer = useRef<number | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    // FIXED: Variable names corrected to remove illegal spaces
    const storedPosts = localStorage.getItem('site_posts');
    if (storedPosts) setPosts(JSON.parse(storedPosts));

    const storedSettings = localStorage.getItem('site_settings');
    if (storedSettings) {
      const settings = JSON.parse(storedSettings);
      setSelectedModel(settings.selectedModel || INITIAL_SETTINGS.selectedModel);
    }
    
    if (!storedPosts) initNews();
    
    loadSportsData();
    refreshTimer.current = window.setInterval(() => loadSportsData(true), 300000);

    return () => {
      if (refreshTimer.current) clearInterval(refreshTimer.current);
      if (sliderInterval.current) clearInterval(sliderInterval.current);
    };
  }, []);

  useEffect(() => {
    loadSportsData();
  }, [activeComp, activeDataType]);

  const initNews = async () => {
    const allNews = await fetchAndRefineNews("Premier League", "Premier League", selectedModel);
    setPosts(allNews);
    localStorage.setItem('site_posts', JSON.stringify(allNews));
  };

  const loadSportsData = async (silent = false) => {
    if (!silent) setIsRefreshing(true);
    try {
      const data = await fetchSportsData(activeDataType, activeComp, selectedModel);
      setSportsData(data);
    } catch (e) {
      setSportsData("### CONNECTION ERROR\nAI broadcast synchronization failed.");
    } finally {
      setIsRefreshing(false);
    }
  };

  const latestPosts = posts.slice(0, 4);
  const featuredStories = posts.filter(p => p.isTopStory).slice(0, 5);
  const followingNext = posts.slice(4, 7);

  useEffect(() => {
    if (featuredStories.length > 1) {
      if (sliderInterval.current) clearInterval(sliderInterval.current);
      sliderInterval.current = window.setInterval(() => setCurrentSlide(p => (p + 1) % featuredStories.length), 6000);
    }
    return () => { if (sliderInterval.current) clearInterval(sliderInterval.current); };
  }, [featuredStories.length]);

  return (
    <div className="container-fluid pt-0 px-0 overflow-x-hidden bg-black">
      
      {/* HERO SLIDER */}
      <div className="row g-0 mb-5 border-bottom border-white border-opacity-10 min-vh-75 position-relative overflow-hidden bg-black">
        <div className="col-lg-8 border-end border-white border-opacity-10 d-flex flex-column position-relative" style={{minHeight: '650px'}}>
          {featuredStories.length > 0 ? (
            <div className="h-100 w-100 position-relative">
              {featuredStories.map((post, idx) => (
                <div key={post.id} className={`position-absolute top-0 start-0 w-100 h-100 transition-all duration-1000 ease-in-out ${idx === currentSlide ? 'opacity-100 z-10' : 'opacity-0 z-0'}`}>
                  <img src={post.image} className="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-40 grayscale" alt="" style={{objectPosition: 'center 30%'}} />
                  <div className="p-4 p-md-5 h-100 d-flex flex-column justify-content-center position-relative z-20 bg-gradient-to-t from-black via-transparent to-transparent">
                    <div className="mb-4">
                      <span className="badge bg-electric-red rounded-0 px-3 py-2 italic font-condensed tracking-tighter fw-black">GLOBAL EXCLUSIVE</span>
                    </div>
                    <Link to={`/post/${post.id}`} className="text-decoration-none group">
                      <h1 className="display-2 font-condensed fw-black text-white italic text-uppercase lh-1 mb-4 group-hover:text-electric-red transition-all tracking-tighter">{post.title}</h1>
                      <p className="lead text-white text-opacity-50 fw-bold text-uppercase fs-4 mb-0 line-clamp-2 max-w-3xl d-none d-md-block">{post.excerpt}</p>
                      <div className="mt-4 mt-md-5">
                        <span className="btn btn-outline-light rounded-0 px-5 py-3 font-condensed fw-black italic tracking-widest hover:bg-electric-red hover:border-electric-red transition-all">VIEW INTEL REPORT →</span>
                      </div>
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="p-5 text-center flex-grow-1 d-flex align-items-center justify-content-center">
              <div className="spinner-border text-danger"></div>
            </div>
          )}
        </div>
        
        <div className="col-lg-4 bg-[#0a0e17]">
           <div className="p-4 p-md-5 h-100 d-flex flex-column">
              <h3 className="h6 font-condensed tracking-widest text-electric-red mb-4 d-flex align-items-center fw-black">SYNDICATED NEXT</h3>
              <div className="space-y-4 flex-grow-1">
                 {followingNext.map(post => (
                   <Link key={post.id} to={`/post/${post.id}`} className="d-block text-decoration-none group mb-4">
                      <div className="d-flex gap-4 align-items-center">
                         <div className="flex-shrink-0 w-32 h-20 overflow-hidden border border-white border-opacity-10 bg-black">
                            <img src={post.image} className="w-100 h-100 object-fit-cover grayscale group-hover:grayscale-0 group-hover:scale-110 transition-all duration-500" alt="" />
                         </div>
                         <div className="overflow-hidden">
                            <span className="text-electric-red fw-black italic font-condensed" style={{fontSize: '9px'}}>{post.category.toUpperCase()}</span>
                            <h4 className="text-white font-condensed fw-black italic text-uppercase text-xs line-clamp-2 mt-1 group-hover:text-electric-red transition-all lh-1">{post.title}</h4>
                         </div>
                      </div>
                   </Link>
                 ))}
              </div>
           </div>
        </div>
      </div>

      {/* AI INTELLIGENCE HUB */}
      <section className="mb-5 bg-[#0a0e17] p-4 p-md-5 rounded-4 border border-white border-opacity-5 mx-2 shadow-2xl">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-4">
          <div className="d-flex align-items-center">
            <div className="bg-electric-red me-3" style={{width: '6px', height: '40px'}}></div>
            <h2 className="h2 font-condensed fw-black italic text-white mb-0 uppercase">AI INTELLIGENCE WIRE</h2>
          </div>
          <div className="d-flex gap-2 overflow-x-auto no-scrollbar pb-1">
            {COMPETITIONS.map(comp => (
              <button key={comp} onClick={() => setActiveComp(comp)} className={`btn btn-sm px-4 py-2 rounded-0 font-condensed fw-black transition-all text-nowrap italic ${activeComp === comp ? 'btn-danger' : 'btn-outline-secondary opacity-50'}`}>
                {comp.toUpperCase()}
              </button>
            ))}
          </div>
        </div>

        <div className="row g-4">
          <div className="col-md-4 col-xl-3">
             <div className="d-flex d-md-block overflow-x-auto no-scrollbar gap-2 mb-4">
                {DATA_TYPES.map(type => (
                  <button key={type.id} onClick={() => setActiveDataType(type.id)} className={`nav-link text-start rounded-0 py-3 px-4 font-condensed tracking-widest fw-black d-flex align-items-center justify-content-between transition-all mb-3 w-100 ${activeDataType === type.id ? 'active bg-electric-red text-white' : 'text-secondary bg-white bg-opacity-5 hover:text-white'}`}>
                      <span className="h6 mb-0 italic">{type.label}</span>
                      <i className={`bi ${type.icon} fs-5 opacity-50`}></i>
                  </button>
                ))}
             </div>
          </div>
          <div className="col-md-8 col-xl-9">
             <div className="bg-black bg-opacity-60 p-4 p-md-5 rounded-4 border border-white border-opacity-5 min-vh-60">
                {isRefreshing ? (
                  <div className="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                    <div className="spinner-grow text-danger mb-4"></div>
                    <p className="font-condensed text-white fw-black italic uppercase tracking-widest">Decrypting Live Stream...</p>
                  </div>
                ) : (
                  <div className="intelligence-content text-white opacity-90 fs-6" dangerouslySetInnerHTML={{ __html: renderMarkdown(sportsData) }}></div>
                )}
             </div>
          </div>
        </div>
      </section>

      {/* GRID LAYOUT */}
      <section className="p-4 p-md-5 bg-black">
        <h2 className="display-5 font-condensed fw-black italic text-white mb-5 border-bottom border-white border-opacity-5 pb-3">ELITE REPORTING</h2>
        <div className="row g-4">
          {latestPosts.map(post => (
            <div key={post.id} className="col-sm-6 col-lg-3">
              <Link to={`/post/${post.id}`} className="card h-100 bg-transparent border-0 group text-decoration-none">
                <div className="ratio ratio-1x1 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                  <img src={post.image} className="object-fit-cover transition-all duration-700 grayscale group-hover:grayscale-0 group-hover:scale-110" alt="" />
                  <div className="position-absolute top-0 start-0 m-3">
                    <span className="badge bg-electric-red font-condensed italic fw-black px-3 py-2 uppercase shadow-sm" style={{fontSize: '9px'}}>{post.category}</span>
                  </div>
                </div>
                <h3 className="h5 text-white fw-black text-uppercase line-clamp-2 mb-2 group-hover:text-electric-red transition-all italic tracking-tight">{post.title}</h3>
                <p className="text-white-50 text-xs line-clamp-3 opacity-60 font-medium">{post.excerpt}</p>
              </Link>
            </div>
          ))}
        </div>
      </section>
      
      <style>{`
        .min-vh-60 { min-height: 60vh; }
        .intelligence-content h2, .intelligence-content h3 { font-family: 'Barlow Condensed', sans-serif; text-transform: uppercase; letter-spacing: -0.01em; font-style: italic; }
      `}</style>
    </div>
  );
};

export default Home;
