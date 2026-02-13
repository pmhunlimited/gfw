
import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
// Fixed: Removed non-existent Category import.
import { Post, Comment } from '../types';
// Fixed: Imported DEFAULT_CATEGORIES.
import { MOCK_POSTS, DEFAULT_CATEGORIES } from '../constants';
import { compressImage } from '../services/imageService';
import { generatePostImage } from '../services/geminiService';
import { autoPostToSocials } from '../services/socialService';

const AdminPosts: React.FC = () => {
  const [posts, setPosts] = useState<Post[]>([]);
  const [isEditing, setIsEditing] = useState(false);
  const [currentPost, setCurrentPost] = useState<Partial<Post>>({ 
    seo: { metaTitle: '', metaDescription: '', keywords: '' }, 
    tags: [],
    socialChannels: { twitter: true, facebook: true, instagram: false },
    isScheduled: false,
    publishDate: new Date().toISOString().split('T')[0]
  });
  const [loadingImage, setLoadingImage] = useState(false);
  const [imagePrompt, setImagePrompt] = useState('');
  const [showImageDialog, setShowImageDialog] = useState(false);
  
  const [searchParams] = useSearchParams();
  const searchQuery = (searchParams.get('search') || '').toLowerCase();

  useEffect(() => {
    const storedPosts = localStorage.getItem('site_posts');
    setPosts(storedPosts ? JSON.parse(storedPosts) : MOCK_POSTS);
  }, []);

  const handleSave = async () => {
    const postToSave = {
      ...currentPost,
      id: currentPost.id || Date.now().toString(),
      date: currentPost.date || new Date().toISOString().split('T')[0],
      author: 'Admin Control'
    } as Post;

    let updatedPosts;
    if (currentPost.id) {
      updatedPosts = posts.map(p => p.id === currentPost.id ? postToSave : p);
    } else {
      updatedPosts = [postToSave, ...posts];
      // Simulated Autopost
      await autoPostToSocials(postToSave);
    }

    setPosts(updatedPosts);
    localStorage.setItem('site_posts', JSON.stringify(updatedPosts));
    setIsEditing(false);
    setCurrentPost({ 
      seo: { metaTitle: '', metaDescription: '', keywords: '' }, 
      tags: [],
      socialChannels: { twitter: true, facebook: true, instagram: false },
      isScheduled: false
    });
  };

  const handleGenerateImage = async () => {
    if (!imagePrompt.trim()) return;
    setLoadingImage(true);

    // Added API Key selection check for high-quality models per Gemini API guidelines.
    // Users must select a key from a paid GCP project.
    if (typeof window !== 'undefined' && (window as any).aistudio) {
      const aistudio = (window as any).aistudio;
      const hasKey = await aistudio.hasSelectedApiKey();
      if (!hasKey) {
        await aistudio.openSelectKey();
        // Guideline: assume the key selection was successful after triggering openSelectKey()
      }
    }

    try {
      const imageUrl = await generatePostImage(imagePrompt);
      if (imageUrl) {
        setCurrentPost({ ...currentPost, image: imageUrl });
        setShowImageDialog(false);
      }
    } catch (error: any) {
      // Per guidelines, if request fails due to missing entity/key config, re-prompt the user
      if (error?.message?.includes("Requested entity was not found") && (window as any).aistudio) {
        await (window as any).aistudio.openSelectKey();
      }
      console.error("AI image generation error:", error);
    } finally {
      setLoadingImage(false);
    }
  };

  const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setLoadingImage(true);
      const compressed = await compressImage(file);
      setCurrentPost({ ...currentPost, image: compressed });
      setLoadingImage(false);
    }
  };

  const filteredPosts = posts.filter(p => 
    p.title.toLowerCase().includes(searchQuery) || 
    p.category.toLowerCase().includes(searchQuery)
  );

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-condensed font-black italic uppercase text-white">Editorial Control</h1>
        <button onClick={() => setIsEditing(true)} className="bg-[#ff3e3e] text-white px-8 py-3 rounded-xl font-black uppercase italic shadow-lg">Compose Broadcast</button>
      </div>

      {isEditing ? (
        <div className="bg-[#0a0e17] p-8 rounded-2xl border border-white/10 space-y-8">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div className="space-y-6">
              <label className="block">
                <span className="text-[10px] font-black uppercase text-gray-500">Headline</span>
                <input type="text" className="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-bold" value={currentPost.title || ''} onChange={e => setCurrentPost({...currentPost, title: e.target.value})} />
              </label>
              
              <div className="grid grid-cols-2 gap-4">
                <label className="block">
                  <span className="text-[10px] font-black uppercase text-gray-500">Category</span>
                  {/* Fixed: Used DEFAULT_CATEGORIES for mapping and removed non-existent Category type cast. */}
                  <select className="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-bold" value={currentPost.category || ''} onChange={e => setCurrentPost({...currentPost, category: e.target.value})}>
                    <option value="">Select...</option>
                    {DEFAULT_CATEGORIES.map(cat => <option key={cat} value={cat}>{cat}</option>)}
                  </select>
                </label>
                <div className="flex items-end gap-2">
                  <button onClick={() => setShowImageDialog(true)} className="flex-1 bg-white/5 border border-white/10 rounded-xl py-3 text-[10px] font-black uppercase text-white hover:bg-[#ff3e3e]">AI Image</button>
                  <label className="flex-1 bg-white/5 border border-white/10 rounded-xl py-3 text-[10px] font-black uppercase text-white text-center cursor-pointer">
                    Upload
                    <input type="file" className="hidden" onChange={handleFileUpload} />
                  </label>
                </div>
              </div>

              {/* SOCIAL CHANNELS */}
              <div className="bg-white/5 p-6 rounded-2xl border border-white/5">
                 <h3 className="text-[10px] font-black uppercase text-[#ff3e3e] mb-4 tracking-widest">SYNDICATION CHANNELS</h3>
                 <div className="flex gap-4">
                    {['twitter', 'facebook', 'instagram'].map(ch => (
                      <label key={ch} className="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" checked={(currentPost.socialChannels as any)?.[ch]} onChange={e => setCurrentPost({...currentPost, socialChannels: {...currentPost.socialChannels!, [ch]: e.target.checked}})} className="hidden" />
                        <div className={`w-8 h-8 rounded-lg flex items-center justify-center border transition-all ${(currentPost.socialChannels as any)?.[ch] ? 'bg-[#ff3e3e] border-[#ff3e3e]' : 'bg-black/40 border-white/10 opacity-50'}`}>
                           <i className={`bi bi-${ch}`}></i>
                        </div>
                      </label>
                    ))}
                 </div>
              </div>

              {/* SCHEDULING */}
              <div className="bg-white/5 p-6 rounded-2xl border border-white/5">
                 <div className="flex items-center justify-between mb-4">
                    <h3 className="text-[10px] font-black uppercase text-white tracking-widest">PUBLISH SCHEDULE</h3>
                    <label className="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" className="sr-only peer" checked={currentPost.isScheduled} onChange={e => setCurrentPost({...currentPost, isScheduled: e.target.checked})} />
                      <div className="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                    </label>
                 </div>
                 {currentPost.isScheduled && (
                    <input type="date" className="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" value={currentPost.publishDate} onChange={e => setCurrentPost({...currentPost, publishDate: e.target.value})} />
                 )}
              </div>
            </div>
            
            <div className="space-y-6">
              <label className="block">
                <span className="text-[10px] font-black uppercase text-gray-500">Excerpt</span>
                <textarea className="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm" rows={3} value={currentPost.excerpt || ''} onChange={e => setCurrentPost({...currentPost, excerpt: e.target.value})} />
              </label>
              <label className="block">
                <span className="text-[10px] font-black uppercase text-gray-500">Content (Markdown Supported)</span>
                <textarea className="mt-1 w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm min-h-[400px] font-mono" value={currentPost.content || ''} onChange={e => setCurrentPost({...currentPost, content: e.target.value})} />
              </label>
            </div>
          </div>
          
          <div className="flex justify-end space-x-6 border-t border-white/5 pt-8">
            <button onClick={() => setIsEditing(false)} className="text-gray-500 font-black uppercase text-xs">Cancel</button>
            <button onClick={handleSave} className="bg-[#ff3e3e] text-white px-12 py-4 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">COMMIT BROADCAST</button>
          </div>
        </div>
      ) : (
        <div className="bg-[#0a0e17] rounded-3xl border border-white/5 overflow-hidden shadow-2xl overflow-x-auto">
          <table className="w-full text-left min-w-[800px]">
            <thead className="bg-black/60 text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 border-b border-white/5">
              <tr>
                <th className="px-8 py-6">Asset</th>
                <th className="px-8 py-6">Headline</th>
                <th className="px-8 py-6">Category</th>
                <th className="px-8 py-6 text-center">Channels</th>
                <th className="px-8 py-6">Status</th>
                <th className="px-8 py-6">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {filteredPosts.map(post => (
                <tr key={post.id} className="hover:bg-white/5 transition-colors">
                  <td className="px-8 py-6"><img src={post.image} className="w-12 h-12 rounded-xl object-cover" alt="" /></td>
                  <td className="px-8 py-6"><span className="font-bold text-white uppercase italic text-sm line-clamp-1">{post.title}</span></td>
                  <td className="px-8 py-6"><span className="bg-white/5 text-[9px] px-2 py-1 rounded font-black uppercase text-gray-400">{post.category}</span></td>
                  <td className="px-8 py-6 text-center">
                    <div className="flex justify-center gap-1">
                      {post.socialChannels?.twitter && <i className="bi bi-twitter text-blue-400"></i>}
                      {post.socialChannels?.facebook && <i className="bi bi-facebook text-blue-600"></i>}
                    </div>
                  </td>
                  <td className="px-8 py-6">
                    <span className={`text-[9px] font-black px-2 py-1 rounded uppercase ${post.isScheduled ? 'bg-blue-500/10 text-blue-400' : 'bg-green-500/10 text-green-400'}`}>
                       {post.isScheduled ? 'Scheduled' : 'Live'}
                    </span>
                  </td>
                  <td className="px-8 py-6">
                    <button onClick={() => { setCurrentPost(post); setIsEditing(true); }} className="text-gray-500 hover:text-white"><i className="bi bi-pencil-square"></i></button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {showImageDialog && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/95">
          <div className="bg-[#0a0e17] border border-white/10 w-full max-w-md p-8 rounded-3xl">
            <h3 className="text-xl font-condensed font-black text-white italic mb-4">ASSET GENERATOR</h3>
            <textarea className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-white text-xs mb-4" rows={4} placeholder="Describe the visual asset..." value={imagePrompt} onChange={e => setImagePrompt(e.target.value)} />
            <div className="flex space-x-4">
              <button onClick={() => setShowImageDialog(false)} className="flex-1 py-3 text-[10px] font-black uppercase text-gray-500">Cancel</button>
              <button onClick={handleGenerateImage} className="flex-[2] bg-[#ff3e3e] text-white py-3 rounded-2xl font-black uppercase italic tracking-widest">{loadingImage ? 'Hydrating...' : 'Generate'}</button>
            </div>
            {/* Added required link to billing documentation for Imagen/Pro models */}
            <div className="mt-4 text-[10px] text-center text-gray-500 font-bold uppercase tracking-widest leading-tight px-4">
              Requires a paid project API key. See <a href="https://ai.google.dev/gemini-api/docs/billing" target="_blank" rel="noreferrer" className="text-red-500 underline">Billing Documentation</a>.
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AdminPosts;
