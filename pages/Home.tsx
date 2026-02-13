
import React, { useState, useEffect, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Post, AIModel } from '../types';
import { fetchAndRefineNews, fetchSportsData, renderMarkdown } from '../services/geminiService';
import { INITIAL_SETTINGS } from '../constants';

const COMPETITIONS = [
  'UEFA Champions League',
  'English Premier League',
  'Spanish La Liga',
  'Italian Serie A'
];

const DATA_TYPES = [
  { id: 'LIVESCORE', label: 'LIVE UPDATES', icon: 'bi-broadcast' },
  { id: 'RESULTS', label: 'FULL TIME', icon: 'bi-check-circle' },
  { id: 'STATS', label: 'PERFORMANCE', icon: 'bi-graph-up' },
  { id: 'ODDS', label: 'MARKET PRICES', icon: 'bi-coin' }
] as const;

const Home: React.FC = () => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [activeComp, setActiveComp] = useState(COMPETITIONS[1]); 
  const [activeDataType, setActiveDataType] = useState<typeof DATA_TYPES[number]['id']>('LIVESCORE');
  const [sportsData, setSportsData] = useState<string>('');
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [selectedModel, setSelectedModel] = useState<AIModel>(INITIAL_SETTINGS.selectedModel);
  
  const navigate = useNavigate();

  useEffect(() => {
    const storedPosts = localStorage.getItem('site_posts');
    if (storedPosts) setPosts(JSON.parse(storedPosts));

    const storedSettings = localStorage.getItem('site_settings');
    if (storedSettings) {
      const settings = JSON.parse(storedSettings);
      setSelectedModel(settings.selectedModel || INITIAL_SETTINGS.selectedModel);
    }
    
    if (!storedPosts) initNews();
    loadSportsData();
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
      setSportsData("### CONNECTION ERROR\nAI broadcast signal lost.");
    } finally {
      setIsRefreshing(false);
    }
  };

  const latestPost = posts[0];
  const syndicatedNext = posts.slice(1, 4);
  const remainingPosts = posts.slice(4);

  return (
    <div className="container-fluid pt-0 px-0 bg-black overflow-x-hidden">
      
      {/* ELITE HERO BROADCAST */}
      <section className="row g-0 mb-5 border-bottom border-white border-opacity-10 bg-[#05070a]">
        {/* GLOBAL EXCLUSIVE - LATEST POST (LEFT ON LG) */}
        <div className="col-lg-8 border-end border-white border-opacity-10 position-relative" style={{ minHeight: '600px' }}>
          {latestPost ? (
            <Link to={`/post/${latestPost.id}`} className="text-decoration-none group d-block h-100 position-relative overflow-hidden">
              <img src={latestPost.image} className="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-60 grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-1000" alt="" />
              <div className="position-absolute bottom-0 start-0 w-100 p-4 p-md-5 bg-gradient-to-t from-black via-black/70 to-transparent z-20">
                <div className="mb-4">
                  <span className="badge bg-electric-red rounded-0 px-4 py-2 italic font-condensed tracking-tighter fw-black shadow-2xl border-0">GLOBAL EXCLUSIVE</span>
                </div>
                <h1 className="display-2 font-condensed fw-black text-white italic text-uppercase lh-1 mb-4 group-hover:text-electric-red transition-all tracking-tighter">{latestPost.title}</h1>
                <p className="lead text-white text-opacity-70 fw-bold text-uppercase fs-4 mb-0 line-clamp-2 max-w-4xl d-none d-md-block">{latestPost.excerpt}</p>
                <div className="mt-5">
                  <span className="btn btn-outline-light rounded-0 px-5 py-3 font-condensed fw-black italic tracking-widest hover:bg-white hover:text-black transition-all">DECRYPT FULL REPORT →</span>
                </div>
              </div>
            </Link>
          ) : (
            <div className="h-100 d-flex align-items-center justify-content-center bg-black/40">
              <div className="spinner-border text-danger" style={{width: '3rem', height: '3rem'}}></div>
            </div>
          )}
        </div>

        {/* SYNDICATED NEXT - VERTICAL STACK (RIGHT ON LG) */}
        <div className="col-lg-4 bg-[#0a0e17]">
          <div className="p-4 p-md-5 h-100 d-flex flex-column">
            <h3 className="h6 font-condensed tracking-widest text-electric-red mb-5 d-flex align-items-center fw-black uppercase">
              <span className="bg-electric-red me-3" style={{ width: '6px', height: '24px' }}></span>
              SYNDICATED NEXT
            </h3>
            <div className="d-flex flex-column flex-grow-1 gap-4">
              {syndicatedNext.map(post => (
                <Link key={post.id} to={`/post/${post.id}`} className="text-decoration-none group d-flex gap-4 border-bottom border-white border-opacity-5 pb-4 last:border-0 transition-all hover:translate-x-1">
                  <div className="flex-shrink-0 w-24 h-24 md:w-32 md:h-32 overflow-hidden border border-white border-opacity-10 rounded-2">
                    <img src={post.image} className="w-100 h-100 object-fit-cover grayscale group-hover:grayscale-0 group-hover:scale-110 transition-all duration-700" alt="" />
                  </div>
                  <div className="flex-grow-1 overflow-hidden">
                    <span className="text-electric-red fw-black italic font-condensed mb-2 d-block" style={{ fontSize: '10px' }}>{post.category.toUpperCase()}</span>
                    <h4 className="text-white font-condensed fw-black italic text-uppercase fs-5 line-clamp-2 group-hover:text-electric-red transition-all lh-1-2">{post.title}</h4>
                    <p className="text-white-50 text-[10px] font-monospace mt-2 uppercase opacity-40">{post.date}</p>
                  </div>
                </Link>
              ))}
              {syndicatedNext.length === 0 && <div className="flex-grow-1 d-flex align-items-center justify-content-center opacity-20"><p className="font-condensed italic uppercase tracking-widest">FEEDING LATEST DATA...</p></div>}
            </div>
          </div>
        </div>
      </section>

      {/* AI INTELLIGENCE HUB */}
      <section className="mb-5 bg-[#0a0e17] p-4 p-md-5 rounded-4 border border-white border-opacity-5 mx-2 shadow-2xl">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-4">
          <div className="d-flex align-items-center">
            <div className="bg-electric-red me-3" style={{ width: '6px', height: '40px' }}></div>
            <h2 className="h2 font-condensed fw-black italic text-white mb-0 uppercase">AI INTELLIGENCE WIRE</h2>
          </div>
          <div className="d-flex gap-2 overflow-x-auto no-scrollbar pb-1">
            {COMPETITIONS.map(comp => (
              <button key={comp} onClick={() => setActiveComp(comp)} className={`btn btn-sm px-4 py-2 rounded-0 font-condensed fw-black transition-all text-nowrap italic ${activeComp === comp ? 'btn-danger shadow-[0_0_15px_rgba(255,62,62,0.3)]' : 'btn-outline-secondary opacity-50 hover:opacity-100'}`}>
                {comp.toUpperCase()}
              </button>
            ))}
          </div>
        </div>

        <div className="row g-4">
          <div className="col-md-4 col-xl-3">
             <div className="d-flex d-md-block overflow-x-auto no-scrollbar gap-2 mb-4">
                {DATA_TYPES.map(type => (
                  <button key={type.id} onClick={() => setActiveDataType(type.id)} className={`nav-link text-start rounded-0 py-3 px-4 font-condensed tracking-widest fw-black d-flex align-items-center justify-content-between transition-all mb-3 w-100 ${activeDataType === type.id ? 'active bg-electric-red text-white shadow-xl translate-x-1' : 'text-secondary bg-white bg-opacity-5 hover:bg-white/10 hover:text-white'}`}>
                      <span className="h6 mb-0 italic">{type.label}</span>
                      <i className={`bi ${type.icon} fs-5 opacity-50`}></i>
                  </button>
                ))}
             </div>
          </div>
          <div className="col-md-8 col-xl-9">
             <div className="bg-black bg-opacity-60 p-4 p-md-5 rounded-4 border border-white border-opacity-5 min-vh-60 shadow-inner">
                {isRefreshing ? (
                  <div className="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                    <div className="spinner-grow text-danger mb-4"></div>
                    <p className="font-condensed text-white fw-black italic uppercase tracking-widest h5">Decrypting Live Stream...</p>
                  </div>
                ) : (
                  <div className="intelligence-content text-white opacity-90 fs-6" dangerouslySetInnerHTML={{ __html: renderMarkdown(sportsData) }}></div>
                )}
             </div>
          </div>
        </div>
      </section>

      {/* ELITE REPORTING GRID */}
      <section className="p-4 p-md-5 bg-black">
        <h2 className="display-5 font-condensed fw-black italic text-white mb-5 border-bottom border-white border-opacity-5 pb-3">ELITE REPORTING</h2>
        <div className="row g-5">
          {remainingPosts.map(post => (
            <div key={post.id} className="col-sm-6 col-md-4 col-lg-3">
              <Link to={`/post/${post.id}`} className="card h-100 bg-transparent border-0 group text-decoration-none">
                <div className="ratio ratio-1x1 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                  <img src={post.image} className="object-fit-cover transition-all duration-700 grayscale group-hover:grayscale-0 group-hover:scale-110" alt="" />
                  <div className="position-absolute top-0 start-0 m-3">
                    <span className="badge bg-electric-red font-condensed italic fw-black px-3 py-2 uppercase shadow-lg" style={{fontSize: '9px'}}>{post.category}</span>
                  </div>
                </div>
                <h3 className="h5 text-white fw-black text-uppercase line-clamp-2 mb-2 group-hover:text-electric-red transition-all italic tracking-tight lh-1-2">{post.title}</h3>
                <p className="text-white-50 text-xs line-clamp-2 opacity-60 font-medium leading-relaxed">{post.excerpt}</p>
              </Link>
            </div>
          ))}
          {remainingPosts.length === 0 && <div className="col-12 py-5 text-center text-white-50 opacity-20 font-condensed italic uppercase">Archive Synchronization Pending...</div>}
        </div>
      </section>
      
      <style>{`
        .min-vh-60 { min-height: 60vh; }
        .intelligence-content h2, .intelligence-content h3 { font-family: 'Barlow Condensed', sans-serif; text-transform: uppercase; letter-spacing: -0.01em; font-style: italic; }
        .lh-1-2 { line-height: 1.2; }
      `}</style>
    </div>
  );
};

export default Home;
