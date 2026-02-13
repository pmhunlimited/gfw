
import React, { useState, useEffect } from 'react';
import { Comment, CommentStatus } from '../types';

const AdminComments: React.FC = () => {
  const [comments, setComments] = useState<Comment[]>([]);
  const [filter, setFilter] = useState<CommentStatus | 'all'>('all');
  const [selectedComments, setSelectedComments] = useState<Set<string>>(new Set());
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editText, setEditText] = useState('');

  useEffect(() => {
    const stored = localStorage.getItem('site_comments');
    if (stored) {
      setComments(JSON.parse(stored));
    } else {
      const mockComments: Comment[] = [
        { id: '1', postId: '1', postTitle: 'Arsenal vs Liverpool: Tactical Preview', author: 'Gooner4Life', text: 'This game is going to be massive! Predicting a 2-1 win.', date: '2023-10-24', status: 'pending' },
        { id: '2', postId: '2', author: 'ChelseaFan', text: 'Buy cheap watches here at scam.com!!!', date: '2023-10-23', status: 'pending' }
      ];
      setComments(mockComments);
      localStorage.setItem('site_comments', JSON.stringify(mockComments));
    }
  }, []);

  const saveAllComments = (updated: Comment[]) => {
    setComments(updated);
    localStorage.setItem('site_comments', JSON.stringify(updated));
  };

  const updateStatus = (id: string, status: CommentStatus) => {
    const updated = comments.map(c => c.id === id ? { ...c, status } : c);
    saveAllComments(updated);
  };

  const deleteComment = (id: string) => {
    if (confirm("Delete this comment permanently?")) {
      const updated = comments.filter(c => c.id !== id);
      saveAllComments(updated);
    }
  };

  const handleBulkAction = (status: CommentStatus) => {
    if (selectedComments.size === 0) return;
    const updated = comments.map(c => selectedComments.has(c.id) ? { ...c, status } : c);
    saveAllComments(updated);
    setSelectedComments(new Set());
  };

  const toggleSelect = (id: string) => {
    const next = new Set(selectedComments);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelectedComments(next);
  };

  const toggleSelectAll = () => {
    if (selectedComments.size === filteredComments.length) {
      setSelectedComments(new Set());
    } else {
      setSelectedComments(new Set(filteredComments.map(c => c.id)));
    }
  };

  const startEditing = (comment: Comment) => {
    setEditingId(comment.id);
    setEditText(comment.text);
  };

  const saveEdit = (id: string) => {
    const updated = comments.map(c => c.id === id ? { ...c, text: editText } : c);
    saveAllComments(updated);
    setEditingId(null);
  };

  const filteredComments = comments.filter(c => filter === 'all' || c.status === filter);

  const getStatusBadge = (status: CommentStatus) => {
    switch (status) {
      case 'approved': return <span className="bg-green-500/10 text-green-400 px-2 py-0.5 rounded text-[10px] font-black uppercase">Approved</span>;
      case 'rejected': return <span className="bg-red-500/10 text-red-400 px-2 py-0.5 rounded text-[10px] font-black uppercase">Rejected</span>;
      case 'spam': return <span className="bg-orange-500/10 text-orange-400 px-2 py-0.5 rounded text-[10px] font-black uppercase">Spam</span>;
      default: return <span className="bg-yellow-500/10 text-yellow-400 px-2 py-0.5 rounded text-[10px] font-black uppercase">Pending</span>;
    }
  };

  return (
    <div className="space-y-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h1 className="text-3xl font-condensed font-black italic uppercase text-white">TERRACE MODERATION</h1>
        
        {/* Bulk Actions Bar */}
        {selectedComments.size > 0 && (
          <div className="flex items-center gap-2 bg-[#ff3e3e] px-4 py-2 rounded-xl animate-in fade-in slide-in-from-top-2">
            <span className="text-[10px] font-black uppercase text-white mr-2">{selectedComments.size} SELECTED</span>
            <button onClick={() => handleBulkAction('approved')} className="bg-white text-black px-3 py-1 rounded text-[10px] font-black uppercase hover:bg-black hover:text-white transition-all">Approve</button>
            <button onClick={() => handleBulkAction('rejected')} className="bg-white text-black px-3 py-1 rounded text-[10px] font-black uppercase hover:bg-black hover:text-white transition-all">Reject</button>
            <button onClick={() => handleBulkAction('spam')} className="bg-white text-black px-3 py-1 rounded text-[10px] font-black uppercase hover:bg-black hover:text-white transition-all">Spam</button>
          </div>
        )}
      </div>

      {/* Filter Tabs */}
      <div className="flex flex-wrap gap-2 border-b border-white/5 pb-4">
        {['all', 'pending', 'approved', 'rejected', 'spam'].map((s) => (
          <button
            key={s}
            onClick={() => {setFilter(s as any); setSelectedComments(new Set());}}
            className={`px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all ${filter === s ? 'bg-[#ff3e3e] text-white' : 'bg-white/5 text-gray-500 hover:bg-white/10'}`}
          >
            {s}
          </button>
        ))}
      </div>
      
      <div className="bg-[#0a0e17] rounded-2xl border border-white/10 overflow-hidden shadow-2xl overflow-x-auto">
        <table className="w-full text-left border-collapse min-w-[800px]">
          <thead className="bg-white/5 text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">
            <tr>
              <th className="px-8 py-4 w-12">
                <input 
                  type="checkbox" 
                  className="rounded border-gray-600 bg-transparent text-[#ff3e3e]" 
                  checked={selectedComments.size === filteredComments.length && filteredComments.length > 0}
                  onChange={toggleSelectAll}
                />
              </th>
              <th className="px-8 py-4">Author & Content</th>
              <th className="px-8 py-4">Target Post</th>
              <th className="px-8 py-4">Status</th>
              <th className="px-8 py-4 text-right">Moderation Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {filteredComments.map(comment => (
              <tr key={comment.id} className={`hover:bg-white/5 transition-colors ${selectedComments.has(comment.id) ? 'bg-[#ff3e3e]/5' : ''}`}>
                <td className="px-8 py-6">
                  <input 
                    type="checkbox" 
                    className="rounded border-gray-600 bg-transparent text-[#ff3e3e]" 
                    checked={selectedComments.has(comment.id)}
                    onChange={() => toggleSelect(comment.id)}
                  />
                </td>
                <td className="px-8 py-6">
                  <div className="flex justify-between items-start">
                    <p className="font-black text-[#ff3e3e] uppercase italic text-sm">{comment.author}</p>
                    <button onClick={() => startEditing(comment)} className="text-[9px] font-black uppercase text-gray-500 hover:text-white transition-all underline decoration-gray-500 underline-offset-2">Edit Text</button>
                  </div>
                  <p className="text-[10px] text-gray-500 mt-1 mb-2">{comment.date}</p>
                  
                  {editingId === comment.id ? (
                    <div className="mt-2 space-y-2">
                      <textarea 
                        className="w-full bg-white/5 border border-white/10 rounded-lg p-3 text-sm text-white focus:outline-none focus:border-[#ff3e3e]"
                        rows={3}
                        value={editText}
                        onChange={(e) => setEditText(e.target.value)}
                      />
                      <div className="flex gap-2">
                        <button onClick={() => saveEdit(comment.id)} className="bg-green-500 text-white px-3 py-1 rounded text-[10px] font-black uppercase">Save</button>
                        <button onClick={() => setEditingId(null)} className="bg-gray-700 text-white px-3 py-1 rounded text-[10px] font-black uppercase">Cancel</button>
                      </div>
                    </div>
                  ) : (
                    <p className="text-sm text-white font-medium line-clamp-2 max-w-md">"{comment.text}"</p>
                  )}
                </td>
                <td className="px-8 py-6 text-xs font-bold text-gray-400 uppercase tracking-tighter">
                  {comment.postTitle || `Post ID: ${comment.postId}`}
                </td>
                <td className="px-8 py-6">
                  {getStatusBadge(comment.status)}
                </td>
                <td className="px-8 py-6 text-right">
                  <div className="flex justify-end space-x-2">
                    <button 
                      onClick={() => updateStatus(comment.id, 'approved')}
                      title="Approve"
                      className={`p-2 rounded-lg transition-all ${comment.status === 'approved' ? 'bg-green-500 text-white' : 'bg-white/5 text-green-500 hover:bg-green-500 hover:text-white'}`}
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button 
                      onClick={() => updateStatus(comment.id, 'rejected')}
                      title="Reject"
                      className={`p-2 rounded-lg transition-all ${comment.status === 'rejected' ? 'bg-red-500 text-white' : 'bg-white/5 text-red-500 hover:bg-red-500 hover:text-white'}`}
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <button 
                      onClick={() => updateStatus(comment.id, 'spam')}
                      title="Mark as Spam"
                      className={`p-2 rounded-lg transition-all ${comment.status === 'spam' ? 'bg-orange-500 text-white' : 'bg-white/5 text-orange-500 hover:bg-orange-500 hover:text-white'}`}
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </button>
                    <button 
                      onClick={() => deleteComment(comment.id)}
                      title="Delete"
                      className="p-2 rounded-lg bg-white/5 text-gray-500 hover:bg-black hover:text-white transition-all"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {filteredComments.length === 0 && (
              <tr>
                <td colSpan={5} className="px-8 py-20 text-center italic text-gray-600 uppercase font-black">No comments found for this filter</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminComments;
