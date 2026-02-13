
import React, { useState, useEffect } from 'react';
import { fetchSportsData } from '../services/geminiService';
// Added imports for model selection
import { INITIAL_SETTINGS } from '../constants';
import { AIModel } from '../types';

const Standings: React.FC = () => {
  const [activeLeague, setActiveLeague] = useState('Premier League');
  const [data, setData] = useState('');
  const [loading, setLoading] = useState(true);
  // Added selectedModel state to manage AI model selection
  const [selectedModel, setSelectedModel] = useState<AIModel>(INITIAL_SETTINGS.selectedModel);

  useEffect(() => {
    // Load model preference from settings
    const storedSettings = localStorage.getItem('site_settings');
    if (storedSettings) {
      const settings = JSON.parse(storedSettings);
      setSelectedModel(settings.selectedModel || INITIAL_SETTINGS.selectedModel);
    }
  }, []);

  useEffect(() => {
    loadStandings();
  }, [activeLeague, selectedModel]);

  const loadStandings = async () => {
    setLoading(true);
    try {
      // FIX: Added selectedModel as the required 3rd argument to fetchSportsData
      const result = await fetchSportsData('STATS', `${activeLeague} Full Table, Tactical Breakdown, and Key Performers`, selectedModel);
      setData(result);
    } catch (e) {
      setData('### CONNECTION INTERRUPTED\nUnable to fetch standings at this time.');
    } finally {
      setLoading(false);
    }
  };

  // Helper to split AI response into sections if possible
  const sections = data.split(/#+ /).filter(s => s.trim()).map(s => {
    const lines = s.split('\n');
    return { title: lines[0], content: lines.slice(1).join('\n') };
  });

  const leagues = [
    'Premier League',
    'La Liga',
    'Serie A',
    'Bundesliga',
    'Ligue 1',
    'Champions League'
  ];

  return (
    <div className="container-fluid py-5 px-md-5 bg-black min-vh-100">
      <div className="row mb-5 align-items-end g-4">
        <div className="col-lg-7">
          <div className="d-flex align-items-center gap-3 mb-3">
             <span className="badge bg-electric-red rounded-0 font-condensed italic fw-black px-3 py-2">GLOBAL RANKINGS</span>
             <span className="text-white-50 font-monospace small">SOURCE: ELITE_PITCH_METRICS</span>
          </div>
          <h1 className="display-4 font-condensed fw-black italic text-white mb-2 tracking-tighter">LEAGUE INTELLIGENCE</h1>
          <p className="text-white text-opacity-40 font-condensed tracking-widest fw-black mb-0 fs-5 italic text-uppercase">Live Standings • Advanced Analytics • Tactical Rankings</p>
        </div>
        <div className="col-lg-5 text-lg-end">
           <div className="d-inline-flex align-items-center gap-4 bg-[#0a0e17] p-4 rounded-4 border border-white border-opacity-10 shadow-lg">
             <div className="text-end">
               <p className="text-[10px] font-black text-white-50 uppercase tracking-widest mb-1">DATA SYNC</p>
               <p className="text-[11px] font-black text-electric-red uppercase tracking-widest mb-0">OPTIMIZED & SECURE</p>
             </div>
             <button onClick={loadStandings} className="btn btn-danger rounded-circle p-3 d-flex align-items-center justify-content-center hover:bg-white hover:text-danger transition-all">
               <i className={`bi bi-arrow-clockwise fs-4 ${loading ? 'spin' : ''}`}></i>
             </button>
           </div>
        </div>
      </div>

      <div className="d-flex overflow-x-auto no-scrollbar gap-2 mb-5 pb-3 border-bottom border-white border-opacity-5">
        {leagues.map(l => (
          <button 
            key={l}
            onClick={() => setActiveLeague(l)}
            className={`btn btn-sm px-5 py-3 rounded-0 font-condensed tracking-widest fw-black transition-all text-nowrap italic border-bottom border-4 ${activeLeague === l ? 'text-white border-electric-red bg-white bg-opacity-5 shadow-lg' : 'text-secondary border-transparent hover:text-white hover:bg-white/5'}`}
          >
            {l.toUpperCase()}
          </button>
        ))}
      </div>

      <div className="row g-4">
        {loading ? (
          <div className="col-12 py-5 text-center">
            <div className="spinner-border text-danger mb-4" style={{width: '3rem', height: '3rem'}}></div>
            <h3 className="font-condensed fw-black italic text-white tracking-widest uppercase h4">Decrypting League Feed...</h3>
          </div>
        ) : sections.length > 0 ? (
          sections.map((section, idx) => (
            <div key={idx} className={idx === 0 ? "col-12" : "col-lg-6"}>
              <div className="bg-[#0a0e17] rounded-4 border border-white border-opacity-10 overflow-hidden shadow-2xl h-100">
                <div className="p-4 bg-dark border-bottom border-white border-opacity-5 d-flex align-items-center justify-content-between">
                   <div className="d-flex align-items-center">
                      <div className="bg-electric-red me-3" style={{width: '5px', height: '24px'}}></div>
                      <h2 className="h5 font-condensed fw-black text-white italic mb-0 uppercase tracking-widest">{section.title}</h2>
                   </div>
                   <i className="bi bi-shield-check text-white-50 opacity-20"></i>
                </div>
                <div className="p-4 p-md-5 bg-black bg-opacity-30">
                   <div className="markdown-content">
                      <div className="text-white opacity-90 fs-6 lh-lg" style={{ whiteSpace: 'pre-wrap' }}>
                        {section.content}
                      </div>
                   </div>
                </div>
              </div>
            </div>
          ))
        ) : (
          <div className="col-12 bg-dark p-5 rounded-4 text-center border border-white border-opacity-10">
            <h3 className="text-white-50 font-condensed fw-black italic">NO INTELLIGENCE RECORDED FOR THIS SECTOR.</h3>
          </div>
        )}
      </div>

      <style>{`
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
      `}</style>
    </div>
  );
};

export default Standings;
