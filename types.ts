
export type AIModel = 
  | 'gemini-3-flash-preview' 
  | 'gemini-3-pro-preview' 
  | 'gemini-flash-latest'
  | 'gemini-2.5-flash-lite-latest' 
  | 'gemini-2.5-flash-image'
  | 'gemini-3-pro-image-preview'
  | 'deepseek-chat' 
  | 'deepseek-reasoner';

export interface Page {
  id: string;
  title: string;
  slug: string;
  content: string;
  isVisible: boolean;
}

export interface SEOMetadata {
  metaTitle: string;
  metaDescription: string;
  keywords: string;
}

export interface Post {
  id: string;
  title: string;
  excerpt: string;
  content: string;
  category: string;
  author: string;
  date: string;
  image: string;
  videoUrl?: string;
  isTopStory?: boolean;
  isScheduled?: boolean;
  publishDate?: string;
  socialChannels?: {
    twitter: boolean;
    facebook: boolean;
    instagram: boolean;
  };
  seo?: SEOMetadata;
  tags?: string[];
}

export interface Match {
  id: string;
  homeTeam: string;
  awayTeam: string;
  time: string;
  league: string;
  homeScore?: number;
  awayScore?: number;
  status: 'SCHEDULED' | 'LIVE' | 'FINISHED' | 'POSTPONED';
}

export interface SiteSettings {
  name: string;
  tagline: string;
  logo: string;
  adminEmail: string;
  whatsappNumber: string;
  selectedModel: AIModel;
  geminiApiKey?: string;
  deepseekApiKey?: string;
  smtp: {
    host: string;
    port: string;
    user: string;
    pass: string;
    senderEmail: string;
    senderName: string;
  };
  socialAutoPost: {
    twitterToken: string;
    facebookToken: string;
    instagramToken: string;
    enabled: boolean;
  };
  socials: {
    facebook: string;
    twitter: string;
    instagram: string;
    youtube: string;
  };
}

export interface Subscriber {
  email: string;
  dateJoined: string;
}

export interface UserPreferences {
  leagues: string[];
  teams: string[];
}

export type CommentStatus = 'pending' | 'approved' | 'rejected' | 'spam';

export interface Comment {
  id: string;
  postId: string;
  postTitle?: string;
  author: string;
  text: string;
  date: string;
  status: CommentStatus;
}
