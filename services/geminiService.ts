
import { GoogleGenAI } from "@google/genai";
import { Post, AIModel, SiteSettings } from "../types";
import { MOCK_POSTS } from "../constants";

// --- Configuration & Constants ---
const CACHE_PREFIX = 'gfw_cache_';
const CACHE_EXPIRY = 20 * 60 * 1000;
const REQUEST_GAP = 1500;
let lastRequestTime = 0;
let requestQueue: Promise<any> = Promise.resolve();

async function throttle() {
  const now = Date.now();
  const timeSinceLast = now - lastRequestTime;
  if (timeSinceLast < REQUEST_GAP) {
    const waitTime = REQUEST_GAP - timeSinceLast;
    await new Promise(resolve => setTimeout(resolve, waitTime));
  }
  lastRequestTime = Date.now();
}

function getFromCache(key: string): any | null {
  const cached = localStorage.getItem(CACHE_PREFIX + key);
  if (!cached) return null;
  try {
    const { data, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp < CACHE_EXPIRY) return data;
  } catch (e) { return null; }
  return null;
}

function saveToCache(key: string, data: any) {
  try {
    localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({ data, timestamp: Date.now() }));
  } catch (e) {}
}

function createCacheKey(params: any): string {
  return btoa(unescape(encodeURIComponent(JSON.stringify(params)))).substring(0, 32);
}

async function callDeepSeek(modelName: string, prompt: string): Promise<string> {
  const storedSettings = localStorage.getItem('site_settings');
  const settings: SiteSettings | null = storedSettings ? JSON.parse(storedSettings) : null;
  const apiKey = settings?.deepseekApiKey || process.env.DEEPSEEK_API_KEY;

  if (!apiKey) throw new Error("DeepSeek API Key missing.");

  const response = await fetch('https://api.deepseek.com/chat/completions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${apiKey}`
    },
    body: JSON.stringify({
      model: modelName,
      messages: [{ role: 'user', content: prompt }],
      temperature: 0.7
    })
  });

  if (!response.ok) {
    if (response.status === 429) throw new Error("DeepSeek Quota Exceeded (429). Check your billing.");
    throw new Error(`DeepSeek API Error: ${response.status}`);
  }
  const data = await response.json();
  return data.choices[0].message.content;
}

async function executeAI(modelName: AIModel, prompt: string, isImage = false): Promise<any> {
  const cacheKey = createCacheKey({ modelName, prompt, isImage });
  const cachedResult = getFromCache(cacheKey);
  if (cachedResult) return cachedResult;

  const storedSettings = localStorage.getItem('site_settings');
  const settings: SiteSettings | null = storedSettings ? JSON.parse(storedSettings) : null;

  return await (requestQueue = requestQueue.then(async () => {
    const recheck = getFromCache(cacheKey);
    if (recheck) return recheck;

    await throttle();

    try {
      if (isImage) {
        const ai = new GoogleGenAI({ apiKey: settings?.geminiApiKey || process.env.API_KEY || '' });
        const response = await ai.models.generateContent({
          model: 'gemini-2.5-flash-image',
          contents: { parts: [{ text: prompt }] },
        });
        
        for (const candidate of response.candidates || []) {
          for (const part of candidate.content.parts) {
            if (part.inlineData) {
              const url = `data:${part.inlineData.mimeType};base64,${part.inlineData.data}`;
              saveToCache(cacheKey, url);
              return url;
            }
          }
        }
        throw new Error("No image data returned");
      } else if (modelName.startsWith('deepseek')) {
        const text = await callDeepSeek(modelName, prompt);
        saveToCache(cacheKey, text);
        return text;
      } else {
        const ai = new GoogleGenAI({ apiKey: settings?.geminiApiKey || process.env.API_KEY || '' });
        const response = await ai.models.generateContent({
          model: modelName as any,
          contents: prompt,
        });
        const text = response.text || "";
        saveToCache(cacheKey, text);
        return text;
      }
    } catch (error: any) {
      if (error?.message?.includes('429') || error?.message?.includes('RESOURCE_EXHAUSTED')) {
        console.warn("AI Quota Exceeded. Please check your API billing or key limits.");
        return "### QUOTA EXCEEDED (429)\nIntelligence stream throttled by provider. Please update your API Key or check billing in Systems tab.";
      }
      console.error("AI execution failed:", error);
      throw error;
    }
  }));
}

/**
 * Enhanced Markdown to HTML converter for professional sports journalism
 */
