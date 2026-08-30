export interface InstagramPost {
  id: string;
  caption: string;
  mediaType: 'IMAGE' | 'VIDEO' | 'CAROUSEL_ALBUM';
  mediaUrl: string;
  thumbnailUrl?: string;
  permalink: string;
  timestamp: string;
}

interface InstagramResponse {
  posts: InstagramPost[];
  username: string;
  profileUrl: string;
  source: 'api' | 'fallback';
}

interface CachedInstagram {
  data: InstagramResponse;
  timestamp: number;
}

const CACHE_TTL = 60 * 60 * 1000; // 1 hora
const DEFAULT_USERNAME = 'andradeconsultoriae';
const DEFAULT_PROFILE_URL = 'https://www.instagram.com/andradeconsultoriae';

let cache: CachedInstagram | null = null;

function buildFallback(username: string, profileUrl: string): InstagramResponse {
  return {
    posts: [],
    username,
    profileUrl,
    source: 'fallback',
  };
}

export async function getInstagramFeed(limit = 6): Promise<InstagramResponse> {
  const username = process.env.INSTAGRAM_USERNAME || DEFAULT_USERNAME;
  const profileUrl = process.env.INSTAGRAM_PROFILE_URL || DEFAULT_PROFILE_URL;

  if (cache && Date.now() - cache.timestamp < CACHE_TTL) {
    return cache.data;
  }

  const token = process.env.INSTAGRAM_ACCESS_TOKEN;
  if (!token) {
    const data = buildFallback(username, profileUrl);
    cache = { data, timestamp: Date.now() };
    return data;
  }

  try {
    const fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp';
    const url = `https://graph.instagram.com/me/media?fields=${fields}&limit=${limit}&access_token=${token}`;

    const res = await fetch(url);
    if (!res.ok) {
      throw new Error(`Instagram API ${res.status}`);
    }

    const json = (await res.json()) as {
      data?: Array<{
        id: string;
        caption?: string;
        media_type: string;
        media_url?: string;
        thumbnail_url?: string;
        permalink: string;
        timestamp: string;
      }>;
    };

    const posts: InstagramPost[] = (json.data ?? [])
      .filter((item) => item.media_url || item.thumbnail_url)
      .map((item) => ({
        id: item.id,
        caption: item.caption ?? '',
        mediaType: item.media_type as InstagramPost['mediaType'],
        mediaUrl: item.media_url || item.thumbnail_url || '',
        thumbnailUrl: item.thumbnail_url,
        permalink: item.permalink,
        timestamp: item.timestamp,
      }));

    const data: InstagramResponse = {
      posts,
      username,
      profileUrl,
      source: posts.length > 0 ? 'api' : 'fallback',
    };

    cache = { data, timestamp: Date.now() };
    return data;
  } catch (err) {
    console.error('instagram feed error', err);
    const data = buildFallback(username, profileUrl);
    cache = { data, timestamp: Date.now() };
    return data;
  }
}
