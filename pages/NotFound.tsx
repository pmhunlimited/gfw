
import React from 'react';
import { Link } from 'react-router-dom';
import { MOCK_POSTS } from '../constants';

const NotFound: React.FC = () => {
  const suggestions = MOCK_POSTS.slice(0, 3);

  return (
    <div className="max-w-4xl mx-auto px-4 py-24 text-center min-h-[70vh] flex flex-col justify-center">
      <div className="mb-12">
        <h1 className="text-9xl font-condensed font-extrabold text-[#ff3e3e] italic drop-shadow-xl">404</h1>
        <p className="text-3xl font-condensed font-black uppercase text-gray-900 dark:text-white mt-4 tracking-widest italic sharp-text">Offside! Page Not Found</p>
        <p className="text-gray-600 dark:text-gray-400 mt-6 max-w-lg mx-auto leading-relaxed">The referee has blown the whistle. The page you're looking for doesn't exist or has moved to a different pitch.</p>
      </div>

      <div className="max-w-lg mx-auto mb-16 w-full">
        <form className="relative group">
          <input
            type="text"
            placeholder="Search team news, players, or transfers..."
            className="w-full px-8 py-5 rounded-3xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 shadow-xl focus:outline-none focus:ring-4 focus:ring-[#ff3e3e]/20 transition-all text-gray-900 dark:text-white font-bold"
          />
          <button className="absolute right-3 top-1/2 -translate-y-1/2 bg-[#ff3e3e] text-white p-3 rounded-2xl shadow-lg hover:scale-110 transition-transform">
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          </button>
        </form>
      </div>

      <div className="text-left">
        <h2 className="text-2xl font-condensed font-black uppercase border-b-2 border-[#ff3e3e] pb-2 mb-8 inline-block text-gray-900 dark:text-white italic">Must Read Stories</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {suggestions.map(post => (
            <div key={post.id} className="group cursor-pointer">
              <div className="aspect-video rounded-2xl overflow-hidden mb-4 shadow-md">
                <img src={post.image} className="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="" />
              </div>
              <Link to={`/post/${post.id}`}>
                <h3 className="text-lg font-condensed font-bold uppercase text-gray-900 dark:text-white group-hover:text-[#ff3e3e] leading-tight transition-colors sharp-text">{post.title}</h3>
              </Link>
            </div>
          ))}
        </div>
      </div>

      <div className="mt-16">
        <Link to="/" className="inline-block bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-12 py-4 rounded-2xl font-black uppercase italic tracking-widest hover:bg-[#ff3e3e] hover:text-white transition-all shadow-2xl">
          Return to Pitch
        </Link>
      </div>
    </div>
  );
};

export default NotFound;
