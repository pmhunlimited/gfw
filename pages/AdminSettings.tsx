
import React, { useState, useEffect } from 'react';
import { SiteSettings, AIModel, Page } from '../types';
import { INITIAL_SETTINGS, DEFAULT_CATEGORIES } from '../constants';

const MODELS: { id: AIModel; name: string; provider: 'Google' | 'DeepSeek'; desc: string }[] = [
  { id: 'gemini-3-flash-preview', name: 'Gemini 3 Flash (Prime)', provider: 'Google', desc: 'Fastest next-gen reasoning engine' },
  { id: 'gemini-flash-latest', name: 'Gemini 1.5 Flash (Stable)', provider: 'Google', desc: 'Stable performance cluster' },
  { id: 'gemini-3-pro-preview', name: 'Gemini 3 Pro', provider: 'Google', desc: 'Advanced investigative analysis' },
  { id: 'gemini-2.5-flash-lite-latest', name: 'Gemini 2.5 Flash Lite', provider: 'Google', desc: 'Optimized efficiency cluster' },
  { id: 'deepseek-chat', name: 'DeepSeek-V3', provider: 'DeepSeek', desc: 'Deep creative intelligence' },
  { id: 'deepseek-reasoner', name: 'DeepSeek-R1', provider: 'DeepSeek', desc: 'Chain-of-thought logical verification' },
];

