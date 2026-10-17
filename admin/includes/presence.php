<?php
/**
 * Lightweight "who else is here" tracking for the admin area.
 *
 * Kept in small JSON files under storage/presence (no DB migration needed):
 * - one file per admin page (keyed by path + query) holding who has it open
 *   and who last saved a form on it;
 * - _online.json holding every admin seen anywhere in the back office.
 * Entries expire on their own once the browser stops sending heartbeats.
 */

const PRESENCE_TTL = 45;          // seconds without a heartbeat before someone counts as gone
const PRESENCE_SAVE_MEMORY = 86400; // how long a "last saved by" record is kept

function presence_dir() {
    $dir = SITE_ROOT . '/storage/presence';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

/** Runs $mutate($data) on a JSON file under an exclusive lock; returns the new data. */
function presence_with_file($filename, callable $mutate) {
    $path = presence_dir() . '/' . $filename;
    $fh = @fopen($path, 'c+');
    if (!$fh) {
        return $mutate([]);
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = $raw ? (json_decode($raw, true) ?: []) : [];
    $data = $mutate($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $data;
}

/**
 * Records one event from $user on admin page $pageKey and returns what that
 * page should show: other people currently on it, the last save, and who is
 * online anywhere in the admin.
 *
 * $event: 'ping' (heartbeat), 'save' (a form on the page was submitted),
 *         'leave' (tab closed / navigated away), 'check' (read only).
 */
function presence_update($pageKey, $user, $event) {
    $now = time();
    $pageFile = 'p_' . sha1($pageKey) . '.json';

    $page = presence_with_file($pageFile, function ($data) use ($user, $event, $now) {
        $viewers = $data['viewers'] ?? [];
        foreach ($viewers as $name => $ts) {
            if ($ts < $now - PRESENCE_TTL) {
                unset($viewers[$name]);
            }
        }
        if ($event === 'leave') {
            unset($viewers[$user]);
        } elseif ($event !== 'check') {
            $viewers[$user] = $now;
        }
        $saved = $data['saved'] ?? null;
        if ($event === 'save') {
            $saved = ['by' => $user, 'at' => $now];
        } elseif ($saved && $saved['at'] < $now - PRESENCE_SAVE_MEMORY) {
            $saved = null;
        }
        return ['viewers' => $viewers, 'saved' => $saved];
    });

    $online = presence_with_file('_online.json', function ($data) use ($user, $event, $now) {
        foreach ($data as $name => $ts) {
            if ($ts < $now - PRESENCE_TTL) {
                unset($data[$name]);
            }
        }
        if ($event !== 'check') {
            $data[$user] = $now;
        }
        return $data;
    });

    $others = array_values(array_filter(array_keys($page['viewers']), function ($name) use ($user) {
        return $name !== $user;
    }));
    $onlineNames = array_keys($online);
    sort($onlineNames);

    return [
        'now' => $now,
        'editors' => $others,
        'saved' => $page['saved'],
        'online' => $onlineNames,
    ];
}
