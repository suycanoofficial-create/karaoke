import { VercelRequest, VercelResponse } from '@vercel/node';
import { jsonSuccess, jsonError } from '../src/lib/response';

const FALLBACK_SONGS = [
  { title: 'Bohemian Rhapsody - Queen', videoId: 'fJ9rUzIMcZQ' },
  { title: 'Sweet Child O Mine - Guns N Roses', videoId: '1w7OgIMMRc4' },
  { title: 'Billie Jean - Michael Jackson', videoId: 'Zi_XLOBDo_Y' },
  { title: 'Hotel California - Eagles', videoId: 'EqPtz5qN7HM' },
  { title: 'Livin on a Prayer - Bon Jovi', videoId: 'lDK9QqIzhwk' },
  { title: 'Dont Stop Believin - Journey', videoId: '1k8craCGpgs' },
  { title: 'Wonderwall - Oasis', videoId: '6hzrDeceEKc' },
  { title: 'Hey Jude - The Beatles', videoId: 'A_MjCqQoLLA' },
  { title: 'Smells Like Teen Spirit - Nirvana', videoId: 'hTWKbfoikeg' },
  { title: 'Take Me Home Country Roads - John Denver', videoId: '1vrEljMfXo0' },
  { title: 'Yesterday - The Beatles', videoId: 'wXTJBrWtt_s' },
  { title: 'Imagine - John Lennon', videoId: 'VOgFZfRVaww' },
  { title: 'Purple Rain - Prince', videoId: 'TvnYmWpD_T8' },
  { title: 'Stairway to Heaven - Led Zeppelin', videoId: 'QkF3oxziUI4' },
  { title: 'Piano Man - Billy Joel', videoId: 'gxEPV4kolz0' },
  { title: 'My Way - Frank Sinatra', videoId: 'qQzdAsjWGPg' },
  { title: 'I Will Always Love You - Whitney Houston', videoId: '3JWTaaS7LdU' },
  { title: 'Livin La Vida Loca - Ricky Martin', videoId: 'p47fEXGabaY' },
  { title: 'Mamma Mia - ABBA', videoId: 'unfzfe8f9NI' },
  { title: 'Shape of You - Ed Sheeran', videoId: 'JGwWNGJdvx8' },
];

export default async function handler(req: VercelRequest, res: VercelResponse) {
  const query = (req.query.q as string || '').trim();

  if (!query) {
    return jsonSuccess(res, { songs: FALLBACK_SONGS });
  }

  const apiKey = process.env.YOUTUBE_API_KEY;

  if (apiKey) {
    try {
      const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(query + ' karaoke')}&type=video&maxResults=15&videoCategoryId=10&key=${apiKey}`;
      const response = await fetch(url);
      const data = await response.json();

      if (data.items) {
        const songs = data.items
          .filter((item: any) => item.id?.kind === 'youtube#video')
          .map((item: any) => ({
            title: item.snippet.title,
            videoId: item.id.videoId,
            thumbnail: item.snippet.thumbnails?.default?.url || null,
          }));
        return jsonSuccess(res, { songs });
      }
    } catch {
      // Fall through to fallback
    }
  }

  const filtered = FALLBACK_SONGS.filter(s =>
    s.title.toLowerCase().includes(query.toLowerCase())
  );

  jsonSuccess(res, {
    songs: filtered.length > 0 ? filtered : FALLBACK_SONGS,
    note: filtered.length === 0 ? 'No results found, showing suggestions' : undefined,
  });
}
