<?php
declare(strict_types=1);

/* ============================================================
   JK × YS — ADMIN PANEL (single file)
   ============================================================ */

/* ================== الإعدادات (عدّلها) ================== */
const ADMIN_USER    = 'admin';                       // ← اسم المستخدم
const ADMIN_PASS    = '#FFHUDT6O3j7F0DdVPo7eP';          // ← كلمة المرور (نص عادي)
const GITHUB_TOKEN  = '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'; // ← توكن GitHub
const GIST_ID       = '758027f903ab4106e76ca4a6d6b1a773';         // ← معرف الـ Gist
const GIST_FILENAME = 'JK×YS.json';

/* ================== الجلسة ================== */
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, 'samesite' => 'Strict',
]);
session_start();

/* ================== دوال Gist ================== */
function gistApi(string $method, ?array $body = null): array {
    $ch = curl_init('https://api.github.com/gists/' . GIST_ID);
    $headers = [
        'Authorization: Bearer ' . GITHUB_TOKEN,
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: JK×YS-Admin',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok'=>$code>=200 && $code<300, 'data'=>json_decode((string)$res, true)];
}

function gistRead(): ?array {
    $r = gistApi('GET');
    if (!$r['ok'] || empty($r['data']['files'][GIST_FILENAME]['content'])) return null;
    $c = json_decode($r['data']['files'][GIST_FILENAME]['content'], true);
    return is_array($c) ? $c : null;
}

function gistWrite(array $content): bool {
    $r = gistApi('PATCH', [
        'files' => [GIST_FILENAME => [
            'content' => json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]]
    ]);
    return $r['ok'];
}

/* ================== CSRF ================== */
if (empty($_SESSION['adm_csrf'])) $_SESSION['adm_csrf'] = bin2hex(random_bytes(32));
function csrfOk(): bool {
    return hash_equals($_SESSION['adm_csrf'], (string)($_POST['csrf'] ?? ''));
}

/* ================== تسجيل الدخول / الخروج ================== */
$flash = ''; $flashType = 'ok';
$loginErr = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='login') {
    $u = (string)($_POST['user'] ?? '');
    $p = (string)($_POST['pass'] ?? '');
    if (hash_equals(ADMIN_USER, $u) && hash_equals(ADMIN_PASS, $p)) {
        $_SESSION['adm_in'] = true;
        header('Location: index.php');
        exit;
    }
    $loginErr = 'بيانات الدخول خاطئة';
}

$logged = !empty($_SESSION['adm_in']);

/* ================== عمليات الأدمن ================== */
if ($logged && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')!=='login') {
    if (!csrfOk()) { $flash='CSRF غير صالح'; $flashType='err'; }
    else {
        $act = (string)($_POST['act'] ?? '');
        $cfg = gistRead();
        if (!$cfg) { $flash='تعذر قراءة Gist'; $flashType='err'; }
        else {
            if ($act==='maintenance') {
                $on = !empty($_POST['active']);
                $cfg['maintenance'] = ['active'=>$on, 'message'=>(string)($_POST['msg']??'')];
                $ok = gistWrite($cfg);
                $flash = $ok ? ($on?'تم تفعيل الصيانة':'تم إيقاف الصيانة') : 'فشل الحفظ';
                $flashType = $ok ? 'ok' : 'err';
            }
            elseif ($act==='telegram') {
                $cfg['telegram'] = trim((string)($_POST['telegram']??''));
                $ok = gistWrite($cfg);
                $flash = $ok ? 'تم تحديث رابط التلغرام' : 'فشل الحفظ';
                $flashType = $ok ? 'ok' : 'err';
            }
            elseif ($act==='save_key') {
                $bucket = in_array($_POST['bucket']??'', ['keys','resellers','developers'], true) ? $_POST['bucket'] : 'keys';
                $key    = strtoupper(trim((string)($_POST['key']??'')));
                if ($key==='') { $flash='المفتاح فارغ'; $flashType='err'; }
                else {
                    $created = (int)($_POST['created_at']??time());
                    $days    = (int)($_POST['duration_days']??30);
                    $maxDev  = (int)($_POST['max_devices']??1);
                    $expires = (int)($_POST['expires_at']??0);
                    if ($expires<=0 && $days>0) $expires = $created + $days*86400;
                    if (!isset($cfg[$bucket]) || !is_array($cfg[$bucket])) $cfg[$bucket]=[];
                    $old = $cfg[$bucket][$key] ?? [];
                    $cfg[$bucket][$key] = array_merge($old, [
                        'created_at'=>$created,'duration_days'=>$days,
                        'max_devices'=>$maxDev,'expires_at'=>$expires,
                        'devices'=>$old['devices']??[],
                    ]);
                    if ($bucket==='developers') $cfg[$bucket][$key]['role']='developer';
                    $ok = gistWrite($cfg);
                    $flash = $ok ? "تم حفظ المفتاح: $key" : 'فشل الحفظ';
                    $flashType = $ok ? 'ok' : 'err';
                }
            }
            elseif ($act==='del_key') {
                $bucket = (string)($_POST['bucket']??'');
                $key    = strtoupper(trim((string)($_POST['key']??'')));
                if (isset($cfg[$bucket][$key])) {
                    unset($cfg[$bucket][$key]);
                    $ok = gistWrite($cfg);
                    $flash = $ok ? "تم حذف المفتاح: $key" : 'فشل الحذف';
                    $flashType = $ok ? 'ok' : 'err';
                }
            }
            elseif ($act==='reset_devices') {
                $bucket = (string)($_POST['bucket']??'');
                $key    = strtoupper(trim((string)($_POST['key']??'')));
                if (isset($cfg[$bucket][$key])) {
                    $cfg[$bucket][$key]['devices'] = [];
                    $ok = gistWrite($cfg);
                    $flash = $ok ? "تم تصفير أجهزة: $key" : 'فشل';
                    $flashType = $ok ? 'ok' : 'err';
                }
            }
        }
    }
}

