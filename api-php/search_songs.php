<?php
/**
 * API: Search Songs (YouTube)
 * GET /api/search_songs.php?q=query
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    json_error('Query must be at least 2 characters');
}

$api_key = YOUTUBE_API_KEY;

function http_get($url, $timeout = 5, $depth = 0) {
    if ($depth > 3) return null;

    // Try cURL first
    if (function_exists('curl_init')) {
        try {
            $ch = curl_init($url);
            if ($ch) {
                $curl_opts = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9'],
                ];
                if (!ini_get('open_basedir')) {
                    $curl_opts[CURLOPT_FOLLOWLOCATION] = true;
                }
                curl_setopt_array($ch, $curl_opts);
                $result = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($http_code === 200 && $result) {
                    curl_close($ch);
                    return $result;
                }

                // Manual redirect follow if FOLLOWLOCATION unavailable
                if ($http_code >= 300 && $http_code < 400) {
                    $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                    curl_close($ch);
                    if ($redirect_url) {
                        return http_get($redirect_url, $timeout, $depth + 1);
                    }
                }

                curl_close($ch);
            }
        } catch (Exception $e) {}
    }

    // Fallback: file_get_contents with stream context
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'timeout' => $timeout,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'header' => 'Accept-Language: en-US,en;q=0.9',
        ]]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result !== false) return $result;
    }

    return null;
}

function youtube_api_search($q, $max = 12) {
    $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
        'part' => 'snippet',
        'q' => $q,
        'type' => 'video',
        'videoEmbeddable' => 'true',
        'maxResults' => $max,
        'key' => YOUTUBE_API_KEY
    ]);

    $response = http_get($url, 5);
    if (!$response) return [];

    $data = json_decode($response, true);
    $results = [];

    if (!empty($data['items'])) {
        foreach ($data['items'] as $item) {
            $results[] = [
                'id' => $item['id']['videoId'],
                'title' => $item['snippet']['title'],
                'channel' => $item['snippet']['channelTitle'],
                'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url']
            ];
        }
    }

    return $results;
}

function merge_prioritized($priority, $fallback, $limit = 12) {
    $seen = [];
    $merged = [];

    foreach ($priority as $r) {
        if (!isset($seen[$r['id']])) {
            $seen[$r['id']] = true;
            $merged[] = $r;
        }
    }

    foreach ($fallback as $r) {
        if (count($merged) >= $limit) break;
        if (!isset($seen[$r['id']])) {
            $seen[$r['id']] = true;
            $merged[] = $r;
        }
    }

    return $merged;
}

$debug = [
    'curl' => function_exists('curl_init'),
    'key_set' => !empty($api_key),
    'allow_url_fopen' => ini_get('allow_url_fopen'),
    'open_basedir' => ini_get('open_basedir'),
];

if (!function_exists('curl_init')) {
    $results = get_hardcoded_fallback($query);
    json_success(['results' => $results, 'source' => 'fallback', 'debug' => $debug]);
}

if ($api_key) {
    $priority = youtube_api_search($query . ' karaoke instrumental', 6);
    $general = youtube_api_search($query, 12);
    $results = merge_prioritized($priority, $general);
    if (empty($results)) $results = get_hardcoded_fallback($query);
    json_success(['results' => $results, 'source' => 'youtube', 'debug' => $debug]);
} else {
    $priority = search_youtube_scrape($query . ' karaoke instrumental', 6);
    $general = search_youtube_scrape($query, 12);
    $results = merge_prioritized($priority, $general);
    if (empty($results)) $results = get_hardcoded_fallback($query);
    json_success(['results' => $results, 'source' => 'scrape', 'debug' => $debug]);
}

function search_youtube_scrape($q, $max = 12) {
    $url = 'https://www.youtube.com/results?search_query=' . urlencode($q);

    $response = http_get($url, 8);
    if (!$response) return [];

    $results = [];

    if (preg_match('/var ytInitialData = ({.*?});\s*<\/script>/s', $response, $m)) {
        $data = json_decode($m[1], true);
        $contents = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'] ?? [];

        foreach ($contents as $item) {
            $video = $item['videoRenderer'] ?? null;
            if (!$video) continue;

            $id = $video['videoId'] ?? null;
            $title = $video['title']['runs'][0]['text'] ?? null;
            $channel = $video['ownerText']['runs'][0]['text'] ?? '';
            $thumb = '';

            if (!empty($video['thumbnail']['thumbnails'])) {
                $thumbs = $video['thumbnail']['thumbnails'];
                $thumb = end($thumbs)['url'] ?? '';
            }

            if ($id && $title) {
                if (strpos($thumb, '//') === 0) $thumb = 'https:' . $thumb;
                $results[] = [
                    'id' => $id,
                    'title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                    'channel' => html_entity_decode($channel, ENT_QUOTES, 'UTF-8'),
                    'thumbnail' => $thumb
                ];
            }

            if (count($results) >= $max) break;
        }
    }

    return $results;
}

function get_hardcoded_fallback($query) {
    $catalog = [
        ['id' => 'fJ9rUzIMcZQ', 'title' => 'Bohemian Rhapsody - Queen', 'channel' => 'Queen Official', 'thumbnail' => 'https://img.youtube.com/vi/fJ9rUzIMcZQ/mqdefault.jpg'],
        ['id' => 'kJQP7kiw5Fk', 'title' => 'Despacito - Luis Fonsi ft. Daddy Yankee', 'channel' => 'Luis Fonsi', 'thumbnail' => 'https://img.youtube.com/vi/kJQP7kiw5Fk/mqdefault.jpg'],
        ['id' => 'JGwWNGJdvx8', 'title' => 'Shape of You - Ed Sheeran', 'channel' => 'Ed Sheeran', 'thumbnail' => 'https://img.youtube.com/vi/JGwWNGJdvx8/mqdefault.jpg'],
        ['id' => 'RgKAFK5djSk', 'title' => 'See You Again - Wiz Khalifa', 'channel' => 'Wiz Khalifa', 'thumbnail' => 'https://img.youtube.com/vi/RgKAFK5djSk/mqdefault.jpg'],
        ['id' => 'lp-EO5I60KA', 'title' => 'Faded - Alan Walker', 'channel' => 'Alan Walker', 'thumbnail' => 'https://img.youtube.com/vi/lp-EO5I60KA/mqdefault.jpg'],
        ['id' => '60ItHLz5WEA', 'title' => 'Alone - Alan Walker', 'channel' => 'Alan Walker', 'thumbnail' => 'https://img.youtube.com/vi/60ItHLz5WEA/mqdefault.jpg'],
        ['id' => 'oRdxUFDoQe0', 'title' => 'Billie Jean - Michael Jackson', 'channel' => 'Michael Jackson', 'thumbnail' => 'https://img.youtube.com/vi/oRdxUFDoQe0/mqdefault.jpg'],
        ['id' => 'CevxZvSJLk8', 'title' => 'Roar - Katy Perry', 'channel' => 'Katy Perry', 'thumbnail' => 'https://img.youtube.com/vi/CevxZvSJLk8/mqdefault.jpg'],
        ['id' => 'YQHsXMglC9A', 'title' => 'Hello - Adele', 'channel' => 'Adele', 'thumbnail' => 'https://img.youtube.com/vi/YQHsXMglC9A/mqdefault.jpg'],
        ['id' => 'hT_nvWreIhg', 'title' => 'Waka Waka - Shakira', 'channel' => 'Shakira', 'thumbnail' => 'https://img.youtube.com/vi/hT_nvWreIhg/mqdefault.jpg'],
        ['id' => 'v2AC41dglnM', 'title' => 'Lean On - Major Lazer', 'channel' => 'Major Lazer', 'thumbnail' => 'https://img.youtube.com/vi/v2AC41dglnM/mqdefault.jpg'],
        ['id' => 'pRpeEdMmmQ0', 'title' => 'Uptown Funk - Bruno Mars', 'channel' => 'Bruno Mars', 'thumbnail' => 'https://img.youtube.com/vi/pRpeEdMmmQ0/mqdefault.jpg'],
    ];

    $query_lower = strtolower($query);
    $filtered = array_filter($catalog, function($item) use ($query_lower) {
        return stripos($item['title'], $query_lower) !== false ||
               stripos($item['channel'], $query_lower) !== false;
    });

    if (empty($filtered)) {
        $filtered = array_slice($catalog, 0, 6);
    }

    return array_values($filtered);
}
