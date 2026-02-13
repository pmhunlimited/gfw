
import React, { useState, useEffect } from 'react';
import { fetchSportsData } from '../services/geminiService';
// Added imports for model selection
import { INITIAL_SETTINGS } from '../constants';
import { AIModel } from '../types';

const Betting: React.FC = () => {
  const [oddsData, setOddsData] = useState('Aggregating global market data...');
  const [isRefreshing, setIsRefreshing] = useState(false);
  // Added selectedModel state to manage AI model selection
  const [selectedModel, setSelectedModel] = useState<AIModel>(INITIAL_SETTINGS.selectedModel);

  useEffect(() => {
    // Load model preference from settings
    const storedSettings = localStorage.getItem('site_settings');
    if (storedSettings) {
      const settings = JSON.parse(storedSettings);
      setSelectedModel(settings.selectedModel || INITIAL_SETTINGS.selectedModel);
    }
    loadOdds();
  }, []);

  // Sync odds when selected model changes
  useEffect(() => {
    if (selectedModel) {
      loadOdds();
    }
  }, [selectedModel]);

  const loadOdds = async () => {
    setIsRefreshing(true);
    try {
      // FIX: Added selectedModel as the required 3rd argument to fetchSportsData
      const data = await fetchSportsData('ODDS', 'European Top 5 Leagues and major cups Market Analysis', selectedModel);
      setOddsData(data);
    } catch (e) {
      setOddsData('Market connection lost.');
    } finally {
      setIsRefreshing(false);
    }
  };

  return (
    <div className="container-fluid py-5 px-md-5 bg-black">
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
        <div>
          <div className="d-flex align-items-center gap-2 mb-3">
            <span className="badge bg-electric-red rounded-0 font-condensed italic fw-black px-3 py-2">LIVE MARKETS</span>
            <span className="text-white-50 font-monospace small">API: BET_QUANT_V4</span>
          </div>
          <h1 className="display-4 font-condensed fw-black italic text-white mb-2 tracking-tighter uppercase">BETTING INTELLIGENCE</h1>
          <p className="text-white-50 font-condensed tracking-widest fw-black mb-0 fs-5 italic uppercase">AI-POWERED PREDICTIVE MODELS • MARKET PRICE AGGREGATION</p>
        </div>
        <button onClick={loadOdds} className="btn btn-danger rounded-0 font-condensed fw-black tracking-widest px-5 py-3 shadow-lg hover:bg-white hover:text-danger transition-all border-0 uppercase">
          {isRefreshing ? 'CRUNCHING MARKETS...' : 'RE-SYNC INTELLIGENCE'}
        </button>
      </div>

      <div className="row g-4">
        {/* Main Probability Display */}
        <div className="col-lg-8">
          <div className="bg-[#0a0e17] rounded-4 border border-white border-opacity-10 shadow-2xl overflow-hidden h-100">
            <div className="p-4 bg-dark border-bottom border-white border-opacity-5 d-flex align-items-center">
              <i className="bi bi-graph-up-arrow text-electric-red fs-4 me-3"></i>
              <h3 className="h5 font-condensed fw-black text-white italic mb-0 uppercase tracking-widest">QUANTITATIVE PROBABILITY HUB</h3>
            </div>
            <div className="p-4 p-md-5 bg-black bg-opacity-20">
              {isRefreshing ? (
                <div className="d-flex flex-column align-items-center justify-content-center py-5">
                  <div className="spinner-grow text-danger mb-4" role="status"></div>
                  <p className="font-condensed text-white fw-black italic uppercase tracking-widest h5">Processing Simulations...</p>
                </div>
              ) : (
                <div className="markdown-content text-white opacity-90 fs-5 lh-lg" style={{ whiteSpace: 'pre-wrap' }}>
                  {oddsData}
                </div>
              )}
            </div>
          </div>
        </div>
        
        {/* Elite Picks Sidebar */}
        <div className="col-lg-4">
          <div className="card bg-[#0a0e17] border-electric-red border-opacity-30 rounded-4 mb-4 shadow-2xl overflow-hidden">
             <div className="card-header bg-black border-bottom border-white border-opacity-10 p-4">
                <h3 className="h4 font-condensed fw-black text-white italic mb-0 d-flex align-items-center uppercase">
                  <i className="bi bi-lightning-fill text-warning me-3 fs-5"></i>
                  ELITE ACCA PICKS
                </h3>
             </div>
             <div className="card-body p-4 bg-black bg-opacity-40">
                <div className="space-y-4">
                  {[
                    { match: 'Man City vs Arsenal', pick: 'Draw No Bet: City', confidence: 'High', prob: '82%', trend: 'up' },
                    { match: 'Real Madrid vs Atleti', pick: 'Over 2.5 Goals', confidence: 'Very High', prob: '91%', trend: 'up' },
                    { match: 'Inter vs Juve', pick: 'Both Teams to Score', confidence: 'Medium', prob: '68%', trend: 'down' },
                    { match: 'Dortmund vs Bayern', pick: 'Away Win', confidence: 'High', prob: '75%', trend: 'up' }
                  ].map((pick, i) => (
                    <div key={i} className="p-4 bg-white bg-opacity-5 rounded-4 d-flex justify-content-between align-items-center border border-white border-opacity-10 mb-3 group hover:border-electric-red transition-all cursor-default shadow-sm">
                       <div className="flex-grow-1 pr-3 border-start border-3 border-electric-red ps-3">
                         <p className="text-white fw-black font-condensed tracking-widest uppercase mb-1 opacity-70" style={{fontSize: '11px'}}>{pick.match}</p>
                         <p className="text-white fw-black font-condensed mb-0 fs-5 italic tracking-tight">{pick.pick}</p>
                       </div>
                       <div className="text-end">
                         <div className={`badge ${pick.confidence.includes('Very') ? 'bg-danger' : 'bg-dark border border-white border-opacity-20'} rounded-0 px-3 py-2 font-condensed italic fw-black mb-2 fs-6 shadow-sm`}>
                            {pick.prob}
                         </div>
                         <div className={`text-[9px] fw-black font-monospace ${pick.trend === 'up' ? 'text-success' : 'text-warning'} uppercase tracking-widest`}>
                           {pick.trend === 'up' ? '▲ TRENDING UP' : '▼ VOLATILE'}
                         </div>
                       </div>
                    </div>
                  ))}
                </div>
             </div>
             <div className="card-footer bg-black border-top border-white border-opacity-10 p-4 text-center">
                <p className="text-white-50 small mb-0 font-condensed fw-black italic tracking-widest uppercase" style={{fontSize: '10px'}}>LIVE ANALYTICS STREAM • AGGREGATED DAILY</p>
             </div>
          </div>

          <div className="card bg-[#05070a] border-white border-opacity-5 rounded-4 shadow-inner border-start border-5 border-white">
            <div className="card-body p-5 text-center">
              <i className="bi bi-shield-lock-fill fs-1 text-white text-opacity-10 mb-3 d-block"></i>
              <h3 className="h6 font-condensed fw-black text-white mb-3 tracking-widest uppercase">TERMS OF ENGAGEMENT</h3>
              <p className="small text-muted mb-4 italic leading-relaxed uppercase fw-bold" style={{fontSize: '9px'}}>
                Our AI analyzes over 10,000 data points per match. However, pitch results are inherently chaotic. GFW Media takes no responsibility for financial losses.
              </p>
              <div className="bg-white bg-opacity-5 p-3 rounded-0 text-white-50 text-xs font-monospace uppercase border border-white border-opacity-5">
                GFW_SECURE_AUTH: PRIME_99
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Betting;