/* ================== جلب البيانات للعرض ================== */
$cfg = $logged ? gistRead() : null;
$maint      = $cfg['maintenance'] ?? ['active'=>false,'message'=>''];
$tgUrl      = $cfg['telegram']    ?? '';
$keys       = $cfg['keys']        ?? [];
$resellers  = $cfg['resellers']   ?? [];
$developers = $cfg['developers']  ?? [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>JK×YS MOD — Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Cairo',system-ui,sans-serif;}
body{background:#0a0a0a;color:#f5f5f5;min-height:100dvh;padding:20px;}
.wrap{max-width:1200px;margin:0 auto;}
a{text-decoration:none;}

/* Login */
.login{max-width:380px;margin:80px auto;background:#151515;border:1px solid #2a2a2a;border-radius:12px;padding:32px;}
.login h1{font-size:1.6rem;letter-spacing:3px;text-align:center;margin-bottom:6px;}
.login .sub{text-align:center;color:#8a8a8a;font-size:.8rem;margin-bottom:24px;letter-spacing:1px;}
.login label{display:block;font-size:.75rem;color:#9ca3af;margin-bottom:6px;font-weight:700;}
.login input{width:100%;padding:12px;background:#0f0f0f;border:1px solid #2a2a2a;border-radius:6px;color:#fff;font-size:.95rem;margin-bottom:16px;}
.login input:focus{outline:none;border-color:#fff;}
.btn{display:inline-block;padding:10px 18px;border:none;border-radius:6px;cursor:pointer;font-weight:700;font-size:.85rem;}
.btn-primary{background:#fff;color:#000;width:100%;padding:14px;}
.btn-primary:hover{opacity:.9;}
.btn-sm{padding:6px 12px;font-size:.75rem;}
.btn-danger{background:#7f1d1d;color:#fca5a5;border:1px solid #b91c1c;}
.btn-danger:hover{background:#991b1b;}
.btn-warn{background:#78350f;color:#fcd34d;border:1px solid #b45309;}
.btn-warn:hover{background:#92400e;}

/* Flash */
.flash{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem;font-weight:700;}
.flash.ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.4);color:#86efac;}
.flash.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.4);color:#fca5a5;}

/* Header */
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #2a2a2a;flex-wrap:wrap;gap:10px;}
.header h1{font-size:1.3rem;letter-spacing:2px;}
.badge{background:#1f1f1f;border:1px solid #2f2f2f;color:#9ca3af;padding:4px 10px;border-radius:20px;font-size:.7rem;font-family:monospace;margin-top:6px;display:inline-block;}
.logout{background:#7f1d1d;color:#fca5a5;border:1px solid #b91c1c;padding:8px 14px;border-radius:6px;font-size:.75rem;font-weight:700;}

/* Panels */
.panel{background:#151515;border:1px solid #2a2a2a;border-radius:10px;padding:18px;margin-bottom:16px;}
.panel h2{font-size:.95rem;letter-spacing:1.5px;margin-bottom:14px;color:#fff;display:flex;align-items:center;gap:8px;}
.dot{width:8px;height:8px;background:#fff;border-radius:50%;}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;}
.stat{background:#0f0f0f;border:1px solid #2a2a2a;border-radius:8px;padding:14px;text-align:center;}
.stat .n{font-size:1.6rem;font-weight:800;font-family:monospace;}
.stat .l{font-size:.65rem;color:#8a8a8a;margin-top:4px;letter-spacing:1px;}

/* Fields */
.field{margin-bottom:12px;}
.field label{display:block;font-size:.7rem;color:#9ca3af;margin-bottom:4px;font-weight:700;letter-spacing:1px;}
.field input,.field select{width:100%;padding:10px;background:#0f0f0f;border:1px solid #2a2a2a;border-radius:6px;color:#fff;font-size:.85rem;}
.field input:focus,.field select:focus{outline:none;border-color:#fff;}
.toggle{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.toggle input{width:20px;height:20px;cursor:pointer;}
.grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;}
.grid-4{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;}

/* Table */
.tbl{width:100%;border-collapse:collapse;font-size:.82rem;}
.tbl th{background:#1a1a1a;padding:10px;text-align:right;font-size:.7rem;color:#9ca3af;letter-spacing:1px;border-bottom:1px solid #2a2a2a;}
.tbl td{padding:10px;border-bottom:1px solid #1f1f1f;vertical-align:middle;}
.tbl tr:hover{background:#121212;}
.key{font-family:monospace;font-size:.78rem;}
.expired{color:#ef4444;} .active{color:#22c55e;} .never{color:#f59e0b;}
.actions{display:flex;gap:6px;flex-wrap:wrap;}
.muted{color:#8a8a8a;font-size:.8rem;}
</style>
</head>
<body>
<div class="wrap">

<?php if (!$logged): ?>
  <!-- شاشة الدخول -->
  <div class="login">
    <h1>JK×YS</h1>
    <div class="sub">ADMIN PANEL</div>
    <?php if ($loginErr): ?><div class="flash err"><?=htmlspecialchars($loginErr)?></div><?php endif;?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="act" value="login">
      <label>USERNAME</label>
      <input type="text" name="user" required autofocus>
      <label>PASSWORD</label>
      <input type="password" name="pass" required>
      <button class="btn btn-primary" type="submit">تسجيل الدخول</button>
    </form>
  </div>

<?php else: ?>

  <div class="header">
    <div>
      <h1>JK×YS — ADMIN</h1>
      <div class="badge">Gist: <?=htmlspecialchars(substr(GIST_ID,0,10))?>…</div>
    </div>
    <a href="?logout=1" class="logout">خروج</a>
  </div>

  <?php if ($flash): ?><div class="flash <?=$flashType?>"><?=htmlspecialchars($flash)?></div><?php endif;?>

  <?php if (!$cfg): ?>
    <div class="flash err">تعذر الاتصال بالـ Gist — تحقق من التوكن ورقم الـ Gist.</div>
  <?php else: ?>

  <!-- الإحصائيات -->
  <div class="panel">
    <h2><span class="dot"></span>نظرة عامة</h2>
    <div class="stats">
      <div class="stat"><div class="n"><?=count($keys)?></div><div class="l">مستخدمون</div></div>
      <div class="stat"><div class="n"><?=count($resellers)?></div><div class="l">موزعون</div></div>
      <div class="stat"><div class="n"><?=count($developers)?></div><div class="l">مطورون</div></div>
      <div class="stat"><div class="n"><?=count($keys)+count($resellers)+count($developers)?></div><div class="l">المجموع</div></div>
    </div>
  </div>

  <!-- الصيانة -->
  <div class="panel">
    <h2><span class="dot"></span>الصيانة</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=$_SESSION['adm_csrf']?>">
      <input type="hidden" name="act" value="maintenance">
      <div class="toggle">
        <input type="checkbox" id="m" name="active" value="1" <?=!empty($maint['active'])?'checked':''?>>
        <label for="m">تفعيل الصيانة (يمنع الدخول)</label>
      </div>
      <div class="field">
        <label>رسالة الصيانة</label>
        <input type="text" name="msg" value="<?=htmlspecialchars((string)($maint['message']??''))?>">
      </div>
      <button class="btn btn-primary" type="submit" style="width:auto;">حفظ</button>
    </form>
  </div>

  <!-- التلغرام -->
  <div class="panel">
    <h2><span class="dot"></span>رابط التلغرام</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=$_SESSION['adm_csrf']?>">
      <input type="hidden" name="act" value="telegram">
      <div class="field">
        <label>TELEGRAM URL</label>
        <input type="url" name="telegram" value="<?=htmlspecialchars($tgUrl)?>" placeholder="https://t.me/YourProfile">
      </div>
      <button class="btn btn-primary" type="submit" style="width:auto;">حفظ</button>
    </form>
  </div>

  <!-- إضافة / تعديل مفتاح -->
  <div class="panel">
    <h2><span class="dot"></span>إضافة / تعديل مفتاح</h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?=$_SESSION['adm_csrf']?>">
      <input type="hidden" name="act" value="save_key">
      <div class="grid-4">
        <div class="field">
          <label>النوع</label>
          <select name="bucket">
            <option value="keys">مستخدم</option>
            <option value="resellers">موزع</option>
            <option value="developers">مطور</option>
          </select>
        </div>
        <div class="field">
          <label>المفتاح</label>
          <input type="text" name="key" placeholder="JK×YS-XXXX-XXXX-XXXX" required style="font-family:monospace;text-transform:uppercase;">
        </div>
        <div class="field">
          <label>أيام (0 = دائم)</label>
          <input type="number" name="duration_days" value="30" min="0">
        </div>
        <div class="field">
          <label>حد الأجهزة</label>
          <input type="number" name="max_devices" value="1" min="1">
        </div>
      </div>
      <div class="grid-2">
        <div class="field">
          <label>تاريخ الإنشاء (Unix)</label>
          <input type="number" name="created_at" value="<?=time()?>">
        </div>
        <div class="field">
          <label>تاريخ الانتهاء (0 = تلقائي)</label>
          <input type="number" name="expires_at" value="0">
        </div>
      </div>
      <button class="btn btn-primary" type="submit" style="width:auto;">حفظ</button>
    </form>
  </div>

  <!-- جداول المفاتيح -->
  <?php foreach ([
      'keys'=>'المستخدمون',
      'resellers'=>'الموزعون',
      'developers'=>'المطورون',
  ] as $bucket=>$label): $list = $$bucket; ?>
    <div class="panel">
      <h2><span class="dot"></span><?=$label?> (<?=count($list)?>)</h2>
      <?php if (!$list): ?>
        <p class="muted">لا يوجد.</p>
      <?php else: ?>
      <table class="tbl">
        <thead><tr><th>المفتاح</th><th>الإنشاء</th><th>الانتهاء</th><th>الأجهزة</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($list as $k=>$v):
            $exp = (int)($v['expires_at']??0);
            $cls = $exp===0?'never':($exp<time()?'expired':'active');
            $txt = $exp===0?'دائم':date('Y-m-d',$exp);
            $cr  = (int)($v['created_at']??0);
            $dn  = count($v['devices']??[]);
            $mx  = (int)($v['max_devices']??1);
        ?>
          <tr>
            <td class="key"><?=htmlspecialchars($k)?></td>
            <td class="muted"><?=$cr?date('Y-m-d',$cr):'—'?></td>
            <td class="<?=$cls?>"><?=$txt?></td>
            <td class="muted"><?=$dn?>/<?=$mx?></td>
            <td>
              <div class="actions">
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?=$_SESSION['adm_csrf']?>">
                  <input type="hidden" name="act" value="reset_devices">
                  <input type="hidden" name="bucket" value="<?=$bucket?>">
                  <input type="hidden" name="key" value="<?=htmlspecialchars($k)?>">
                  <button class="btn btn-warn btn-sm" onclick="return confirm('تصفير أجهزة المفتاح؟')">تصفير</button>
                </form>
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?=$_SESSION['adm_csrf']?>">
                  <input type="hidden" name="act" value="del_key">
                  <input type="hidden" name="bucket" value="<?=$bucket?>">
                  <input type="hidden" name="key" value="<?=htmlspecialchars($k)?>">
                  <button class="btn btn-danger btn-sm" onclick="return confirm('حذف المفتاح؟')">حذف</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>