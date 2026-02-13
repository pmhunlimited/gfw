
import React, { useState, useEffect } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { generateNewsArticle } from '../services/geminiService';
// Fixed: Removed non-existent Category import.
import { Post } from '../types';

const Watch: React.FC = () => {
  const [searchParams] = useSearchParams();
  const catParam = searchParams.get('category');
  
  const [posts, setPosts] = useState<Post[]>([]);
  const [videoSummary, setVideoSummary] = useState<string>('');
  const [isSummaryLoading, setIsSummaryLoading] = useState(false);
  const [selectedVideo, setSelectedVideo] = useState<Post | null>(null);

  useEffect(() => {
    loadContent();
  }, [catParam]);

  const loadContent = () => {
    const storedPosts = localStorage.getItem('site_posts');
    const allPosts: Post[] = storedPosts ? JSON.parse(storedPosts) : [];
    
    let filtered = allPosts.filter(p => p.videoUrl);
    if (catParam) {
      filtered = filtered.filter(p => p.category === catParam);
    }
    
    setPosts(filtered);
    if (filtered.length > 0) {
      handleVideoSelect(filtered[0]);
    } else {
      setSelectedVideo(null);
    }
  };

  const handleVideoSelect = async (video: Post) => {
    setSelectedVideo(video);
    setIsSummaryLoading(true);
    try {
      const summaryData = await generateNewsArticle(`Tactical Breakdown and Key Moments for: ${video.title}`, video.category);
      setVideoSummary(summaryData.content || "Detailed summary pending AI generation.");
    } catch (e) {
      setVideoSummary("Unable to generate tactical summary at this time.");
    } finally {
      setIsSummaryLoading(false);
    }
  };

  return (
    <div className="container-fluid py-4 px-md-5">
      <div className="mb-5">
        <h1 className="display-4 font-condensed fw-black italic text-white mb-2">EDITORIAL VIDEO HUB</h1>
        <p className="text-electric-red font-condensed tracking-widest fw-black uppercase">
          {catParam ? `FILTERED: ${catParam.toUpperCase()}` : 'ARCHIVED HIGHLIGHTS & TACTICAL ANALYTICS'}
        </p>
      </div>

      <div className="row g-4 justify-content-center">
        <div className="col-12 col-xl-10">
          {selectedVideo ? (
            <div className="bg-black border border-white border-opacity-10 rounded-4 overflow-hidden shadow-2xl mb-5">
              <div className="ratio ratio-16x9">
                {selectedVideo.videoUrl?.includes('youtube.com') || selectedVideo.videoUrl?.includes('youtu.be') ? (
                  <iframe 
                    src={`https://www.youtube.com/embed/${selectedVideo.videoUrl.split('v=')[1] || selectedVideo.videoUrl.split('/').pop()}`}
                    title={selectedVideo.title}
                    allowFullScreen
                  ></iframe>
                ) : (
                   <div className="d-flex flex-column align-items-center justify-content-center bg-dark">
                      <i className="bi bi-play-btn-fill fs-1 text-electric-red mb-3"></i>
                      <h4 className="font-condensed text-white uppercase">PLAYBACK READY</h4>
                   </div>
                )}
              </div>
              <div className="p-4 p-md-5 border-top border-white border-opacity-5">
                <div className="d-flex align-items-center gap-3 mb-3">
                  <span className="badge bg-electric-red font-condensed px-3 py-2 rounded-0 italic fw-black">{selectedVideo.category.toUpperCase()}</span>
                  <span className="text-white-50 font-monospace small uppercase opacity-50">{selectedVideo.date}</span>
                </div>
                <h2 className="display-6 font-condensed fw-black italic text-white mb-4">{selectedVideo.title}</h2>
                
                <div className="mt-5">
                  <h4 className="h6 font-condensed fw-black text-electric-red tracking-widest mb-3 border-bottom border-white border-opacity-5 pb-2 uppercase italic">AI TACTICAL ANALYSIS</h4>
                  {isSummaryLoading ? (
                    <div className="d-flex align-items-center gap-3 py-4">
                      <div className="spinner-border spinner-border-sm text-danger"></div>
                      <span className="text-white-50 font-condensed italic uppercase tracking-widest">Generating Insight...</span>
                    </div>
                  ) : (
                    <div className="text-white opacity-80 fs-5 lh-lg bg-white bg-opacity-5 p-4 rounded-4 border border-white border-opacity-5">
                      {videoSummary}
                    </div>
                  )}
                </div>
              </div>
            </div>
          ) : (
            <div className="bg-black border border-white border-opacity-10 rounded-4 overflow-hidden shadow-2xl p-5 text-center min-vh-50 d-flex flex-column align-items-center justify-content-center mb-5">
               <i className="bi bi-camera-reels fs-1 text-electric-red mb-3 opacity-20"></i>
               <h2 className="h3 font-condensed fw-black italic text-white uppercase">NO BROADCASTS FOUND</h2>
               <p className="text-white-50 font-condensed tracking-widest uppercase opacity-50">Select a different category or return later for fresh footage.</p>
            </div>
          )}
          
          <div className="p-4 p-md-5 bg-[#0a0e17] rounded-4 border border-white border-opacity-5 shadow-inner">
             <div className="d-flex align-items-center mb-4 gap-3">
                <div className="bg-electric-red" style={{width: '4px', height: '20px'}}></div>
                <h3 className="h5 font-condensed fw-black text-white italic mb-0 uppercase tracking-widest">VIDEO ARCHIVE</h3>
             </div>
             <div className="row g-4">
               {posts.map(post => (
                 <div key={post.id} className="col-sm-6 col-md-4 col-lg-3">
                    <div 
                      onClick={() => handleVideoSelect(post)}
                      className={`card bg-black border-white border-opacity-10 overflow-hidden cursor-pointer hover-shadow transition-all group h-100 ${selectedVideo?.id === post.id ? 'border-electric-red border-opacity-100 ring-1 ring-electric-red shadow-[0_0_20px_rgba(255,62,62,0.1)]' : 'hover:border-white hover:border-opacity-30'}`}
                    >
                       <div className="ratio ratio-16x9 overflow-hidden">
                          <img src={post.image} className="object-fit-cover grayscale group-hover:grayscale-0 group-hover:scale-110 transition-all duration-500" alt="" />
                          <div className="position-absolute inset-0 d-flex align-items-center justify-content-center bg-black bg-opacity-40 group-hover:bg-opacity-10 transition-all">
                            <i className="bi bi-play-circle text-white fs-2 opacity-80 group-hover:opacity-100 group-hover:scale-125 transition-all"></i>
                          </div>
                       </div>
                       <div className="p-3">
                         <span className="text-electric-red fw-black italic font-condensed mb-1 d-block" style={{fontSize: '9px'}}>{post.category.toUpperCase()}</span>
                         <h4 className="text-white font-condensed fw-bold mb-0 text-uppercase line-clamp-2 italic" style={{fontSize: '13px', lineHeight: '1.2'}}>{post.title}</h4>
                       </div>
                    </div>
                 </div>
               ))}
               {posts.length === 0 && <div className="col-12 text-center text-muted py-5 font-condensed italic uppercase tracking-widest opacity-30">Archive empty for this selection</div>}
             </div>
          </div>
        </div>
      </div>
      <style>{`
        .hover-shadow:hover { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); }
        .group-hover-scale { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .max-vh-50 { max-height: 50vh; }
      `}</style>
    </div>
  );
};

export default Watch;
