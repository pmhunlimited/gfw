
import React, { useState, useEffect } from 'react';
import { Subscriber } from '../types';
import { MOCK_SUBSCRIBERS } from '../constants';

const AdminSubscribers: React.FC = () => {
  const [subscribers, setSubscribers] = useState<Subscriber[]>([]);

  useEffect(() => {
    const stored = localStorage.getItem('subscribers');
    if (stored) {
      setSubscribers(JSON.parse(stored));
    } else {
      setSubscribers(MOCK_SUBSCRIBERS);
      localStorage.setItem('subscribers', JSON.stringify(MOCK_SUBSCRIBERS));
    }
  }, []);

  const removeSubscriber = (email: string) => {
    if (confirm(`Unsubscribe ${email}?`)) {
      const updated = subscribers.filter(s => s.email !== email);
      setSubscribers(updated);
      localStorage.setItem('subscribers', JSON.stringify(updated));
    }
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-condensed font-black italic uppercase text-white">Fanbase Network</h1>
        <div className="bg-[#ff3e3e] text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">{subscribers.length} ACTIVE</div>
      </div>

      <div className="bg-[#0a0e17] rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
        <table className="w-full text-left">
          <thead className="bg-white/5 text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">
            <tr>
              <th className="px-8 py-4">Email Address</th>
              <th className="px-8 py-4">Joined Date</th>
              <th className="px-8 py-4 text-right">Control</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {subscribers.map(sub => (
              <tr key={sub.email} className="hover:bg-white/5 transition-colors">
                <td className="px-8 py-6 font-bold text-white text-sm">{sub.email}</td>
                <td className="px-8 py-6 text-gray-500 text-xs font-bold uppercase">{sub.dateJoined}</td>
                <td className="px-8 py-6 text-right">
                  <button onClick={() => removeSubscriber(sub.email)} className="text-gray-500 hover:text-[#ff3e3e]"><svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminSubscribers;