const AdminSettings: React.FC = () => {
  const [settings, setSettings] = useState<SiteSettings>(INITIAL_SETTINGS);
  const [categories, setCategories] = useState<string[]>([]);
  const [newCategory, setNewCategory] = useState('');
  const [pages, setPages] = useState<Page[]>([]);
  const [isEditingPage, setIsEditingPage] = useState(false);
  const [currentPage, setCurrentPage] = useState<Partial<Page>>({ title: '', slug: '', content: '', isVisible: true });
  const [activeTab, setActiveTab] = useState<'general' | 'ai' | 'pages' | 'categories' | 'smtp' | 'social'>('general');

  useEffect(() => {
    const stored = localStorage.getItem('site_settings');
    if (stored) setSettings(JSON.parse(stored));
    
    const storedPages = localStorage.getItem('site_pages');
    if (storedPages) setPages(JSON.parse(storedPages));

    const storedCats = localStorage.getItem('site_categories');
    setCategories(storedCats ? JSON.parse(storedCats) : DEFAULT_CATEGORIES);
  }, []);

  const handleSaveSettings = (e?: React.FormEvent) => {
    e?.preventDefault();
    localStorage.setItem('site_settings', JSON.stringify(settings));
    alert("Infrastructure parameters synchronized to mainframe.");
  };

  const handleSaveCategories = () => {
    localStorage.setItem('site_categories', JSON.stringify(categories));
    alert("Taxonomy registry updated.");
  };

  const addCategory = () => {
    if (newCategory && !categories.includes(newCategory)) {
      setCategories([...categories, newCategory]);
      setNewCategory('');
    }
  };

  const deleteCategory = (cat: string) => {
    if (confirm(`Decommission category "${cat}"?`)) {
      setCategories(categories.filter(c => c !== cat));
    }
  };

  const handleSavePage = () => {
    const pageToSave = {
      ...currentPage,
      id: currentPage.id || Date.now().toString(),
    } as Page;
    const updatedPages = currentPage.id ? pages.map(p => p.id === currentPage.id ? pageToSave : p) : [pageToSave, ...pages];
    setPages(updatedPages);
    localStorage.setItem('site_pages', JSON.stringify(updatedPages));
    setIsEditingPage(false);
    setCurrentPage({ title: '', slug: '', content: '', isVisible: true });
  };

  return (
    <div className="max-w-4xl space-y-8 pb-12">
      <h1 className="text-4xl font-condensed font-black italic uppercase text-white tracking-tighter">System Control</h1>

      <div className="bg-[#0a0e17] rounded-3xl border border-white/5 overflow-hidden shadow-2xl">
        <div className="flex flex-wrap border-b border-white/5 bg-black">
          {['general', 'ai', 'pages', 'categories', 'smtp', 'social'].map(tab => (
            <button key={tab} onClick={() => setActiveTab(tab as any)} className={`flex-1 min-w-[100px] py-4 text-[10px] font-black uppercase tracking-[0.15em] transition-all border-b-2 ${activeTab === tab ? 'text-[#ff3e3e] border-[#ff3e3e] bg-white/5' : 'text-gray-500 border-transparent hover:bg-white/5'}`}>
              {tab}
            </button>
          ))}
        </div>

        <div className="p-8 md:p-12">
          {activeTab === 'general' && (
            <form onSubmit={handleSaveSettings} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Broadcaster Identity</span>
                  <input type="text" className="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value={settings.name} onChange={e => setSettings({...settings, name: e.target.value})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Tagline</span>
                  <input type="text" className="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value={settings.tagline} onChange={e => setSettings({...settings, tagline: e.target.value})} />
                </label>
              </div>
              <button type="submit" className="bg-[#ff3e3e] text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">Commit Identity</button>
            </form>
          )}

          {activeTab === 'ai' && (
            <div className="space-y-8">
               <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                    <span className="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Gemini API Key</span>
                    <input type="password" placeholder="Paste Gemini Key..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value={settings.geminiApiKey || ''} onChange={e => setSettings({...settings, geminiApiKey: e.target.value})} />
                  </div>
                  <div className="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                    <span className="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">DeepSeek API Key</span>
                    <input type="password" placeholder="Paste DeepSeek Key..." className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value={settings.deepseekApiKey || ''} onChange={e => setSettings({...settings, deepseekApiKey: e.target.value})} />
                  </div>
               </div>
               <div className="space-y-3">
                 <span className="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-4">Central Intelligence Model</span>
                 {MODELS.map(m => (
                   <button key={m.id} onClick={() => setSettings({...settings, selectedModel: m.id})} className={`flex items-center justify-between p-5 rounded-2xl border w-full transition-all ${settings.selectedModel === m.id ? 'border-[#ff3e3e] bg-[#ff3e3e]/5' : 'border-white/5 bg-white/5 hover:bg-white/10'}`}>
                      <div className="text-left">
                        <span className="text-[8px] font-black px-1.5 py-0.5 rounded uppercase bg-white/10 text-gray-400 mr-2">{m.provider}</span>
                        <span className="text-sm font-black text-white uppercase italic">{m.name}</span>
                        <p className="text-[10px] text-gray-500 font-bold uppercase mt-1">{m.desc}</p>
                      </div>
                      {settings.selectedModel === m.id && <div className="w-2.5 h-2.5 rounded-full bg-[#ff3e3e] shadow-[0_0_12px_#ff3e3e]"></div>}
                   </button>
                 ))}
               </div>
               <button onClick={() => handleSaveSettings()} className="bg-[#ff3e3e] text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest">Update AI Logic</button>
            </div>
          )}

          {activeTab === 'categories' && (
            <div className="space-y-6">
               <h3 className="text-xl font-condensed font-black text-white uppercase italic">Taxonomy Engine</h3>
               <div className="flex gap-4 mb-8">
                 <input type="text" placeholder="New Category Name..." className="flex-grow bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-bold" value={newCategory} onChange={e => setNewCategory(e.target.value)} />
                 <button onClick={addCategory} className="bg-white/10 hover:bg-[#ff3e3e] text-white px-6 py-3 rounded-xl font-black uppercase italic transition-all">Add</button>
               </div>
               <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                  {categories.map(cat => (
                    <div key={cat} className="bg-white/5 border border-white/10 p-4 rounded-xl flex items-center justify-between group hover:border-[#ff3e3e]/50 transition-all">
                      <span className="text-[11px] font-black text-white uppercase italic tracking-tighter">{cat}</span>
                      <button onClick={() => deleteCategory(cat)} className="text-gray-500 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"><i className="bi bi-trash"></i></button>
                    </div>
                  ))}
               </div>
               <button onClick={handleSaveCategories} className="mt-8 bg-[#ff3e3e] text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest">Commit Taxonomy</button>
            </div>
          )}

          {activeTab === 'smtp' && (
            <form onSubmit={handleSaveSettings} className="space-y-8">
              <h3 className="text-xl font-condensed font-black text-white uppercase italic">Broadcast SMTP Cluster</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Host Address</span>
                  <input type="text" placeholder="e.g. smtp.gmail.com" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono" value={settings.smtp.host} onChange={e => setSettings({...settings, smtp: {...settings.smtp, host: e.target.value}})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Port</span>
                  <input type="text" placeholder="e.g. 587" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono" value={settings.smtp.port} onChange={e => setSettings({...settings, smtp: {...settings.smtp, port: e.target.value}})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Auth Username</span>
                  <input type="text" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value={settings.smtp.user} onChange={e => setSettings({...settings, smtp: {...settings.smtp, user: e.target.value}})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Auth Password</span>
                  <input type="password" title="Enter SMTP password" placeholder="••••••••" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value={settings.smtp.pass} onChange={e => setSettings({...settings, smtp: {...settings.smtp, pass: e.target.value}})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Sender Email Address</span>
                  <input type="email" placeholder="news@yourdomain.com" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value={settings.smtp.senderEmail} onChange={e => setSettings({...settings, smtp: {...settings.smtp, senderEmail: e.target.value}})} />
                </label>
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Sender Display Name</span>
                  <input type="text" placeholder="Site Identity" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value={settings.smtp.senderName} onChange={e => setSettings({...settings, smtp: {...settings.smtp, senderName: e.target.value}})} />
                </label>
              </div>
              <button type="submit" className="bg-[#ff3e3e] text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">Apply SMTP Config</button>
            </form>
          )}

          {activeTab === 'social' && (
            <div className="space-y-8">
               <h3 className="text-xl font-condensed font-black text-white uppercase italic">Syndication Matrix</h3>
               <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                 {['twitter', 'facebook', 'instagram'].map(p => (
                   <label key={p} className="block bg-white/5 p-6 rounded-2xl border border-white/10 shadow-sm">
                     <span className="text-[10px] font-black uppercase text-gray-500 block mb-3">{p} API Authorization Token</span>
                     <input type="password" placeholder={`Enter ${p} Token...`} className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white font-mono" value={(settings.socialAutoPost as any)[`${p}Token`]} onChange={e => setSettings({...settings, socialAutoPost: {...settings.socialAutoPost, [`${p}Token`]: e.target.value}})} />
                   </label>
                 ))}
                 <div className="flex items-center justify-between p-6 bg-[#ff3e3e]/5 border border-[#ff3e3e]/20 rounded-2xl shadow-inner">
                    <div className="flex flex-col">
                      <span className="text-[10px] font-black uppercase text-white tracking-widest mb-1">Global Auto-Post Broadcast</span>
                      <span className="text-[9px] text-gray-500 uppercase font-bold italic">Automatic syndication to social handles</span>
                    </div>
                    <label className="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" className="sr-only peer" checked={settings.socialAutoPost.enabled} onChange={e => setSettings({...settings, socialAutoPost: {...settings.socialAutoPost, enabled: e.target.checked}})} />
                      <div className="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-[#ff3e3e] after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                 </div>
               </div>
               <button onClick={() => handleSaveSettings()} className="bg-[#ff3e3e] text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">Commit Social Pulse</button>
            </div>
          )}

          {activeTab === 'pages' && (
            <div className="space-y-8">
               <div className="flex justify-between items-center">
                  <h3 className="text-xl font-condensed font-black text-white uppercase italic">CMS Infrastructure</h3>
                  {/* FIXED: hover:text-[#0a0e17] and explicit text color classes ensure readability on hover */}
                  <button 
                    onClick={() => { setIsEditingPage(true); setCurrentPage({ title: '', slug: '', content: '', isVisible: true }); }} 
                    className="bg-white/10 border border-white/10 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase transition-all hover:bg-white hover:text-[#0a0e17] focus:outline-none shadow-sm active:scale-95"
                  >
                    Deploy New Node
                  </button>
               </div>

               {isEditingPage ? (
                 <div className="space-y-6 bg-black/40 p-8 rounded-3xl border border-white/5 shadow-2xl">
                    <div className="grid grid-cols-2 gap-4">
                      <label className="block">
                        <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Page Title</span>
                        <input type="text" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-bold" value={currentPage.title} onChange={e => setCurrentPage({...currentPage, title: e.target.value, slug: e.target.value.toLowerCase().replace(/ /g, '-')})} />
                      </label>
                      <label className="block">
                        <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Registry Slug</span>
                        <input type="text" className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono" value={currentPage.slug} onChange={e => setCurrentPage({...currentPage, slug: e.target.value})} />
                      </label>
                    </div>
                    <label className="block">
                       <span className="text-[10px] font-black uppercase text-gray-500 block mb-2">Page Content</span>
                       <textarea className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white min-h-[350px]" value={currentPage.content} onChange={e => setCurrentPage({...currentPage, content: e.target.value})} />
                    </label>
                    <div className="flex justify-end gap-4 border-t border-white/5 pt-6">
                       <button onClick={() => setIsEditingPage(false)} className="text-gray-500 uppercase font-black text-[10px] hover:text-white transition-all">Abort</button>
                       <button onClick={handleSavePage} className="bg-[#ff3e3e] text-white px-8 py-2.5 rounded-xl font-black uppercase italic tracking-widest shadow-lg">Commit Node</button>
                    </div>
                 </div>
               ) : (
                 <div className="space-y-3">
                    {pages.map(page => (
                      <div key={page.id} className="flex items-center justify-between p-5 bg-white/5 rounded-2xl border border-white/5 hover:border-white/20 transition-all group shadow-sm">
                        <div>
                          <span className="text-white font-black uppercase italic text-sm tracking-tight">{page.title}</span>
                          <span className="text-[9px] text-gray-500 uppercase font-bold ml-4 tracking-[0.2em]">/{page.slug}</span>
                        </div>
                        <div className="flex gap-4 opacity-0 group-hover:opacity-100 transition-all">
                           <button onClick={() => { setCurrentPage(page); setIsEditingPage(true); }} className="text-gray-500 hover:text-white transition-all"><i className="bi bi-pencil-square fs-5"></i></button>
                           <button onClick={() => { setPages(pages.filter(p => p.id !== page.id)); localStorage.setItem('site_pages', JSON.stringify(pages.filter(p => p.id !== page.id))); }} className="text-gray-500 hover:text-red-500 transition-all"><i className="bi bi-trash fs-5"></i></button>
                        </div>
                      </div>
                    ))}
                    {pages.length === 0 && <p className="text-center text-gray-600 font-black uppercase italic text-xs py-12">No nodes deployed to current infrastructure.</p>}
                 </div>
               )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminSettings;
