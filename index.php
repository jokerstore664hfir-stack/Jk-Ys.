<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

session_set_cookie_params([
    'lifetime'=>0,'path'=>'/',
    'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
    'httponly'=>true,'samesite'=>'Strict',
]);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0a0a0a">
<meta name="robots" content="noindex, nofollow">
<title>DARK MOD</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Chakra+Petch:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0a0a; --card:#151515; --card2:#1a1a1a; --border:#2a2a2a;
  --text:#f5f5f5; --muted:#8a8a8a;
  --gray:#9ca3af; --gray-lite:#d1d5db; --gray-dark:#4b5563;
  --white:#ffffff; --black:#000000;
  --success:#22c55e; --error:#ef4444; --warning:#f59e0b;
  --font-title:'Anton',sans-serif;
  --font-petch:'Chakra Petch',sans-serif;
  --font-mono:'JetBrains Mono',monospace;
  --font-arabic:'Cairo',sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;
  font-family:var(--font-petch),-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;}
html,body{height:100%;}
body{background:var(--bg);color:var(--text);display:flex;justify-content:center;align-items:center;
  min-height:100dvh;overflow:hidden;position:relative;}
body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
  background-size:44px 44px;
  -webkit-mask-image:radial-gradient(circle at 50% 40%,#000 0%,transparent 72%);
  mask-image:radial-gradient(circle at 50% 40%,#000 0%,transparent 72%);}

.app-container{position:relative;z-index:1;width:100%;max-width:420px;height:100dvh;
  padding:16px;display:flex;flex-direction:column;}
.view{display:none;flex-direction:column;height:100%;min-height:0;}
.view.active{display:flex;animation:fadeUp .45s cubic-bezier(.22,1,.36,1) both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:none;}}
.ph-cut-sm{clip-path:polygon(0 0,100% 0,100% calc(100% - 12px),calc(100% - 12px) 100%,0 100%);}
.ph-cut-xs{clip-path:polygon(0 0,100% 0,100% calc(100% - 6px),calc(100% - 6px) 100%,0 100%);}

.brand-logo{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;margin-bottom:30px;width:100%;}
.brand-line-1{font-family:var(--font-title)!important;font-size:2.6rem;letter-spacing:2px;line-height:1.1;color:#fff;text-transform:uppercase;text-align:center;}
.brand-line-2{font-family:var(--font-title)!important;font-size:2.4rem;letter-spacing:8px;line-height:1.1;color:var(--gray-lite);text-transform:uppercase;text-align:center;text-shadow:0 0 15px rgba(255,255,255,.15);}
.brand-sub{font-family:var(--font-petch);font-size:11px;color:var(--muted);letter-spacing:2px;text-transform:uppercase;font-weight:700;margin-top:12px;text-align:center;}

#loginView{justify-content:center;align-items:center;text-align:center;gap:18px;}
.input-wrap{width:100%;display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
.input-label{display:flex;align-items:center;gap:8px;font-family:var(--font-petch);font-size:11px;font-weight:700;letter-spacing:1.5px;color:var(--gray-lite);text-transform:uppercase;}
.input-label span.dot{width:6px;height:6px;background:var(--white);display:inline-block;}
.cyber-input-box{background:var(--card);border:1px solid var(--border);display:flex;align-items:center;padding:4px;width:100%;transition:border-color .2s,box-shadow .2s;}
.cyber-input-box:focus-within{border-color:var(--gray-lite);box-shadow:0 0 15px rgba(255,255,255,.1);}
.cyber-input{flex:1;background:transparent;border:none;outline:none;color:#fff;padding:12px 14px;font-family:var(--font-petch);font-size:14px;font-weight:700;letter-spacing:2px;text-transform:uppercase;min-width:0;}
.cyber-input::placeholder{color:#3f3f3f;font-weight:500;}
.cyber-btn-paste{background:#1f1f1f;border:1px solid #2f2f2f;color:#e5e5e5;padding:10px 16px;font-family:var(--font-petch);font-size:11px;font-weight:700;letter-spacing:1px;text-transform:lowercase;cursor:pointer;transition:background .2s;}
.cyber-btn-paste:active{background:#2a2a2a;}

.cyber-btn-primary{width:100%;background:#fff;color:#000;border:none;padding:18px;font-family:var(--font-title);font-size:22px;letter-spacing:2px;text-transform:lowercase;cursor:pointer;box-shadow:0 8px 25px rgba(255,255,255,.15);transition:transform .15s,opacity .2s;}
.cyber-btn-primary:active{transform:scale(.98);}
.cyber-btn-primary:disabled{opacity:.6;cursor:not-allowed;}

.help-text{display:flex;flex-direction:column;gap:4px;text-align:center;}
.help-text span:first-child{font-size:13px;color:#a3a3a3;}
.help-text span:last-child{font-size:11px;color:#525252;}

.top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex:none;}
.status{display:flex;align-items:center;gap:8px;color:var(--success);font-size:.7rem;font-weight:800;letter-spacing:1px;}
.status .dot{width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 10px var(--success);animation:blink 1.6s ease-in-out infinite;}
@keyframes blink{50%{opacity:.3;}}

.btn-logout{background:var(--card);border:1px solid var(--border);color:#b9b9b9;font-family:var(--font-petch);font-size:.65rem;font-weight:700;letter-spacing:1.5px;padding:8px 14px;cursor:pointer;text-transform:uppercase;}

.scroll-content{flex:1;min-height:0;overflow-y:auto;-webkit-overflow-scrolling:touch;padding-bottom:10px;scrollbar-width:none;}
.scroll-content::-webkit-scrollbar{display:none;}
.pane{display:none;}
.pane.active{display:block;animation:fadeUp .35s both;}

.timer-section{display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;margin:10px 0 30px 0;}
.timer-circle{position:relative;width:280px;height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.progress-ring-container{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;transform:rotate(180deg);}
.progress-ring{width:100%;height:100%;}
.progress-ring__circle-bg{fill:transparent;stroke:rgba(255,255,255,.08);stroke-width:12;}
.progress-ring__circle-fg{fill:transparent;stroke:#fff;stroke-width:12;stroke-linecap:butt;transition:stroke-dashoffset .5s linear;filter:drop-shadow(0 0 8px rgba(255,255,255,.5));}
.timer-inner{width:220px;height:220px;border-radius:50%;
  background:radial-gradient(circle at 50% 50%,rgba(255,255,255,.06) 0%,transparent 70%);
  border:1px solid rgba(255,255,255,.12);box-shadow:inset 0 0 30px rgba(255,255,255,.05);
  display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;z-index:2;}
.timer-brand{text-align:center;margin-bottom:8px;display:flex;flex-direction:column;align-items:center;gap:2px;}
.timer-brand .tb-line1{font-family:var(--font-title)!important;font-size:1.5rem;letter-spacing:1.5px;color:#fff;line-height:1;text-shadow:0 0 10px rgba(255,255,255,.2);}
.timer-brand .tb-line2{font-family:var(--font-title)!important;font-size:1rem;letter-spacing:5px;color:var(--gray-lite);line-height:1;text-shadow:0 0 12px rgba(255,255,255,.3);}
.time-display{font-family:var(--font-mono);font-size:2rem;font-weight:800;color:#fff;line-height:1;display:flex;align-items:baseline;gap:4px;position:relative;z-index:3;text-shadow:0 0 10px rgba(255,255,255,.3);margin-top:2px;}
.time-unit{font-size:.9rem;color:var(--gray-lite);font-weight:600;}
.timer-lbl{font-size:.7rem;color:var(--muted);margin-top:6px;text-transform:uppercase;letter-spacing:1px;}

.info-grid{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
.key-bar{background:var(--card);border:1px solid var(--border);padding:18px 16px;display:flex;align-items:center;justify-content:space-between;clip-path:polygon(0 0,100% 0,100% calc(100% - 12px),calc(100% - 12px) 100%,0 100%);}
.key-bar-label{font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700;letter-spacing:1px;}
.key-bar-value{font-family:var(--font-mono);font-size:15px;font-weight:700;color:var(--gray-lite);letter-spacing:1px;margin-top:2px;transition:filter .3s,opacity .3s;word-break:break-all;}
.key-bar-value.blurred{filter:blur(7px);user-select:none;opacity:.6;}
.key-bar-actions{display:flex;gap:8px;align-items:center;}
.key-bar-btn{background:#1f1f1f;border:1px solid #2f2f2f;color:var(--gray-lite);padding:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;clip-path:polygon(0 0,100% 0,100% calc(100% - 6px),calc(100% - 6px) 100%,0 100%);}
.key-bar-btn svg{width:18px;height:18px;}
.details-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.detail-box{background:var(--card);border:1px solid var(--border);padding:16px 8px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:8px;clip-path:polygon(0 0,100% 0,100% calc(100% - 8px),calc(100% - 8px) 100%,0 100%);}
.detail-title{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;}
.detail-val{font-family:var(--font-petch);font-size:13px;font-weight:700;color:#fff;}
.detail-val.blue{color:var(--gray-lite);}
.info-card{background:var(--card);border:1px solid var(--border);border-left:3px solid var(--white);padding:14px 16px;margin-bottom:14px;}
.info-title{font-size:.65rem;font-weight:900;letter-spacing:1.5px;color:var(--gray-lite);text-transform:uppercase;margin-bottom:6px;}
.info-text{font-size:.8rem;color:var(--text);line-height:1.5;}

.sub-btn-wrap{margin-top:auto;padding-top:10px;transition:opacity .2s;width:100%;}
.sub-btn-wrap.hidden{display:none;}
.sub-btn{width:100%;background:#fff;color:#000;border:none;padding:22px;font-family:var(--font-petch);font-size:18px;font-weight:800;text-transform:uppercase;display:flex;justify-content:space-between;align-items:center;clip-path:polygon(0 0,100% 0,100% calc(100% - 14px),calc(100% - 14px) 100%,0 100%);cursor:pointer;transition:all .2s;box-shadow:0 0 20px rgba(255,255,255,.2);}
.sub-btn:active{transform:scale(.98);}
.sub-btn.expired{background:rgba(255,255,255,.15);color:rgba(255,255,255,.5);box-shadow:none;cursor:not-allowed;}
.sub-btn-icon{width:38px;height:38px;background:rgba(0,0,0,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;}
.sub-btn.expired .sub-btn-icon{background:rgba(255,255,255,.1);}

.nav-bar{flex:none;display:flex;justify-content:space-around;padding:15px 0;margin-top:10px;border-top:1px solid var(--border);}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--muted);background:transparent;border:none;cursor:pointer;transition:color .2s;}
.nav-item.active{color:#fff;}
.nav-item svg{width:24px;height:24px;}
.nav-item span{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}

.sec-title{font-family:var(--font-petch);font-size:.75rem;font-weight:900;letter-spacing:2px;color:var(--muted);text-transform:uppercase;margin:4px 2px 16px;}
.set-item{display:flex;align-items:center;gap:13px;width:100%;padding:18px 16px;margin-bottom:10px;background:var(--card);border:1px solid var(--border);text-decoration:none;color:inherit;font-family:inherit;text-align:left;cursor:pointer;transition:border-color .2s;clip-path:polygon(0 0,100% 0,100% calc(100% - 10px),calc(100% - 10px) 100%,0 100%);}
.set-ico{width:44px;height:44px;flex:none;display:grid;place-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.15);color:var(--gray-lite);border-radius:8px;}
.set-ico svg{width:22px;height:22px;}
.set-body{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;flex:1;min-width:0;gap:3px;}
html[dir="rtl"] .set-body{align-items:flex-end;}
.set-title{font-family:var(--font-petch);font-size:.95rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#fff;line-height:1.2;margin:0;}
.set-sub{font-size:.75rem;font-weight:500;color:var(--muted);line-height:1.2;margin:0;word-break:break-word;}
.set-arrow{color:#525252;flex:none;display:flex;align-items:center;justify-content:center;}
.set-arrow svg{width:20px;height:20px;}
.about-box{margin-top:16px;text-align:center;font-family:var(--font-petch);font-size:.75rem;font-weight:700;color:#525252;line-height:2;}

.loading-overlay{position:fixed;inset:0;background:rgba(10,10,10,.95);z-index:9999;display:none;align-items:center;justify-content:center;flex-direction:column;gap:20px;}
.loading-overlay.show{display:flex;}
.spinner{width:44px;height:44px;border:3px solid rgba(255,255,255,.1);border-top-color:#fff;border-radius:50%;animation:spin 1s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.loading-text{color:#fff;font-family:var(--font-petch);font-size:.8rem;font-weight:900;letter-spacing:2px;text-transform:uppercase;}

.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--card);border:1px solid #fff;color:#fff;padding:12px 20px;font-family:var(--font-petch);font-size:.85rem;font-weight:700;z-index:9999;opacity:0;transition:all .3s;pointer-events:none;max-width:90%;text-align:center;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.error-msg{display:none;font-size:.8rem;margin-top:8px;font-weight:700;color:var(--error);}

.maintenance-overlay{position:fixed;inset:0;background:rgba(10,10,10,.95);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .3s ease;}
.maintenance-overlay.show{opacity:1;pointer-events:auto;}
.maintenance-box{background:var(--card);border:1px solid var(--border);border-top:4px solid var(--warning);padding:30px 20px;width:100%;max-width:360px;text-align:center;clip-path:polygon(0 0,100% 0,100% calc(100% - 20px),calc(100% - 20px) 100%,0 100%);box-shadow:0 10px 40px rgba(0,0,0,.6),0 0 20px rgba(245,158,11,.1);transform:translateY(20px);transition:transform .3s ease;}
.maintenance-overlay.show .maintenance-box{transform:translateY(0);}
.maintenance-icon{width:64px;height:64px;margin:0 auto 20px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--warning);animation:pulseWarning 2s infinite;}
@keyframes pulseWarning{0%{box-shadow:0 0 0 0 rgba(245,158,11,.4);}70%{box-shadow:0 0 0 15px rgba(245,158,11,0);}100%{box-shadow:0 0 0 0 rgba(245,158,11,0);}}
.maintenance-icon svg{width:32px;height:32px;}
.maintenance-title{font-family:var(--font-title);font-size:1.8rem;color:#fff;letter-spacing:1px;margin-bottom:10px;text-transform:uppercase;}
.maintenance-text{font-size:.9rem;color:var(--muted);line-height:1.5;margin-bottom:20px;}
.maint-tg-btn{width:100%;background:transparent;color:var(--gray-lite);border:1px solid var(--gray-lite);padding:14px;font-family:var(--font-petch);font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;margin-top:10px;clip-path:polygon(0 0,100% 0,100% calc(100% - 10px),calc(100% - 10px) 100%,0 100%);}
.maint-tg-btn:hover{background:rgba(255,255,255,.05);box-shadow:0 0 10px rgba(255,255,255,.2);}
.maint-tg-btn svg{width:18px;height:18px;}

html[dir="rtl"] body{font-family:var(--font-arabic)!important;}
html[dir="rtl"] *:not(.keep-english-font):not(.brand-line-1):not(.brand-line-2):not(.tb-line1):not(.tb-line2){font-family:var(--font-arabic)!important;}
html[dir="rtl"] .brand-line-1,html[dir="rtl"] .brand-line-2,html[dir="rtl"] .timer-brand .tb-line1,html[dir="rtl"] .timer-brand .tb-line2{font-family:var(--font-title)!important;}
html[dir="rtl"] .cyber-input{text-align:right;letter-spacing:1px;direction:ltr;}
html[dir="rtl"] .set-arrow{transform:rotate(180deg);}
</style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div class="loading-text" data-i18n="loading">جاري التحميل...</div>
</div>

<div class="toast" id="toast"></div>

<!-- نافذة الصيانة -->
<div class="maintenance-overlay" id="maintenanceModal">
  <div class="maintenance-box">
    <div class="maintenance-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
    <div class="maintenance-title" data-i18n="maint_title">صيانة</div>
    <div class="maintenance-text" data-i18n="maint_text">نقوم حالياً بتحديث الأنظمة. يرجى المحاولة لاحقاً.</div>
    <button class="cyber-btn-primary ph-cut-sm" onclick="location.reload()" data-i18n="maint_refresh" style="margin-bottom:10px;">تحديث</button>
    <a href="<?= htmlspecialchars(TELEGRAM_URL, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="maint-tg-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
      <span data-i18n="tg_profile">تلغرام</span>
    </a>
  </div>
</div>

<div class="app-container">

  <!-- شاشة الدخول -->
  <section id="loginView" class="view active">
    <div class="brand-logo">
      <div class="brand-line-1">DARK</div>
      <div class="brand-line-2">MOD</div>
      <div class="brand-sub" data-i18n="brand_sub">بدون حساب. بدون كلمة مرور. مفتاحك فقط.</div>
    </div>

    <form onsubmit="event.preventDefault(); login();" style="width:100%;">
      <div class="input-wrap">
        <label class="input-label" for="accessKey"><span class="dot"></span><span data-i18n="access_key">مفتاح الدخول</span></label>
        <div class="cyber-input-box ph-cut-sm" id="inputWrapper">
          <input type="password" id="accessKey" class="cyber-input" placeholder="DARK-XXXX-XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false">
          <button type="button" class="cyber-btn-paste ph-cut-xs" onclick="pasteKey()" data-i18n="paste">لصق</button>
          <button type="button" onclick="toggleKeyVisibility()" style="background:transparent;border:none;color:var(--muted);padding:0 12px;cursor:pointer;">
            <svg id="eyeIconOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;display:none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeIconClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
        <div class="error-msg" id="errorMsg"></div>
      </div>
      <button type="submit" class="cyber-btn-primary ph-cut-sm" id="unlockBtn" data-i18n="unlock">دخول</button>
      <div class="help-text" style="margin-top:20px;">
        <span data-i18n="no_key">لا تملك مفتاحاً؟ تواصل مع البائع.</span>
        <span data-i18n="time_starts">الوقت يبدأ عند الاتصال.</span>
      </div>
    </form>
  </section>

  <!-- لوحة التحكم -->
  <section id="dashView" class="view">
    <div class="top-bar">
      <div class="status"><i class="dot"></i> <span data-i18n="online">متصل</span></div>
      <button class="