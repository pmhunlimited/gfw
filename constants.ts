
import { Post, Match, SiteSettings, Subscriber } from './types';

export const ADMIN_CREDENTIALS = {
  email: "admin@example.com",
  password: "password123"
};

export const DEFAULT_CATEGORIES = [
  'Premier League',
  'Champions League',
  'Europa League',
  'La Liga',
  'Serie A',
  'Bundesliga',
  'Transfers',
  'Chelsea',
  'Arsenal',
  'Liverpool',
  'Man City'
];

export const INITIAL_SETTINGS: SiteSettings = {
  name: "The Global Football Watch",
  tagline: "The World Watches Football Here.",
  logo: "https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&q=80&w=200&h=60",
  adminEmail: "admin@example.com",
  whatsappNumber: "+1234567890",
  selectedModel: 'gemini-3-flash-preview',
  smtp: {
    host: "smtp.example.com",
    port: "587",
    user: "editorial@example.com",
    pass: "secure_pass",
    senderEmail: "news@theglobalfootballwatch.com",
    senderName: "The Global Football Watch"
  },
  socialAutoPost: {
    twitterToken: "",
    facebookToken: "",
    instagramToken: "",
    enabled: false
  },
  socials: {
    facebook: "https://facebook.com/globalfootballwatch",
    twitter: "https://twitter.com/globalfootballwatch",
    instagram: "https://instagram.com/globalfootballwatch",
    youtube: "https://youtube.com/globalfootballwatch"
  }
};

export const MOCK_POSTS: Post[] = [
  {
    id: '1',
    title: "Arteta vs Slot: Tactical Masterclass Expected at the Emirates",
    excerpt: "The battle at the top of the table heats up as league leaders Arsenal host a resurgent Liverpool side in London.",
    content: "The Premier League title race takes center stage this weekend as Arsenal face Liverpool. Both managers have shown incredible tactical flexibility this season, with Mikel Arteta's high-press system going up against Arne Slot's direct attacking philosophy. Key battles in midfield will likely decide the outcome of this heavyweight clash.",
    category: "Premier League",
    author: "James Wilson",
    date: "2024-10-24",
    image: "https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=1200",
    isTopStory: true,
    seo: {
      metaTitle: "Arteta vs Slot Tactical Analysis | Arsenal vs Liverpool",
      metaDescription: "Detailed tactical breakdown of the upcoming clash between Arteta's Arsenal and Slot's Liverpool.",
      keywords: "Premier League, Arsenal, Liverpool, Tactical Analysis"
    }
  }
];

export const MOCK_MATCHES: Match[] = [
  { id: 'm1', homeTeam: "Arsenal", awayTeam: "Liverpool", time: "16:30", league: "Premier League", status: 'SCHEDULED' },
  { id: 'm2', homeTeam: "West Ham", awayTeam: "Man Utd", time: "14:00", league: "Premier League", homeScore: 2, awayScore: 1, status: 'FINISHED' },
  { id: 'm3', homeTeam: "Chelsea", awayTeam: "Newcastle", time: "14:00", league: "Premier League", status: 'SCHEDULED' },
  { id: 'm4', homeTeam: "Spurs", awayTeam: "Man City", time: "20:00", league: "Premier League", status: 'SCHEDULED' }
];

export const MOCK_SUBSCRIBERS: Subscriber[] = [
  { email: "fan1@gmail.com", dateJoined: "2024-09-01" },
  { email: "pitch_lover@yahoo.com", dateJoined: "2024-09-15" }
];