export function renderMarkdown(text: string): string {
  if (!text) return "";
  
  // Clean up code blocks and technical wrappers (common in AI outputs)
  let cleanText = text
    .replace(/```json\n?|```markdown\n?|```[a-z]*\n?/gi, '')
    .replace(/```/g, '')
    .trim();

  // Convert headers with high contrast styling
  let html = cleanText
    .replace(/^# (.*$)/gm, '<h2 class="h3 font-condensed fw-black text-electric-red mt-4 mb-3 border-bottom border-danger border-opacity-25 pb-2 uppercase italic">$1</h2>')
    .replace(/^## (.*$)/gm, '<h3 class="h4 font-condensed fw-black text-white mt-4 mb-2 uppercase italic">$1</h3>')
    .replace(/^### (.*$)/gm, '<h4 class="h5 font-condensed fw-black text-white-50 mt-3 mb-2 uppercase italic">$1</h4>')
    .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white fw-bold">$1</strong>')
    .replace(/\*(.*?)\*/g, '<em class="italic text-white-50">$1</em>');

  // Advanced Table handling with Bootstrap classes for better visual display
  if (html.includes('|')) {
    const lines = html.split('\n');
    let tableHtml = '';
    let inTable = false;

    lines.forEach((line) => {
      const isTableRow = line.trim().startsWith('|') && line.trim().endsWith('|');
      
      if (isTableRow) {
        const cells = line.split('|').map(c => c.trim()).filter((c, i, arr) => i > 0 && i < arr.length - 1);
        if (cells.length > 0) {
          if (!inTable) {
            tableHtml += '<div class="table-responsive my-4 shadow-sm border border-white border-opacity-10 rounded-3"><table class="table table-dark table-hover mb-0 align-middle" style="font-size: 0.85rem">';
            tableHtml += '<thead class="bg-black"><tr>';
            cells.forEach(c => tableHtml += `<th class="text-electric-red font-black uppercase py-3 border-danger border-opacity-25">${c}</th>`);
            tableHtml += '</tr></thead><tbody>';
            inTable = true;
          } else if (line.includes('---')) {
            // Divider row, skip
          } else {
            tableHtml += '<tr>';
            cells.forEach(c => tableHtml += `<td class="text-white-50 py-3 border-white border-opacity-5">${c}</td>`);
            tableHtml += '</tr>';
          }
        }
      } else if (inTable) {
        tableHtml += '</tbody></table></div>';
        inTable = false;
        if (line.trim()) tableHtml += `<p class="my-3 text-white-50">${line}</p>`;
      } else {
        if (line.trim()) tableHtml += `<p class="my-3 text-white-50">${line}</p>`;
      }
    });
    
    if (inTable) tableHtml += '</tbody></table></div>';
    html = tableHtml;
  } else {
    // Basic paragraph wrapping
    html = html.split('\n\n').map(p => p.trim() ? `<p class="mb-4 text-white-50 leading-relaxed">${p.replace(/\n/g, '<br/>')}</p>` : '').join('');
  }

  return html;
}

export async function fetchAndRefineNews(category: string, subCategory: string, model: AIModel): Promise<Post[]> {
  const prompt = `Generate 6 professional football news articles for: ${category} / ${subCategory}. Return RAW JSON array with keys: id, title, excerpt, content, category, author, date, image, isTopStory, tags. Use realistic data. Ensure image is a high-quality Unsplash sports URL.`;
  try {
    const raw = await executeAI(model, prompt);
    const jsonStr = raw.replace(/```json|```/g, '').trim();
    return JSON.parse(jsonStr);
  } catch (e) {
    return MOCK_POSTS;
  }
}

export async function fetchSportsData(type: string, competition: string, model: AIModel): Promise<string> {
  const prompt = `Provide a detailed ${type} report for ${competition}. Include rankings, tactical insights, and key performance indicators. Use Markdown tables for data. Do not include markdown code block backticks.`;
  try {
    return await executeAI(model, prompt);
  } catch (e) {
    return "### DATA STREAM INTERRUPTED\nPlease verify API Credentials in Admin Systems.";
  }
}

export async function getAIFootballInsight(prompt: string): Promise<string> {
  try {
    const stored = localStorage.getItem('site_settings');
    const model = stored ? JSON.parse(stored).selectedModel : 'gemini-3-flash-preview';
    return await executeAI(model, prompt);
  } catch (e) {
    return "Analysis calibration required.";
  }
}

export async function generateNewsArticle(title: string, category: string) {
  const prompt = `Write a comprehensive 500-word football news article: "${title}". Category: ${category}. Format: JSON with "content" and "excerpt".`;
  try {
    const stored = localStorage.getItem('site_settings');
    const model = stored ? JSON.parse(stored).selectedModel : 'gemini-3-flash-preview';
    const raw = await executeAI(model, prompt);
    const jsonStr = raw.replace(/```json|```/g, '').trim();
    return JSON.parse(jsonStr);
  } catch (e) {
    return { content: "Drafting in progress.", excerpt: "Updates to follow." };
  }
}

export async function generatePostImage(prompt: string): Promise<string | null> {
  try {
    return await executeAI('gemini-3-pro-image-preview', prompt, true);
  } catch (e) {
    return null;
  }
}
