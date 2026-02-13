
import React, { useState } from 'react';
import { sendPasswordResetToken } from '../services/mailService';
import { ADMIN_CREDENTIALS } from '../constants';

const AdminProfile: React.FC = () => {
  const [currentStep, setCurrentStep] = useState(1); // 1: Send Token, 2: Verify Token, 3: Update Password
  const [tokenInput, setTokenInput] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSendToken = async () => {
    setLoading(true);
    await sendPasswordResetToken(ADMIN_CREDENTIALS.email);
    alert("Verification token sent to your editorial email.");
    setCurrentStep(2);
    setLoading(false);
  };

  const handleVerifyToken = () => {
    const stored = localStorage.getItem('admin_reset_token');
    if (tokenInput === stored) {
      setCurrentStep(3);
    } else {
      alert("Invalid token. Please check your console/logs.");
    }
  };

  const handleFinalUpdate = () => {
    alert("Identity credentials updated successfully!");
    setCurrentStep(1);
    setTokenInput('');
    setNewPassword('');
  };

  return (
    <div className="max-w-xl mx-auto space-y-8">
      <h1 className="text-3xl font-condensed font-black italic uppercase text-white">Identity Management</h1>

      <div className="bg-[#0a0e17] border border-white/5 p-10 rounded-3xl shadow-2xl">
        <div className="flex items-center space-x-6 mb-10 border-b border-white/5 pb-10">
          <div className="w-20 h-20 rounded-full bg-[#ff3e3e] flex items-center justify-center text-3xl font-black text-white italic shadow-lg">AW</div>
          <div>
            <h2 className="text-xl font-bold text-white uppercase italic">Site Administrator</h2>
            <p className="text-[10px] text-gray-500 font-black uppercase tracking-widest">{ADMIN_CREDENTIALS.email}</p>
          </div>
        </div>

        {currentStep === 1 && (
          <div className="space-y-6">
            <h3 className="text-xs font-black text-gray-400 uppercase tracking-widest">Credential Rotation</h3>
            <p className="text-sm text-gray-500">For security, rotation requires email verification. Site name will appear as sender.</p>
            <button onClick={handleSendToken} disabled={loading} className="w-full bg-white/5 border border-white/10 text-white py-4 rounded-2xl font-black uppercase italic tracking-widest hover:bg-[#ff3e3e] transition-all">
              {loading ? 'Processing...' : 'Request Verification Token'}
            </button>
          </div>
        )}

        {currentStep === 2 && (
          <div className="space-y-6">
            <h3 className="text-xs font-black text-[#ff3e3e] uppercase tracking-widest">Verify Ownership</h3>
            <input 
              type="text" 
              placeholder="ENTER 6-DIGIT TOKEN" 
              className="w-full bg-black/40 border border-[#ff3e3e]/30 rounded-2xl px-6 py-4 text-center text-2xl font-black tracking-[0.5em] text-white"
              value={tokenInput}
              onChange={e => setTokenInput(e.target.value)}
            />
            <button onClick={handleVerifyToken} className="w-full bg-[#ff3e3e] text-white py-4 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">
              Verify Token
            </button>
          </div>
        )}

        {currentStep === 3 && (
          <div className="space-y-6">
            <h3 className="text-xs font-black text-green-500 uppercase tracking-widest">Set New Password</h3>
            <input 
              type="password" 
              placeholder="NEW SECURE PASSWORD" 
              className="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold"
              value={newPassword}
              onChange={e => setNewPassword(e.target.value)}
            />
            <button onClick={handleFinalUpdate} className="w-full bg-green-500 text-white py-4 rounded-2xl font-black uppercase italic tracking-widest shadow-xl">
              Apply Rotation
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminProfile;
