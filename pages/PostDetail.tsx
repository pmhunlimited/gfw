
import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Post, Comment } from '../types';
import { MOCK_POSTS } from '../constants';
import { getAIFootballInsight } from '../services/geminiService';

const PostDetail: React.FC = () => {
  const { id } = useParams();
  const [post, setPost] = useState<Post | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [aiInsight, setAiInsight] = useState<string>('Generating tactical breakdown...');
  const [subEmail, setSubEmail] = useState('');
  const [newComment, setNewComment] = useState({ author: '', text: '' });
  const [captchaValue, setCaptchaValue] = useState('');
  const [captchaInput, setCaptchaInput] = useState('');

  useEffect(() => {
    const stored = localStorage.getItem('site_posts');
    const posts = stored ? JSON.parse(stored) : MOCK_POSTS;
    const found = posts.find((p: Post) => p.id === id);
    if (found) {
      setPost(found);
      generateCaptcha();
      fetchInsight(found.title);
    }
    
    const storedComments = localStorage.getItem('site_comments');
    if (storedComments) {
      const allComments: Comment[] = JSON.parse(storedComments);
      setComments(allComments.filter(c => c.postId === id && c.status === 'approved'));
    }
    window.scrollTo(0, 0);
  }, [id]);

  const generateCaptcha = () => {
    const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    let result = '';
    for (let i = 0; i < 6; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
    setCaptchaValue(result);
  };

  const fetchInsight = async (title: string) => {
    const insight = await getAIFootballInsight(`Provide an elite tactical analysis of this football news: "${title}". Be professional and concise.`);
    setAiInsight(insight);
  };

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    alert("Subscription confirmed!");
    setSubEmail('');
  };

  const handleSubmitComment = (e: React.FormEvent) => {
    e.preventDefault();
    if (captchaInput.toUpperCase() !== captchaValue.toUpperCase()) {
      alert("Verification failed.");
      return;
    }
    alert("Comment submitted for moderation.");
    setNewComment({ author: '', text: '' });
    setCaptchaInput('');
    generateCaptcha();
  };

  if (!post) return <div className="text-center py-5 h1 font-condensed">LOADING BROADCAST...</div>;

  return (
    <div className="container-fluid p-0 overflow-hidden">
      {/* HERO SECTION */}
      <section className="position-relative" style={{ height: '60vh', minHeight: '500px' }}>
        <img src={post.image} className="w-100 h-100 object-fit-cover brightness-50" alt="" style={{objectPosition: 'center 30%'}} />
        <div className="position-absolute bottom-0 start-0 w-100 p-4 p-md-5 bg-gradient-to-t from-black to-transparent">
          <div className="container-fluid">
            <div className="d-flex align-items-center gap-3 mb-3">
              <span className="badge bg-electric-red rounded-0 fw-black italic text-uppercase px-3 py-2">{post.category}</span>
              <span className="text-white-50 fw-bold text-uppercase" style={{ fontSize: '12px' }}>{post.date}</span>
            </div>
            <h1 className="display-2 font-condensed fw-black text-white italic text-uppercase lh-1 mb-4">{post.title}</h1>
            <div className="border-start border-danger border-4 ps-4">
              <p className="text-white-50 fw-bold text-uppercase mb-0">REPORTED BY <span className="text-white">{post.author}</span></p>
            </div>
          </div>
        </div>
      </section>

      {/* ARTICLE CONTENT */}
      <div className="container-fluid py-5 px-md-5">
        <div className="row justify-content-center">
          <div className="col-lg-8">
            <article className="mb-5">
              <p className="lead fw-bold italic text-white mb-5 pb-4 border-bottom border-white border-opacity-10 fs-3">
                "{post.excerpt}"
              </p>
              <div className="text-white-50 lh-lg fs-5" style={{ whiteSpace: 'pre-wrap' }}>
                {post.content}
              </div>
            </article>

            {/* AI PULSE */}
            <div className="card mb-5 border-start border-danger border-5 shadow-lg bg-dark">
              <div className="card-body p-4 p-md-5">
                <div className="d-flex align-items-center gap-2 mb-3">
                  <div className="bg-success rounded-circle animate-pulse" style={{ width: '8px', height: '8px' }}></div>
                  <h3 className="h6 text-electric-red fw-black text-uppercase ls-widest mb-0">AI TACTICAL PULSE</h3>
                </div>
                <p className="h4 font-condensed italic text-white mb-0">{aiInsight}</p>
              </div>
            </div>
          </div>
          
          <div className="col-lg-4">
             {/* SIDEBAR CONTENT */}
             <div className="sticky-top" style={{top: '120px'}}>
                <section className="bg-[#0a0e17] p-5 rounded-4 border border-white border-opacity-5 shadow-2xl mb-5">
                  <h3 className="h4 font-condensed fw-black italic text-white text-uppercase mb-4">SUBSCRIBE TO FEED</h3>
                  <form onSubmit={handleSubscribe} className="space-y-4">
                      <input type="email" required placeholder="EMAIL@DOMAIN.COM" className="form-control bg-black border-white border-opacity-10 text-white rounded-0 px-4 py-3 fw-bold" value={subEmail} onChange={e => setSubEmail(e.target.value)} />
                      <button className="btn btn-danger w-100 rounded-0 py-3 fw-black italic uppercase">JOIN ELITE HUB</button>
                  </form>
                </section>
             </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PostDetail;
