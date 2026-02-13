
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
      const apiKey = settings?.geminiApiKey || process.env.API_KEY || '';
      const ai = new GoogleGenAI({ apiKey });

      if (isImage) {
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
      } else {
        const response = await ai.models.generateContent({
          model: modelName as any,
          contents: prompt,
        });
        const text = response.text || "";
        saveToCache(cacheKey, text);
        return text;
      }
    } catch (error: any) {
      // Robust 429 Quota Exceeded Handling
      if (error?.message?.includes('429') || error?.message?.includes('RESOURCE_EXHAUSTED') || error?.status === 'RESOURCE_EXHAUSTED') {
        console.warn("AI Intelligence Throttled: Quota Exceeded.");
        return "### BROADCAST WARNING: QUOTA LIMIT REACHED\nThe AI tactical feed is currently throttled due to high demand or plan limits. Please verify your API Key billing status in the System Settings to restore high-frequency intelligence updates.";
      }
      console.error("AI execution failed:", error);
      throw error;
    }
  }));
}

export function renderMarkdown(text: string): string {
  if (!text) return "";
  let cleanText = text.replace(/```[a-z]*\n?/gi, '').replace(/```/g, '').trim();

  let html = cleanText
    .replace(/^# (.*$)/gm, '<h2 class="h3 font-condensed fw-black text-electric-red mt-4 mb-3 border-bottom border-danger border-opacity-25 pb-2 uppercase italic">$1</h2>')
    .replace(/^## (.*$)/gm, '<h3 class="h4 font-condensed fw-black text-white mt-4 mb-2 uppercase italic">$1</h3>')
    .replace(/^### (.*$)/gm, '<h4 class="h5 font-condensed fw-black text-white-50 mt-3 mb-2 uppercase italic">$1</h4>')
    .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white fw-bold">$1</strong>')
    .replace(/\*(.*?)\*/g, '<em class="italic text-white-50">$1</em>');

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
          } else if (!line.includes('---')) {
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
    html = html.split('\n\n').map(p => p.trim() ? `<p class="mb-4 text-white-50 leading-relaxed">${p.replace(/\n/g, '<br/>')}</p>` : '').join('');
  }
  return html;
}

export async function fetchAndRefineNews(category: string, subCategory: string, model: AIModel): Promise<Post[]> {
  const prompt = `Generate 6 professional football news articles for: ${category}. Return JSON array with keys: id, title, excerpt, content, category, author, date, image, isTopStory. Use high-quality Unsplash sports URLs.`;
  try {
    const raw = await executeAI(model, prompt);
    const jsonStr = raw.replace(/```json|```/g, '').trim();
    return JSON.parse(jsonStr);
  } catch (e) { return MOCK_POSTS; }
}

export async function fetchSportsData(type: string, competition: string, model: AIModel): Promise<string> {
  const prompt = `Provide a detailed ${type} report for ${competition}. Use Markdown tables for data.`;
  return await executeAI(model, prompt);
}

export async function getAIFootballInsight(prompt: string): Promise<string> {
  const stored = localStorage.getItem('site_settings');
  const model = stored ? JSON.parse(stored).selectedModel : 'gemini-3-flash-preview';
  return await executeAI(model, prompt);
}

export async function generateNewsArticle(title: string, category: string) {
  const stored = localStorage.getItem('site_settings');
  const model = stored ? JSON.parse(stored).selectedModel : 'gemini-3-flash-preview';
  const prompt = `Write a 500-word football article: "${title}". JSON with "content" and "excerpt".`;
  try {
    const raw = await executeAI(model, prompt);
    const jsonStr = raw.replace(/```json|```/g, '').trim();
    return JSON.parse(jsonStr);
  } catch (e) { return { content: "Intelligence gathering in progress...", excerpt: "Tactical data incoming." }; }
}

export async function generatePostImage(prompt: string): Promise<string | null> {
  return await executeAI('gemini-3-pro-image-preview', prompt, true);
}
