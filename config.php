<?php
declare(strict_types=1);

/* ============================================================
   الإعدادات الحساسة — تبقى في السيرفر فقط
   ============================================================ */

// توكن GitHub الشخصي (Fine-grained token بصلاحية gist فقط)
// لا تضعه أبداً في HTML/JS
define('GITHUB_TOKEN', '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');

// معرف الـ Gist (الجزء بعد اسم المستخدم في الرابط)
define('GIST_ID', '758027f903ab4106e76ca4a6d6b1a773');

// اسم الملف داخل الـ Gist
define('GIST_FILENAME', 'JK × YS.json');

// الرابط السري للاشتراك (happ://)
define('SECRET_SUB_URL', 'happ://crypt5/fzvdAvajYAMtMDqSOHkd24VX13BXPD6zUnBtEtUcByXmbddQf2JQ1gtsjU/pqyWQnXF9yJpG8+X4PMINNa1AuSrmLnlsYmZ4EwG/eMGGrO3NRqgsJvnwPXaUY4k+pHMzDJvRI12zhipp9fEZKWAg4O7R6SW3oM3JPAQPBm==k1lSCyd7fG0/iX2xeHQ3yp70eRXyzF4x6JETiYStWON9jr0S1yCOVaCL8LaLifL0kg3LWJ+xRidhEH7t67xivJJs60PQHaq/YaFZii0b/xPNYUJc+TOxIjqEsCp5L/b9BvdEinoJm8mtIcFgbMxynjhczHJJKjWcg0HKL5iPUY+ircEtpILYaOI/0aP2xXbVV0izFkGgBS0ZBKU2cn+yAuQ32vKF3I21r6fMqBPzahJSlAlXFN16VVHIOPbMXMPwIY2NergJyspZgID/+DkLksYzzBPPKUWOiXz+2s4F7oJtFKml1VAg4rXD0aLnXrZjyTBn099cXw5t1j47F7fL3AJRt2k5F5RHXWxt6BglIMGyG70ELtn38e1ZuVGsTxQj4Boo0nZLd/JFCbcHVTZfDoRcpUEljwoJPx6LVSDL8SEoSD0nwqM9Wcc5iIyk8dvXIV7hMIm+Kd5KHuHgmZR5rN2CcblZMiNz37jyiVOwz1jicdB1PL+dflUEDyF1P+qXPSYL0r/IuyrpluVpciwEQg+ZZxDdEsdz6rrtSYcbcUZY9ALpiv/EyXuc4ryttmUxuOEiA2BI68Coy1ZRC06H2hMeXczp5cfXYezw/6ht3A+bZIydYDsVnzSq7KSO/X/TjXsXNGlzIr7tA13WHQacn458XjZgCN/Csb1fC7NCfok=ff');

// رابط التلغرام (يظهر في الواجهة)
define('TELEGRAM_URL', 'https://t.me/DXVZ8');

// اسم الكاش المحلي لتقليل استهلاك GitHub API
define('CACHE_FILE', __DIR__ . '/.gist_cache.json');
define('CACHE_TTL', 30); // ثانية

/* ============================================================
   دوال GitHub Gist
   ============================================================ */

function githubApi(string $method, string $endpoint, ?array $body = null): array {
    $ch = curl_init('https://api.github.com' . $endpoint);
    $headers = [
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: JK × YS-Client',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) return ['ok' => false, 'error' => 'curl_failed', 'code' => 0];
    $json = json_decode($res, true);
    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'data' => $json];
}

/**
 * قراءة محتوى الـ Gist (مع كاش محلي)
 */
function getGistContent(bool $forceRefresh = false): ?array {
    if (!$forceRefresh && file_exists(CACHE_FILE)) {
        $age = time() - filemtime(CACHE_FILE);
        if ($age < CACHE_TTL) {
            $cached = json_decode((string)file_get_contents(CACHE_FILE), true);
            if (is_array($cached)) return $cached;
        }
    }

    $res = githubApi('GET', '/gists/' . GIST_ID);
    if (!$res['ok'] || empty($res['data']['files'][GIST_FILENAME]['content'])) {
        // إذا فشل الطلب الخارجي، حاول استخدام الكاش القديم كخطة بديلة
        if (file_exists(CACHE_FILE)) {
            $cached = json_decode((string)file_get_contents(CACHE_FILE), true);
            if (is_array($cached)) return $cached;
        }
        return null;
    }

    $content = json_decode($res['data']['files'][GIST_FILENAME]['content'], true);
    if (!is_array($content)) return null;

    @file_put_contents(CACHE_FILE, json_encode($content), LOCK_EX);
    return $content;
}

/**
 * كتابة محتوى الـ Gist (لتحديث عدّاد الأجهزة أو المفاتيح)
 */
function saveGistContent(array $content): bool {
    $res = githubApi('PATCH', '/gists/' . GIST_ID, [
        'files' => [
            GIST_FILENAME => ['content' => json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]
        ]
    ]);
    if ($res['ok']) {
        @file_put_contents(CACHE_FILE, json_encode($content), LOCK_EX);
        return true;
    }
    return false;
}