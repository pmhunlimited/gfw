
const BASE_URL = 'https://api.football-data.org/v4';
// IMPORTANT: Replace this with your actual API key from football-data.org
const API_KEY = 'YOUR_FOOTBALL_DATA_API_KEY'; 

async function fetchFromAPI(endpoint: string) {
  try {
    const response = await fetch(`${BASE_URL}${endpoint}`, {
      headers: { 'X-Auth-Token': API_KEY }
    });
    if (!response.ok) {
      if (response.status === 429) console.warn("Football API: Rate limit reached.");
      return null;
    }
    return await response.json();
  } catch (error) {
    console.warn("Football API Error:", error);
    return null;
  }
}

export async function getStandings(competitionId = 'PL') {
  return fetchFromAPI(`/competitions/${competitionId}/standings`);
}

export async function getMatches(competitionId = 'PL') {
  // Fetching both finished and upcoming/live matches to show scores
  return fetchFromAPI(`/competitions/${competitionId}/matches?status=LIVE,IN_PLAY,FINISHED,SCHEDULED&limit=20`);
}

export async function getScorers(competitionId = 'PL') {
  return fetchFromAPI(`/competitions/${competitionId}/scorers`);
}
