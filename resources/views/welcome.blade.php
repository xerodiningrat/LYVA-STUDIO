<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LYVA Studio - Pusat Operasi Roblox</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Orbitron:wght@500;700;800&family=Oxanium:wght@500;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css'])
        <style>
            :root{color-scheme:dark;--bg:#03050d;--panel:rgba(8,14,29,.8);--line:rgba(118,224,255,.12);--text:#f4f7ff;--muted:#9eacc8;--primary:#68f0ff;--secondary:#6f86ff;--accent:#7cffb2;--shadow:0 30px 80px rgba(0,0,0,.62);--mono:'JetBrains Mono',monospace;--display:'Orbitron',sans-serif;--display-alt:'Oxanium',sans-serif;--body:'Inter',sans-serif}
            *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;font-family:var(--body);color:var(--text);overflow-x:hidden;background:radial-gradient(circle at 18% 16%,rgba(88,114,255,.1),transparent 26%),radial-gradient(circle at 84% 12%,rgba(104,240,255,.08),transparent 24%),linear-gradient(180deg,#02040a 0%,#040916 42%,#03050d 100%)}a{text-decoration:none;color:inherit}.page{position:relative;isolation:isolate}.shell{width:min(1380px,calc(100% - 72px));margin:0 auto}
            .grid::before,.grid::after{content:"";position:fixed;inset:0;pointer-events:none}.grid::before{background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:48px 48px;opacity:.24;mask-image:linear-gradient(180deg,rgba(0,0,0,.92),transparent 96%)}.grid::after{height:140px;background:linear-gradient(180deg,transparent,rgba(255,255,255,.06),transparent);animation:scan 6s linear infinite}@keyframes scan{0%{transform:translateY(-150px)}100%{transform:translateY(calc(100vh + 150px))}}
            .matrix{position:fixed;inset:0;z-index:-1;opacity:.16;pointer-events:none}.particles{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none}.particle{position:absolute;font:700 14px var(--mono);color:rgba(235,242,255,.55);text-shadow:0 0 10px rgba(255,255,255,.15);animation:drift linear infinite}@keyframes drift{0%{transform:translate3d(0,88vh,0);opacity:0}15%,85%{opacity:.7}100%{transform:translate3d(42px,-14vh,0);opacity:0}}.kana-rain{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none;opacity:.24}.kana-stream{position:absolute;top:-20vh;display:flex;flex-direction:column;gap:10px;color:rgba(244,247,255,.3);font:700 17px/1 var(--mono);text-shadow:0 0 10px rgba(255,255,255,.12);animation:kanaFall linear infinite}.kana-stream span:nth-child(odd){color:rgba(202,221,255,.22)}@keyframes kanaFall{0%{transform:translateY(-25vh)}100%{transform:translateY(125vh)}}.code-ribbons{position:fixed;inset:0;z-index:-1;pointer-events:none;overflow:hidden}.code-ribbon{position:absolute;left:-12%;display:flex;gap:36px;white-space:nowrap;color:rgba(255,255,255,.16);font:700 13px var(--mono);letter-spacing:.16em;text-transform:uppercase;text-shadow:0 0 12px rgba(255,255,255,.08);mix-blend-mode:screen;animation:codeRibbon 28s linear infinite}.code-ribbon span{opacity:.88}.code-ribbon.ribbon-a{top:18%;animation-duration:24s}.code-ribbon.ribbon-b{top:52%;animation-duration:32s;animation-direction:reverse;color:rgba(214,232,255,.14)}.code-ribbon.ribbon-c{top:78%;animation-duration:27s}.code-ribbon::after{content:"";position:absolute;inset:-10px 0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);filter:blur(10px);opacity:.28}@keyframes codeRibbon{0%{transform:translateX(0)}100%{transform:translateX(18%)}}
            .loader{position:fixed;inset:0;z-index:120;background:radial-gradient(circle at 50% 30%,rgba(104,240,255,.08),transparent 30%),linear-gradient(180deg,rgba(3,8,20,.94),rgba(2,8,18,.98));display:flex;align-items:center;justify-content:center;padding:24px;transition:opacity .55s ease,visibility .55s ease}.loader.hidden{opacity:0;visibility:hidden}.loader-shell{width:min(760px,100%);position:relative}.loader-shell::before,.loader-shell::after{content:"";position:absolute;inset:auto 0 0 0;height:2px;background:linear-gradient(90deg,transparent,rgba(104,240,255,.7),transparent)}.loader-shell::before{top:18px;animation:loaderSweep 4s linear infinite}.loader-shell::after{bottom:8px;animation:loaderSweep 4s linear infinite reverse}.loader-box{position:relative;padding:0;border-radius:22px;border:2px solid rgba(0,255,170,.86);background:linear-gradient(180deg,rgba(8,40,44,.92),rgba(7,24,34,.96));box-shadow:0 0 0 1px rgba(104,240,255,.14),0 0 40px rgba(0,255,170,.22),0 0 80px rgba(104,240,255,.12);overflow:hidden}.loader-box::before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent 0 49.7%,rgba(104,240,255,.18) 49.9% 50.1%,transparent 50.3% 100%),linear-gradient(180deg,transparent 0 27%,rgba(104,240,255,.1) 27.3% 27.7%,transparent 28% 100%);pointer-events:none}.loader-box::after{content:"";position:absolute;inset:0;background:repeating-linear-gradient(180deg,rgba(255,255,255,.02) 0 2px,transparent 2px 4px);opacity:.45;pointer-events:none}.loader-head{display:flex;align-items:center;gap:14px;padding:20px 24px;border-bottom:2px solid rgba(0,255,170,.78);font:700 14px var(--mono);letter-spacing:.14em;text-transform:uppercase;color:#dffaff}.loader-dots{display:flex;gap:10px}.loader-dots span{width:12px;height:12px;border-radius:999px}.loader-dots span:nth-child(1){background:#ff4d5d}.loader-dots span:nth-child(2){background:#ffc83d}.loader-dots span:nth-child(3){background:#7cff88}.loader-body{padding:30px 24px 24px;font:700 clamp(.92rem,2vw,1.12rem)/1.9 var(--mono);color:#cce4ee;min-height:320px}.loader-line{opacity:0;transform:translateY(8px);animation:loaderLine .45s ease forwards}.loader-line:nth-child(1){animation-delay:.15s}.loader-line:nth-child(2){animation-delay:.35s}.loader-line:nth-child(3){animation-delay:.55s}.loader-line:nth-child(4){animation-delay:.75s}.loader-line:nth-child(5){animation-delay:.95s}.loader-line:nth-child(6){animation-delay:1.15s}.loader-line:nth-child(7){animation-delay:1.35s}.loader-prompt{color:#00ffae}.loader-info{display:block;margin-left:24px;color:#9fb7c2}.loader-ok{color:#6cf076}.loader-cursor{display:inline-block;color:#00ffae;animation:blink 1s steps(1,end) infinite}.loader-progress{margin-top:28px}.loader-track{height:6px;border-radius:999px;background:rgba(0,255,170,.12);border:1px solid rgba(0,255,170,.22);overflow:hidden;box-shadow:inset 0 2px 12px rgba(0,0,0,.38)}.loader-fill{height:100%;width:0;background:linear-gradient(90deg,#00ffa6,#68f0ff);box-shadow:0 0 18px rgba(104,240,255,.42);transition:width .2s ease}.loader-meta{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:12px;color:var(--muted);font:700 12px var(--mono);letter-spacing:.12em;text-transform:uppercase}.loader-percent{color:#dffaff}.app-shell{transition:opacity .4s ease,transform .5s ease}.app-shell.booting{opacity:0;transform:translateY(18px)}@keyframes loaderSweep{0%{transform:translateX(-18%);opacity:0}18%,82%{opacity:1}100%{transform:translateX(18%);opacity:0}}@keyframes loaderLine{to{opacity:1;transform:translateY(0)}}
            .header,.header::before,.header::after{background:none !important;border:0 !important;box-shadow:none !important;backdrop-filter:none !important}.header::before,.header::after{content:none !important;display:none !important}.header{position:sticky;top:0;z-index:40;padding-top:8px}.nav{display:flex;justify-content:space-between;align-items:center;gap:22px;padding:14px 22px;border-radius:24px;background:rgba(5,10,24,.92);border:1px solid rgba(104,240,255,.1);box-shadow:none}.brand{display:flex;align-items:center;gap:14px;min-width:0;padding-right:10px}.mark{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;font:800 11px var(--display);letter-spacing:.08em;color:#04111e;background:linear-gradient(135deg,var(--primary),var(--accent));box-shadow:none}.brand strong{display:block;font:800 12px/1.1 var(--display);letter-spacing:.12em;text-transform:uppercase}.brand span{display:block;margin-top:3px;color:var(--muted);font-size:11px;line-height:1.35}.nav-links{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;padding-left:10px}.pill{padding:8px 14px;border-radius:999px;border:1px solid rgba(104,240,255,.14);background:rgba(11,19,38,.52);color:var(--muted);font-size:12px;font-weight:600;transition:.18s;line-height:1}.pill:hover{color:var(--text);transform:translateY(-1px);border-color:rgba(104,240,255,.28)}.pill.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent;box-shadow:none}
            .hero{padding:16px 0 34px;min-height:calc(100vh - 76px);display:flex;align-items:flex-start}.hero-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(320px,.92fr);gap:28px;align-items:start}.eyebrow{display:inline-flex;align-items:center;gap:10px;padding:10px 14px;border-radius:999px;border:1px solid rgba(104,240,255,.18);background:rgba(11,19,38,.62);color:var(--primary);font:700 12px var(--mono);letter-spacing:.12em;text-transform:uppercase}.eyebrow::before{content:"";width:8px;height:8px;border-radius:999px;background:var(--accent);box-shadow:0 0 16px rgba(124,255,178,.8)}h1{margin:14px 0 14px;font:800 clamp(3.4rem,8.8vw,6.6rem)/.88 var(--display-alt);letter-spacing:.08em;text-transform:uppercase;text-shadow:0 0 24px rgba(104,240,255,.08)}h1 span{display:block}.glow{background:linear-gradient(90deg,var(--primary),#8be8ff,var(--accent));-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 0 18px rgba(104,240,255,.18))}.glitch{position:relative;display:inline-block;animation:glitchFlicker 5s linear infinite}.glitch::before,.glitch::after{content:attr(data-text);position:absolute;inset:0;pointer-events:none}.glitch::before{color:rgba(255,255,255,.9);transform:translate(2px,-1px);clip-path:inset(0 0 58% 0);animation:glitchSliceA 2.6s steps(2,end) infinite}.glitch::after{color:rgba(104,240,255,.8);transform:translate(-3px,1px);clip-path:inset(62% 0 0 0);animation:glitchSliceB 2s steps(2,end) infinite}@keyframes glitchSliceA{0%,100%{transform:translate(0,0);opacity:0}10%,18%{transform:translate(2px,-2px);opacity:.85}19%,100%{opacity:0}}@keyframes glitchSliceB{0%,100%{transform:translate(0,0);opacity:0}12%,20%{transform:translate(-3px,1px);opacity:.7}21%,100%{opacity:0}}@keyframes glitchFlicker{0%,94%,100%{filter:none}95%{filter:brightness(1.2)}96%{filter:brightness(.92)}}.lead{max-width:690px;margin:0;color:var(--muted);font-size:17px;line-height:1.85}.actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:22px}.cta{display:flex;align-items:center;gap:12px;padding:16px 20px;border-radius:18px;font-size:14px;font-weight:700;border:1px solid rgba(104,240,255,.16);background:rgba(11,19,38,.58);transition:.18s}.cta:hover{transform:translateY(-2px)}.cta div strong{display:block;font-size:15px;color:var(--text)}.cta div small{display:block;margin-top:4px;color:var(--muted);font-size:12px}.cta.primary{color:#04111e;background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:transparent;box-shadow:0 20px 40px rgba(84,129,255,.28)}.cta.primary div strong{color:#04111e}.cta.primary div small{color:rgba(4,17,30,.68)}.hero-visual-shell{position:relative}
            .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:28px}.stat,.panel,.feature,.workflow,.manifest,.ops,.main-card,.side-card,.step,.manifest-item,.footer-module{position:relative;overflow:hidden;transform-style:preserve-3d;will-change:transform;transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease,background .22s ease}.stat,.panel,.feature,.workflow,.manifest,.ops{border-radius:28px;background:var(--panel);border:1px solid var(--line);box-shadow:var(--shadow)}.stat::after,.feature::after,.workflow::after,.manifest::after,.ops::after,.main-card::after,.side-card::after,.step::after,.manifest-item::after,.footer-module::after{content:"";position:absolute;inset:0;border-radius:inherit;background:radial-gradient(circle at var(--mx,50%) var(--my,50%),rgba(104,240,255,.18),transparent 34%);opacity:0;transition:opacity .22s ease;pointer-events:none}.stat:hover,.feature:hover,.workflow:hover,.ops:hover,.main-card:hover,.side-card:hover,.step:hover,.manifest-item:hover,.footer-module:hover{transform:translateY(-6px);border-color:rgba(104,240,255,.22);box-shadow:0 28px 72px rgba(3,8,20,.62),0 0 0 1px rgba(104,240,255,.08)}.panel:hover,.manifest:hover{transform:translateY(-6px);border-color:rgba(104,240,255,.22);box-shadow:0 28px 72px rgba(3,8,20,.62),0 0 0 1px rgba(104,240,255,.08)}.stat:hover::after,.feature:hover::after,.workflow:hover::after,.manifest:hover::after,.ops:hover::after,.main-card:hover::after,.side-card:hover::after,.step:hover::after,.manifest-item:hover::after,.footer-module:hover::after{opacity:1}.stat{padding:18px;position:relative;overflow:hidden}.stat::before{content:"";position:absolute;inset:auto -20% -60% auto;width:120px;height:120px;border-radius:999px;background:radial-gradient(circle,rgba(104,240,255,.18),transparent 70%)}.stat strong{display:block;font:800 28px var(--display);letter-spacing:.06em;animation:numberPulse 3.4s ease-in-out infinite}.stat:nth-child(2) strong{animation-delay:.35s}.stat:nth-child(3) strong{animation-delay:.7s}.stat:nth-child(4) strong{animation-delay:1.05s}.stat span{display:block;margin-top:6px;color:var(--muted);font-size:13px}@keyframes numberPulse{0%,100%{transform:translateY(0);text-shadow:0 0 0 rgba(104,240,255,0)}50%{transform:translateY(-3px);text-shadow:0 0 16px rgba(104,240,255,.18)}}
            .panel{padding:22px;background:linear-gradient(180deg,rgba(14,26,51,.98),rgba(8,16,34,.92));position:relative;overflow:hidden}.panel::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at top right,rgba(104,240,255,.14),transparent 32%),radial-gradient(circle at bottom left,rgba(111,134,255,.16),transparent 36%);pointer-events:none}.security-panel{box-shadow:0 0 0 1px rgba(104,240,255,.1),0 0 40px rgba(104,240,255,.16),var(--shadow)}.toolbar{display:flex;justify-content:space-between;gap:12px;padding-bottom:18px;margin-bottom:18px;border-bottom:1px solid rgba(104,240,255,.1)}.toolbar strong{display:block;font-size:18px}.toolbar span{display:block;margin-top:4px;color:var(--muted);font-size:13px}.chip{padding:8px 12px;border-radius:999px;font:700 11px var(--mono);letter-spacing:.12em;color:var(--accent);background:rgba(124,255,178,.08);border:1px solid rgba(124,255,178,.22);text-transform:uppercase}.pulse,.mini{padding:18px;border-radius:22px;background:rgba(6,13,28,.78);border:1px solid rgba(104,240,255,.08)}.pulse-top{display:flex;justify-content:space-between;gap:12px}.pulse-top strong{font-size:14px;font-family:var(--mono);color:var(--muted)}.pulse-top span{font:700 11px var(--display);letter-spacing:.12em;text-transform:uppercase;color:var(--primary)}.value{margin-top:10px;font:800 40px var(--display);letter-spacing:.06em}.copy{margin-top:10px;color:var(--muted);font-size:14px;line-height:1.7}.mini-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:14px}.mini strong{display:block;font-size:14px}.mini p{margin:8px 0 0;color:var(--muted);font-size:13px;line-height:1.7}.security-orbs{position:absolute;inset:0;pointer-events:none}.security-node{position:absolute;display:grid;place-items:center;width:62px;height:62px;border-radius:999px;border:2px solid rgba(0,255,170,.88);background:rgba(2,18,28,.86);color:#00ffae;box-shadow:0 0 24px rgba(0,255,170,.25)}.security-node::after{content:"";position:absolute;inset:-10px;border-radius:999px;border:2px solid rgba(0,255,170,.22);animation:nodePulse 2.6s ease-out infinite}.security-node i{font-size:21px}.security-node small{position:absolute;right:-14px;bottom:-10px;font:700 18px var(--mono);color:rgba(104,240,255,.72)}.node-a{top:44px;right:-26px;animation:nodeFloat 4.8s ease-in-out infinite}.node-b{top:222px;left:-28px;animation:nodeFloat 5.2s ease-in-out infinite .8s}.node-c{bottom:88px;right:-18px;animation:nodeFloat 5.6s ease-in-out infinite 1.2s}.node-d{bottom:18px;left:18px;animation:nodeFloat 4.4s ease-in-out infinite .45s}@keyframes nodeFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}@keyframes nodePulse{0%{transform:scale(.92);opacity:.8}100%{transform:scale(1.24);opacity:0}}
            .section{position:relative;padding:42px 0 78px}.section::before{content:"";position:absolute;left:4%;bottom:12%;width:240px;height:240px;border-radius:999px;background:radial-gradient(circle,rgba(104,240,255,.12),transparent 72%);filter:blur(14px);pointer-events:none}.section::after{content:"";position:absolute;right:5%;top:18%;width:160px;height:160px;border-radius:999px;border:1px solid rgba(104,240,255,.05);box-shadow:0 0 0 28px rgba(104,240,255,.02),0 0 0 64px rgba(104,240,255,.015);pointer-events:none}#operations.section{padding-top:8px;margin-top:-26px}#operations .head{margin-bottom:22px}#operations .ops{margin-top:0}.head{display:flex;justify-content:space-between;align-items:end;gap:18px;margin-bottom:28px;position:relative;z-index:1}.head small{display:block;color:var(--primary);font:700 12px var(--mono);letter-spacing:.16em;text-transform:uppercase;margin-bottom:10px}.head h2{margin:0;font:800 clamp(1.8rem,4vw,3rem)/1.05 var(--display);letter-spacing:.06em;text-transform:uppercase}.head p{max-width:580px;margin:10px 0 0;color:var(--muted);line-height:1.8}
            .ops{padding:24px;overflow:hidden}.track{display:flex;transition:transform .38s ease}.slide{min-width:100%;display:grid;grid-template-columns:minmax(0,1fr) 230px;gap:18px;align-items:start}.main-card,.side-card{padding:22px;border-radius:24px;background:rgba(6,13,28,.84);border:1px solid rgba(104,240,255,.08)}.main-card{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(260px,.8fr);gap:22px;align-items:start}.main-copy{display:flex;flex-direction:column}.main-copy-bottom{display:grid;grid-template-columns:1.1fr .9fr;gap:14px;margin-top:18px;padding-top:0}.main-surface,.main-feed,.main-floor{padding:16px;border-radius:18px;background:linear-gradient(180deg,rgba(11,19,38,.82),rgba(7,14,30,.78));border:1px solid rgba(104,240,255,.08)}.main-surface strong,.main-feed strong,.main-floor strong{display:block;margin-bottom:12px;color:var(--muted);font:700 12px var(--mono);letter-spacing:.08em;text-transform:uppercase}.main-floor{grid-column:1 / -1;margin-top:14px}.main-floor-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.main-floor-item{padding:12px 14px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.05)}.main-floor-item b{display:block;color:var(--text);font-size:13px;margin-bottom:5px}.main-floor-item span{display:block;color:var(--muted);font-size:12px;line-height:1.45}.surface-lines{display:grid;gap:10px}.surface-line{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.05);font-size:13px;color:var(--muted)}.surface-line span:last-child{color:var(--text)}.feed-stack{display:grid;gap:10px}.feed-item{padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.05)}.feed-item b{display:block;color:var(--text);font-size:13px}.feed-item span{display:block;margin-top:4px;color:var(--muted);font-size:12px;line-height:1.45}.ops-preview{display:grid;gap:14px;align-content:start}.preview-panel{padding:16px;border-radius:18px;background:linear-gradient(180deg,rgba(11,19,38,.88),rgba(7,14,30,.82));border:1px solid rgba(104,240,255,.1);box-shadow:inset 0 1px 0 rgba(255,255,255,.03)}.preview-top{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px}.preview-top strong{font:700 12px var(--mono);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}.preview-chip{padding:7px 10px;border-radius:999px;background:rgba(104,240,255,.08);border:1px solid rgba(104,240,255,.14);color:var(--primary);font:700 10px var(--mono);letter-spacing:.08em;text-transform:uppercase}.preview-list{display:grid;gap:10px}.preview-row{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.06);color:var(--muted);font-size:13px}.preview-row strong{color:var(--text);font-size:13px}.preview-bar{height:6px;border-radius:999px;background:rgba(104,240,255,.08);overflow:hidden;margin-top:10px}.preview-bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--primary),var(--secondary));animation:barShineMove 3.2s linear infinite alternate}.preview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.preview-mini{padding:12px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.06)}.preview-mini strong{display:block;font:800 20px var(--display);margin-bottom:6px}.preview-mini span{display:block;color:var(--muted);font-size:12px;line-height:1.5}.tag{display:inline-flex;padding:8px 12px;border-radius:999px;color:var(--primary);border:1px solid rgba(104,240,255,.14);font:700 11px var(--mono);letter-spacing:.1em;text-transform:uppercase}.main-card h3{margin:18px 0 12px;font-size:30px;line-height:1.1}.main-card p{margin:0;color:var(--muted);line-height:1.8}.metrics{display:flex;flex-wrap:wrap;gap:14px;margin-top:22px}.metric{padding:12px 14px;border-radius:16px;background:rgba(104,240,255,.05);border:1px solid rgba(104,240,255,.1)}.metric strong{display:block;font:800 24px var(--display)}.metric span{display:block;margin-top:5px;color:var(--muted);font-size:12px}.side-card{display:flex;flex-direction:column;gap:16px}.side-card strong{display:block;font:700 13px var(--mono);color:var(--muted);margin-bottom:18px}.stack{display:grid;gap:12px}.stack div{padding:14px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.08)}.stack span{display:block;font:700 22px var(--display);margin-bottom:6px}.stack small{color:var(--muted);line-height:1.6}.side-footer{padding-top:0}.side-status{padding:16px;border-radius:18px;background:linear-gradient(180deg,rgba(11,19,38,.78),rgba(7,14,30,.76));border:1px solid rgba(104,240,255,.08)}.side-status strong{display:block;margin:0 0 12px;color:var(--muted);font:700 12px var(--mono);letter-spacing:.08em;text-transform:uppercase}.side-status-grid{display:grid;gap:10px}.side-status-item{padding:11px 12px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.05);color:var(--muted);font-size:12px;line-height:1.5}.side-status-item b{display:block;color:var(--text);font-size:13px;margin-bottom:4px}.ops-nav{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-top:18px}.bar{flex:1;height:6px;border-radius:999px;overflow:hidden;background:rgba(104,240,255,.08)}.bar span{display:block;height:100%;width:20%;border-radius:inherit;background:linear-gradient(90deg,var(--primary),var(--secondary));transition:width .38s ease;box-shadow:0 0 20px rgba(104,240,255,.36)}.controls{display:flex;gap:10px}.btn{width:46px;height:46px;border-radius:999px;border:1px solid rgba(104,240,255,.16);background:rgba(11,19,38,.72);color:var(--text);cursor:pointer;font-size:18px;transition:.18s}.btn:hover{transform:translateY(-2px);border-color:rgba(104,240,255,.34)}@keyframes barShineMove{0%{opacity:.72;transform:scaleX(.92);transform-origin:left}100%{opacity:1;transform:scaleX(1);transform-origin:left}}
            .features{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.feature{padding:22px;position:relative;overflow:hidden}.feature::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(104,240,255,.08),transparent 40%);pointer-events:none}.feature strong{display:block;font:800 22px/1.2 var(--display);margin-bottom:18px;text-transform:uppercase;letter-spacing:.04em}.feature-list{display:grid;gap:12px}.feature-item{padding:14px 16px;border-radius:18px;background:rgba(255,255,255,.03);border:1px solid rgba(104,240,255,.08);color:var(--muted);font-size:14px;line-height:1.7}
            .workflow-grid{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:18px}.workflow,.manifest{padding:26px}.workflow{background:linear-gradient(180deg,rgba(12,20,41,.96),rgba(7,14,28,.96))}.workflow h3,.manifest h3,.footer-panel h3{margin:0;font:800 28px var(--display);text-transform:uppercase;letter-spacing:.05em}.workflow-stack{display:grid;gap:14px;margin-top:22px}.step{padding:18px;border-radius:22px;background:rgba(255,255,255,.04);border:1px solid rgba(104,240,255,.08);color:var(--muted);font-size:14px;line-height:1.8}.manifest{background:rgba(239,247,255,.98);color:#07111f;border:1px solid rgba(111,134,255,.16)}.manifest p{margin:12px 0 0;color:#536276;line-height:1.8}.manifest-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:22px}.manifest-item{padding:16px;border-radius:20px;background:#fff;border:1px solid rgba(90,116,170,.14);color:#536276;line-height:1.7;box-shadow:0 18px 40px rgba(120,138,178,.12)}.manifest-item strong{display:block;color:#091321;margin-bottom:8px;font-size:14px;text-transform:uppercase;letter-spacing:.08em}
            .footer{position:relative;padding:32px 0 0;margin-top:18px}.footer::before{content:"";position:absolute;left:0;right:0;top:0;bottom:0;background:linear-gradient(180deg,rgba(4,8,18,.4),rgba(2,5,12,.92));border-top:1px solid rgba(104,240,255,.08);box-shadow:inset 0 1px 0 rgba(104,240,255,.04);pointer-events:none}.footer .shell{width:min(100% - 40px,1520px)}.footer-panel{padding:34px 34px 26px;border-radius:0;background:transparent;border:0;box-shadow:none;display:grid;gap:26px}.footer-top{display:grid;grid-template-columns:minmax(0,1.2fr) auto;gap:24px;align-items:start}.footer-panel p{margin:12px 0 0;color:var(--muted);line-height:1.8;max-width:650px}.footer-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.footer-module{padding:22px 22px 20px;border-radius:28px;background:linear-gradient(180deg,rgba(8,14,29,.88),rgba(6,11,23,.88));border:1px solid rgba(104,240,255,.08)}.footer-module strong{display:block;font:800 15px var(--display);letter-spacing:.08em;text-transform:uppercase}.footer-module p{margin:10px 0 0;color:var(--muted);font-size:14px;line-height:1.75}.footer-links{display:grid;gap:10px;margin-top:14px}.footer-links a{padding:11px 12px;border-radius:16px;background:rgba(6,13,28,.72);border:1px solid rgba(104,240,255,.08);color:var(--muted);font-size:13px;transition:.18s}.footer-links a:hover{color:var(--text);transform:translateX(4px);border-color:rgba(104,240,255,.22)}.footer-status{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}.footer-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:999px;background:rgba(104,240,255,.08);border:1px solid rgba(104,240,255,.16);color:var(--primary);font:700 11px var(--mono);letter-spacing:.08em;text-transform:uppercase}.footer-badge::before{content:"";width:8px;height:8px;border-radius:999px;background:var(--accent);box-shadow:0 0 12px rgba(124,255,178,.7)}.footer-actions{display:flex;gap:12px;flex-wrap:wrap;justify-content:flex-end}.footer-bottom{display:flex;justify-content:space-between;gap:16px;padding-top:10px;border-top:1px solid rgba(104,240,255,.1);color:var(--muted);font:700 11px var(--mono);letter-spacing:.08em;text-transform:uppercase}
            .hero-copy{max-width:650px;padding-top:0}.hero-title{max-width:780px;margin-bottom:14px}.hero-line{display:block}.hero-subhead{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px;color:var(--muted);font:700 15px var(--mono);letter-spacing:.02em}.hero-sub-separator{color:var(--primary)}.lead{max-width:640px}.hero-visual-shell{display:flex;justify-content:flex-end;padding-top:4px}.security-panel{width:min(100%,520px);padding:22px 22px 18px;background:linear-gradient(180deg,rgba(13,20,42,.94),rgba(9,16,34,.94));border-radius:30px}.monitor-shell{position:relative;border-radius:24px;background:linear-gradient(180deg,rgba(5,10,22,.96),rgba(7,14,30,.94));border:2px solid rgba(0,255,170,.72);box-shadow:0 0 0 1px rgba(104,240,255,.12),0 0 35px rgba(0,255,170,.12),inset 0 0 25px rgba(104,240,255,.08);overflow:hidden}.monitor-shell::after{content:"";position:absolute;left:0;right:0;height:120px;background:linear-gradient(180deg,transparent,rgba(104,240,255,.1),transparent);mix-blend-mode:screen;pointer-events:none;animation:monitorSweep 4.8s linear infinite}@keyframes monitorSweep{0%{top:-26%}100%{top:100%}}.monitor-head{display:flex;align-items:center;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(104,240,255,.12);font:700 13px var(--mono);letter-spacing:.14em;text-transform:uppercase}.monitor-grid{display:grid;gap:16px;padding:18px}.monitor-card{padding:18px;border-radius:18px;background:rgba(11,19,38,.76);border:1px solid rgba(104,240,255,.08);animation:monitorPulse 4.8s ease-in-out infinite}.monitor-card:nth-child(2){animation-delay:.5s}.monitor-card:nth-child(3){animation-delay:1s}.monitor-card:nth-child(4){animation-delay:1.5s}@keyframes monitorPulse{0%,100%{transform:translateY(0)}50%{transform:translateY(-2px)}}.monitor-label{display:block;color:var(--muted);font:700 14px var(--mono);margin-bottom:14px}.monitor-value{display:block;margin-bottom:14px;color:var(--accent);font:800 28px var(--display-alt);letter-spacing:.08em;animation:monitorFlicker 3.8s linear infinite}@keyframes monitorFlicker{0%,92%,100%{opacity:1}93%{opacity:.66}95%{opacity:.88}97%{opacity:.72}}.monitor-bar{height:8px;border-radius:999px;background:rgba(104,240,255,.08);overflow:hidden}.monitor-bar span{position:relative;display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#00ff88,#00d4ff);box-shadow:0 0 18px rgba(0,255,136,.28)}.monitor-bar span::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.42),transparent);animation:barShine 2.6s linear infinite}@keyframes barShine{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}.security-node{backdrop-filter:blur(12px)}.node-a{top:28px;right:-18px}.node-b{top:190px;left:-22px}.node-c{bottom:84px;right:-12px}.node-d{bottom:22px;left:52px}
            @media (max-width:1080px){.hero-grid,.workflow-grid,.footer-top,.footer-grid{grid-template-columns:1fr}.features{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.hero{min-height:auto;padding-top:12px}.hero-copy{padding-top:0}#operations.section{margin-top:-14px;padding-top:10px}}
            @media (max-width:780px){.nav,.head,.ops-nav{flex-direction:column;align-items:flex-start}.nav-links,.footer-actions{justify-content:flex-start}.hero{padding-top:14px;min-height:auto}.stats,.mini-grid,.manifest-grid,.footer-grid,.preview-grid,.main-copy-bottom,.main-floor-grid{grid-template-columns:1fr}.slide,.main-card{grid-template-columns:1fr}.actions,.actions .cta{width:100%}.actions .cta{justify-content:center}.loader-head{padding:16px 18px;font-size:12px}.loader-body{padding:22px 18px 20px;min-height:270px;font-size:.9rem}.loader-meta{flex-direction:column;align-items:flex-start}.hero-title{font-size:clamp(2.7rem,16vw,4.3rem)}.hero-subhead{font-size:13px}.hero-visual-shell{justify-content:center;width:100%;padding-top:4px}.security-panel{width:100%}.monitor-value{font-size:22px}.node-a,.node-b,.node-c,.node-d{display:none}.nav{padding:14px 16px;gap:14px}.brand{padding-right:0}.brand strong{font-size:10px}.brand span{font-size:10px}.nav-links{width:100%;padding-left:0}.pill{padding:8px 10px;font-size:11px}.footer-top,.footer-bottom{flex-direction:column;align-items:flex-start}.footer{padding-top:24px}.footer .shell{width:min(100% - 22px,1520px)}.footer-panel{padding:28px 0 24px}#operations.section{margin-top:-8px;padding-top:12px}}
            @media (max-width:520px){.shell{width:min(100% - 28px,1380px)}.nav-links{width:100%}.pill{width:100%;text-align:center}.controls{width:100%;justify-content:space-between}}
        </style>
    </head>
    <body>
        <div class="page">
            <div class="loader" id="bootLoader" aria-hidden="true">
                <div class="loader-shell">
                    <div class="loader-box">
                        <div class="loader-head">
                            <div class="loader-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <span>Security Protocol Initialization</span>
                        </div>
                        <div class="loader-body">
                            <div class="loader-line"><span class="loader-prompt">root@cybersec:~$</span> ./init_security.sh</div>
                            <div class="loader-line"><span class="loader-info">[INFO] Scanning system vulnerabilities...</span></div>
                            <div class="loader-line"><span class="loader-info">[INFO] Firewall configuration: <span class="loader-ok">ACTIVE</span></span></div>
                            <div class="loader-line"><span class="loader-info">[INFO] Encryption protocols: <span class="loader-ok">ENABLED</span></span></div>
                            <div class="loader-line"><span class="loader-info">[INFO] Intrusion detection: <span class="loader-ok">ONLINE</span></span></div>
                            <div class="loader-line"><span class="loader-info loader-ok">[SUCCESS] System secured. Access granted.</span></div>
                            <div class="loader-line"><span class="loader-prompt">root@cybersec:~$</span> <span class="loader-cursor">_</span></div>
                            <div class="loader-progress">
                                <div class="loader-track">
                                    <div class="loader-fill" id="loaderFill"></div>
                                </div>
                                <div class="loader-meta">
                                    <span>Booting LYVA Studio</span>
                                    <span class="loader-percent" id="loaderPercent">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid"></div>
            <canvas class="matrix" id="matrixCanvas"></canvas>
            <div class="particles" id="particles"></div>
            <div class="kana-rain" id="kanaRain"></div>
            <div class="code-ribbons" id="codeRibbons"></div>

            <div class="app-shell booting" id="appShell">
            <header class="header">
                <div class="shell">
                    <nav class="nav">
                        <a href="#top" class="brand">
                            <span class="mark">LY</span>
                            <span>
                                <strong>LYVA Studio</strong>
                                <span>Bot Discord + command center Laravel untuk tim Roblox</span>
                            </span>
                        </a>
                        <div class="nav-links">
                            <a href="#operations" class="pill">Operasi</a>
                            <a href="#features" class="pill">Fitur</a>
                            <a href="#workflow" class="pill">Alur kerja</a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="pill">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="pill">Masuk</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="pill primary">Mulai setup</a>
                                @endif
                            @endauth
                        </div>
                    </nav>
                </div>
            </header>

            <main id="top">
                <section class="hero">
                    <div class="shell">
                        <div class="hero-grid">
                            <div class="hero-copy">
                                <span class="eyebrow">Pusat Operasi Roblox</span>
                                <h1 class="hero-title">
                                    <span class="hero-line">Rancang</span>
                                    <span class="hero-line glow glitch" data-text="Bot Discord">Bot Discord</span>
                                    <span class="hero-line">Sekelas Produk</span>
                                </h1>
                                <div class="hero-subhead">
                                    <span>Verification</span>
                                    <span class="hero-sub-separator">|</span>
                                    <span>Otomasi Tiket</span>
                                    <span class="hero-sub-separator">|</span>
                                    <span>Dasbor Ops Roblox</span>
                                </div>
                                <p class="lead">
                                    Bot Discord internal yang terasa seperti produk sungguhan untuk tim dev Roblox:
                                    verifikasi, tiket, deploy board, sales alert, moderation, dan dashboard Laravel dalam satu tempat.
                                </p>
                                <div class="actions">
                                    <a href="{{ auth()->check() ? route('dashboard') : (Route::has('register') ? route('register') : route('login')) }}" class="cta primary">
                                        <div>
                                            <strong>Masuk ke dashboard</strong>
                                            <small>Buka panel bot dan admin tools</small>
                                        </div>
                                    </a>
                                    <a href="#features" class="cta">
                                        <div>
                                            <strong>Lihat peta fitur</strong>
                                            <small>Telusuri scope Roblox + Discord + Laravel</small>
                                        </div>
                                    </a>
                                </div>
                                <div class="stats">
                                    <div class="stat"><strong data-counter="14">0</strong><span>Flow Discord siap pakai</span></div>
                                    <div class="stat"><strong data-counter="7">0</strong><span>Panel operasional aktif</span></div>
                                    <div class="stat"><strong data-counter="24">0</strong><span>Sinyal dipantau per hari</span></div>
                                    <div class="stat"><strong data-counter="12">0</strong><span>Command inti tersinkron</span></div>
                                </div>
                            </div>

                            <div class="hero-visual-shell">
                            <aside class="panel security-panel">
                                <div class="monitor-shell">
                                    <div class="monitor-head">
                                        <div class="loader-dots">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <strong>Dasbor Operasional</strong>
                                    </div>
                                    <div class="monitor-grid">
                                        <div class="monitor-card">
                                            <span class="monitor-label">Status Firewall</span>
                                            <strong class="monitor-value">ACTIVE</strong>
                                            <div class="monitor-bar"><span class="monitor-progress" data-base="100" style="width:100%"></span></div>
                                        </div>
                                        <div class="monitor-card">
                                            <span class="monitor-label">Deteksi Ancaman</span>
                                            <strong class="monitor-value">MONITORING</strong>
                                            <div class="monitor-bar"><span class="monitor-progress" data-base="95" style="width:95%"></span></div>
                                        </div>
                                        <div class="monitor-card">
                                            <span class="monitor-label">Antrian Verifikasi</span>
                                            <strong class="monitor-value">SYNCED</strong>
                                            <div class="monitor-bar"><span class="monitor-progress" data-base="88" style="width:88%"></span></div>
                                        </div>
                                        <div class="monitor-card">
                                            <span class="monitor-label">Respons Tiket</span>
                                            <strong class="monitor-value">ONLINE</strong>
                                            <div class="monitor-bar"><span class="monitor-progress" data-base="92" style="width:92%"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="security-orbs" aria-hidden="true">
                                    <div class="security-node node-a"><i>&#128737;</i><small>1</small></div>
                                    <div class="security-node node-b"><i>&#128274;</i></div>
                                    <div class="security-node node-c"><i>&#128375;</i></div>
                                    <div class="security-node node-d"><i>&#128736;</i></div>
                                </div>
                            </aside>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section" id="operations">
                    <div class="shell">
                        <div class="head">
                            <div>
                                <small>Operations Showcase</small>
                                <h2>Realtime Product Surface</h2>
                                <p>Bagian ini saya bikin lebih hidup: ada operations slider, board metrics, dan command center feel yang lebih niat.</p>
                            </div>
                        </div>
                        <div class="ops">
                            <div class="track" id="track">
                                <article class="slide">
                                    <div class="main-card">
                                        <div class="main-copy">
                                            <span class="tag">Penjualan dan Commerce</span>
                                            <h3>Pantau revenue tanpa pindah-pindah tool</h3>
                                            <p>Feed penjualan game pass dan developer product bisa dikirim ke Discord, dicatat ke Laravel, lalu dipakai lagi untuk summary, report, dan workflow support.</p>
                                            <div class="metrics">
                                                <div class="metric"><strong>31x</strong><span>Lonjakan sales</span></div>
                                                <div class="metric"><strong>12d</strong><span>Delay alert</span></div>
                                                <div class="metric"><strong>24j</strong><span>Jendela ringkasan</span></div>
                                            </div>
                                            <div class="main-copy-bottom">
                                                <div class="main-surface">
                                                    <strong>Control surface</strong>
                                                    <div class="surface-lines">
                                                        <div class="surface-line"><span>Webhook Discord</span><span>Armed</span></div>
                                                        <div class="surface-line"><span>Rekap Laravel</span><span>Realtime</span></div>
                                                        <div class="surface-line"><span>Summary harian</span><span>Auto generated</span></div>
                                                    </div>
                                                </div>
                                                <div class="main-feed">
                                                    <strong>Queue terbaru</strong>
                                                    <div class="feed-stack">
                                                        <div class="feed-item"><b>Alert premium crate</b><span>Masuk ke channel sales-feed 12 detik lalu</span></div>
                                                        <div class="feed-item"><b>Revenue sync</b><span>Batch revenue group berhasil diproses</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ops-preview">
                                            <div class="preview-panel">
                                                <div class="preview-top">
                                                    <strong>Feed penjualan</strong>
                                                    <span class="preview-chip">Live</span>
                                                </div>
                                                <div class="preview-list">
                                                    <div class="preview-row"><strong>Premium Crate</strong><span>120 R$</span></div>
                                                    <div class="preview-row"><strong>VIP Access</strong><span>80 R$</span></div>
                                                    <div class="preview-row"><strong>Starter Bundle</strong><span>45 R$</span></div>
                                                </div>
                                                <div class="preview-bar"><span></span></div>
                                            </div>
                                            <div class="preview-grid">
                                                <div class="preview-mini"><strong>1.240</strong><span>Robux masuk 1 jam terakhir</span></div>
                                                <div class="preview-mini"><strong>18</strong><span>Alert berhasil dikirim ke Discord</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="side-card">
                                        <strong>Output stack</strong>
                                        <div class="stack">
                                            <div><span>Discord</span><small>Slash command, webhook, embed, notification lane.</small></div>
                                            <div><span>Laravel</span><small>Storage, dashboard, internal API, moderation queue.</small></div>
                                            <div><span>Roblox</span><small>Ingest sales event dan data verifikasi dari game.</small></div>
                                        </div>
                                        <div class="side-footer">
                                            <div class="side-status">
                                                <strong>Status stack</strong>
                                                <div class="side-status-grid">
                                                    <div class="side-status-item"><b>Webhook</b>Sinkron ke lane penjualan</div>
                                                    <div class="side-status-item"><b>Database</b>Storage dan summary aktif</div>
                                                    <div class="side-status-item"><b>Bridge</b>Ingest Roblox siap menerima event</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                                <article class="slide">
                                    <div class="main-card">
                                        <div class="main-copy">
                                            <span class="tag">Kontrol Komunitas</span>
                                            <h3>Verifikasi, rules, dan tiket dalam satu alur</h3>
                                            <p>User dapat panel verifikasi, rules acknowledgement, lalu tiket bantuan dengan transcript dan claim staff. Bukan sekadar command mentah.</p>
                                            <div class="metrics">
                                                <div class="metric"><strong>1 klik</strong><span>Panel verifikasi</span></div>
                                                <div class="metric"><strong>3x</strong><span>Strip avatar ack</span></div>
                                                <div class="metric"><strong>TXT</strong><span>Transkrip tiket</span></div>
                                            </div>
                                            <div class="main-copy-bottom">
                                                <div class="main-surface">
                                                    <strong>Community state</strong>
                                                    <div class="surface-lines">
                                                        <div class="surface-line"><span>Role verified</span><span>Enabled</span></div>
                                                        <div class="surface-line"><span>Ticket claim</span><span>Staff ready</span></div>
                                                        <div class="surface-line"><span>Rules panel</span><span>Interactive</span></div>
                                                    </div>
                                                </div>
                                                <div class="main-feed">
                                                    <strong>Aktivitas terbaru</strong>
                                                    <div class="feed-stack">
                                                        <div class="feed-item"><b>User verified</b><span>2 akun baru sinkron ke role server</span></div>
                                                        <div class="feed-item"><b>Ticket masuk</b><span>Bantuan pembelian dan pembayaran aktif</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ops-preview">
                                            <div class="preview-panel">
                                                <div class="preview-top">
                                                    <strong>Queue verifikasi</strong>
                                                    <span class="preview-chip">Sinkron</span>
                                                </div>
                                                <div class="preview-list">
                                                    <div class="preview-row"><strong>Role verified</strong><span>Aktif</span></div>
                                                    <div class="preview-row"><strong>Rules ack</strong><span>123 user</span></div>
                                                    <div class="preview-row"><strong>Ticket terbuka</strong><span>08 aktif</span></div>
                                                </div>
                                                <div class="preview-bar"><span></span></div>
                                            </div>
                                            <div class="preview-grid">
                                                <div class="preview-mini"><strong>98%</strong><span>User selesai baca alur verifikasi</span></div>
                                                <div class="preview-mini"><strong>6m</strong><span>Rata-rata respons ticket staff</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="side-card">
                                        <strong>Jalur fokus</strong>
                                        <div class="stack">
                                            <div><span>Role sync</span><small>Role verified dan role server diset per guild.</small></div>
                                            <div><span>Ticket panel</span><small>Bantuan pembelian, pembayaran, dan bantuan lainnya.</small></div>
                                            <div><span>Rules flow</span><small>Panel rules modern dengan acknowledgement visual.</small></div>
                                        </div>
                                        <div class="side-footer">
                                            <div class="side-status">
                                                <strong>Status komunitas</strong>
                                                <div class="side-status-grid">
                                                    <div class="side-status-item"><b>Verifikasi</b>Panel aktif dan role siap dibagikan</div>
                                                    <div class="side-status-item"><b>Rules</b>Acknowledgement visual berjalan</div>
                                                    <div class="side-status-item"><b>Ticket</b>Claim staff dan transcript aktif</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                                <article class="slide">
                                    <div class="main-card">
                                        <div class="main-copy">
                                            <span class="tag">Event dan Moderasi</span>
                                            <h3>Race panel, anti-spam, dan board pengumuman</h3>
                                            <p>Admin bisa create event, buka pendaftaran, input podium, lalu bot mengurus panel dan feedback visual. Moderation guard juga bisa jalan server-wide.</p>
                                            <div class="metrics">
                                                <div class="metric"><strong>Modal</strong><span>Alur join</span></div>
                                                <div class="metric"><strong>3 tahap</strong><span>Trigger spam</span></div>
                                                <div class="metric"><strong>Live</strong><span>Board deploy</span></div>
                                            </div>
                                            <div class="main-copy-bottom">
                                                <div class="main-surface">
                                                    <strong>Moderation lane</strong>
                                                    <div class="surface-lines">
                                                        <div class="surface-line"><span>Spam guard</span><span>Server-wide</span></div>
                                                        <div class="surface-line"><span>Race panel</span><span>Interactive</span></div>
                                                        <div class="surface-line"><span>Deploy announce</span><span>Broadcast ready</span></div>
                                                    </div>
                                                </div>
                                                <div class="main-feed">
                                                    <strong>Event feed</strong>
                                                    <div class="feed-stack">
                                                        <div class="feed-item"><b>Night Sprint</b><span>Podium tersimpan dan panel diperbarui</span></div>
                                                        <div class="feed-item"><b>Spam blocked</b><span>Recent messages dipurge dan user ditindak</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ops-preview">
                                            <div class="preview-panel">
                                                <div class="preview-top">
                                                    <strong>Panel event</strong>
                                                    <span class="preview-chip">Armed</span>
                                                </div>
                                                <div class="preview-list">
                                                    <div class="preview-row"><strong>Night Sprint</strong><span>8/8 slot</span></div>
                                                    <div class="preview-row"><strong>Anti-spam</strong><span>Server-wide</span></div>
                                                    <div class="preview-row"><strong>Deploy lane</strong><span>2 broadcast</span></div>
                                                </div>
                                                <div class="preview-bar"><span></span></div>
                                            </div>
                                            <div class="preview-grid">
                                                <div class="preview-mini"><strong>04</strong><span>Event aktif minggu ini</span></div>
                                                <div class="preview-mini"><strong>99%</strong><span>Pesan spam tertahan guard</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="side-card">
                                        <strong>Jalur kontrol</strong>
                                        <div class="stack">
                                            <div><span>Race system</span><small>Create, join, finish, podium, dan interactive panel.</small></div>
                                            <div><span>Deploy</span><small>Announcement, maintenance, hotfix, dan event broadcast.</small></div>
                                            <div><span>Spam guard</span><small>Semua channel teks, purge recent messages, dan auto-ban.</small></div>
                                        </div>
                                        <div class="side-footer">
                                            <div class="side-status">
                                                <strong>Status moderasi</strong>
                                                <div class="side-status-grid">
                                                    <div class="side-status-item"><b>Race event</b>Panel daftar dan podium aktif</div>
                                                    <div class="side-status-item"><b>Anti-spam</b>Tindak otomatis lintas channel</div>
                                                    <div class="side-status-item"><b>Broadcast</b>Announcement siap kirim ke lane deploy</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                            <div class="ops-nav">
                                <div class="bar"><span id="bar"></span></div>
                                <div class="controls">
                                    <button class="btn" id="prev" type="button" aria-label="Previous">&larr;</button>
                                    <button class="btn" id="next" type="button" aria-label="Next">&rarr;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section" id="features">
                    <div class="shell">
                        <div class="head">
                            <div>
                                <small>Feature Matrix</small>
                                <h2>Scope bot yang memang berguna</h2>
                                <p>Data di bawah tetap diambil dari controller Laravel, jadi landing page ini masih sinkron dengan fondasi produk yang sudah ada.</p>
                            </div>
                        </div>
                        <div class="features">
                            @foreach ($featureGroups as $group)
                                <article class="feature">
                                    <strong>{{ $group['title'] }}</strong>
                                    <div class="feature-list">
                                        @foreach ($group['items'] as $item)
                                            <div class="feature-item">{{ $item }}</div>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="section" id="workflow">
                    <div class="shell">
                        <div class="head">
                            <div>
                                <small>Workflow</small>
                                <h2>Backend dan bot tetap nyambung</h2>
                                <p>Desain saya bikin modern, tapi arsitektur tetap dijaga masuk akal untuk Roblox, Discord, dan Laravel.</p>
                            </div>
                        </div>
                        <div class="workflow-grid">
                            <article class="workflow">
                                <h3>Operational pipeline</h3>
                                <div class="workflow-stack">
                                    @foreach ($workflow as $step)
                                        <div class="step">{{ $step }}</div>
                                    @endforeach
                                </div>
                            </article>
                            <article class="manifest">
                                <h3>What is already live</h3>
                                <p>Yang sudah ada sekarang bukan cuma landing page. Sistem bot, panel, dan API internal sudah membentuk satu produk yang konsisten.</p>
                                <div class="manifest-grid">
                                    <div class="manifest-item"><strong>Discord bot</strong>Slash command, verify panel, rules, ticket, race, deploy, dan moderation setup.</div>
                                    <div class="manifest-item"><strong>Laravel API</strong>Internal endpoint untuk guild settings, verification, sales feed, report, dan event management.</div>
                                    <div class="manifest-item"><strong>Roblox bridge</strong>Script template dan ingest flow untuk sinkron data dari Roblox ke backend.</div>
                                    <div class="manifest-item"><strong>Dashboard</strong>Halaman admin siap dipoles lebih jauh jadi full control room.</div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="footer">
                    <div class="shell">
                        <div class="footer-panel">
                            <div class="footer-top">
                                <div>
                                    <span class="tag">LYVA Studio</span>
                                    <h3>Ready to ship a serious bot stack?</h3>
                                    <p>Landing page ini sekarang saya bikin lebih rapat, lebih konsisten, dan lebih terasa seperti command center produk internal Roblox. Dari verify panel sampai ticket desk, semuanya sudah terasa satu sistem.</p>
                                </div>
                                <div class="footer-actions">
                                    @auth
                                        <a href="{{ route('dashboard') }}" class="cta primary"><div><strong>Open dashboard</strong><small>Lanjut ke area admin sekarang</small></div></a>
                                    @else
                                        <a href="{{ route('login') }}" class="cta"><div><strong>Log in</strong><small>Masuk ke workspace</small></div></a>
                                        @if (Route::has('register'))
                                            <a href="{{ route('register') }}" class="cta primary"><div><strong>Create access</strong><small>Aktifkan dashboard Laravel</small></div></a>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                            <div class="footer-grid">
                                <article class="footer-module">
                                    <strong>Command Center</strong>
                                    <p>Verification, rules, ticket, deploy board, moderation, dan workflow Laravel sudah dipaketkan sebagai satu surface operasional.</p>
                                </article>
                                <article class="footer-module">
                                    <strong>Quick Access</strong>
                                    <div class="footer-links">
                                        <a href="#operations">Operations surface</a>
                                        <a href="#features">Feature map</a>
                                        <a href="#workflow">Workflow bot + backend</a>
                                        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">Workspace access</a>
                                    </div>
                                </article>
                                <article class="footer-module">
                                    <strong>System Status</strong>
                                    <div class="footer-status">
                                        <span class="footer-badge">Discord flows online</span>
                                        <span class="footer-badge">Laravel API ready</span>
                                        <span class="footer-badge">Roblox bridge armed</span>
                                    </div>
                                    <p>Struktur halaman ini sekarang lebih cocok untuk dipoles lagi jadi marketing site, docs hub, atau internal control room.</p>
                                </article>
                            </div>
                            <div class="footer-bottom">
                                <span>LYVA Studio</span>
                                <span>Discord + Roblox + Laravel Ops Surface</span>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
            </div>
        </div>

        <script>
            (() => {
                const loader = document.getElementById('bootLoader');
                const loaderFill = document.getElementById('loaderFill');
                const loaderPercent = document.getElementById('loaderPercent');
                const appShell = document.getElementById('appShell');

                const startLoader = () => new Promise((resolve) => {
                    if (!loader || !loaderFill || !loaderPercent || !appShell) {
                        resolve();
                        return;
                    }

                    let progress = 0;
                    const interval = window.setInterval(() => {
                        progress += Math.random() * 12 + 6;
                        if (progress >= 100) {
                            progress = 100;
                            window.clearInterval(interval);
                            window.setTimeout(() => {
                                loader.classList.add('hidden');
                                appShell.classList.remove('booting');
                                window.setTimeout(resolve, 300);
                            }, 420);
                        }

                        loaderFill.style.width = `${progress}%`;
                        loaderPercent.textContent = `${Math.floor(progress)}%`;
                    }, 90);
                });

                const track = document.getElementById('track');
                const bar = document.getElementById('bar');
                const prev = document.getElementById('prev');
                const next = document.getElementById('next');
                if (track && bar && prev && next) {
                    const slides = Array.from(track.children);
                    let index = 0;
                    const update = () => {
                        track.style.transform = `translateX(-${index * 100}%)`;
                        bar.style.width = `${((index + 1) / slides.length) * 100}%`;
                    };
                    prev.addEventListener('click', () => { index = (index - 1 + slides.length) % slides.length; update(); });
                    next.addEventListener('click', () => { index = (index + 1) % slides.length; update(); });
                    setInterval(() => { index = (index + 1) % slides.length; update(); }, 6500);
                    update();
                }

                const counterObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        const element = entry.target;
                        const target = Number(element.dataset.counter || 0);
                        const start = performance.now();
                        const duration = 1200;
                        const tick = (now) => {
                            const progress = Math.min((now - start) / duration, 1);
                            element.textContent = Math.floor(target * progress).toString();
                            if (progress < 1) requestAnimationFrame(tick);
                        };
                        requestAnimationFrame(tick);
                        counterObserver.unobserve(element);
                    });
                }, { threshold: .5 });
                document.querySelectorAll('[data-counter]').forEach((item) => counterObserver.observe(item));

                const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const monitorProgressBars = document.querySelectorAll('.monitor-progress');
                if (monitorProgressBars.length && !prefersReducedMotion) {
                    window.setInterval(() => {
                        monitorProgressBars.forEach((bar) => {
                            const base = Number(bar.dataset.base || 90);
                            const variance = base >= 98 ? 0 : 3;
                            const width = Math.max(72, Math.min(100, base + ((Math.random() * variance * 2) - variance)));
                            bar.style.width = `${width}%`;
                        });
                    }, 1700);
                }

                if (!prefersReducedMotion && window.innerWidth > 960) {
                    const tiltTargets = document.querySelectorAll('.stat, .feature, .workflow, .manifest, .ops, .main-card, .side-card, .step, .manifest-item, .footer-module, .security-panel');
                    tiltTargets.forEach((card) => {
                        const reset = () => {
                            card.style.transform = '';
                            card.style.setProperty('--mx', '50%');
                            card.style.setProperty('--my', '50%');
                        };

                        card.addEventListener('pointermove', (event) => {
                            const rect = card.getBoundingClientRect();
                            const x = event.clientX - rect.left;
                            const y = event.clientY - rect.top;
                            const rotateY = ((x / rect.width) - 0.5) * 8;
                            const rotateX = (0.5 - (y / rect.height)) * 8;
                            card.style.setProperty('--mx', `${(x / rect.width) * 100}%`);
                            card.style.setProperty('--my', `${(y / rect.height) * 100}%`);
                            card.style.transform = `perspective(1400px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
                        });

                        card.addEventListener('pointerleave', reset);
                        card.addEventListener('pointercancel', reset);
                    });
                }

                const canvas = document.getElementById('matrixCanvas');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    const chars = '01{}[]<>/BOTDCLYVAアイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンセキュリティ';
                    const fontSize = 15;
                    let drops = [];
                    const resize = () => {
                        canvas.width = window.innerWidth;
                        canvas.height = window.innerHeight;
                        drops = Array.from({ length: Math.ceil(canvas.width / fontSize) }, () => Math.random() * -40);
                    };
                    const draw = () => {
                        ctx.fillStyle = 'rgba(2,4,10,.11)';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        ctx.fillStyle = 'rgba(244,247,255,.72)';
                        ctx.font = `${fontSize}px JetBrains Mono`;
                        for (let i = 0; i < drops.length; i += 1) {
                            const text = chars[Math.floor(Math.random() * chars.length)];
                            const x = i * fontSize;
                            const y = drops[i] * fontSize;
                            ctx.fillText(text, x, y);
                            if (y > canvas.height && Math.random() > .985) drops[i] = Math.random() * -20;
                            drops[i] += .28;
                        }
                        requestAnimationFrame(draw);
                    };
                    resize();
                    draw();
                    window.addEventListener('resize', resize);
                }

                const root = document.getElementById('particles');
                if (root) {
                    const glyphs = ['0', '1', '{', '}', '[', ']', '>', '<', '/'];
                    for (let i = 0; i < 24; i += 1) {
                        const particle = document.createElement('span');
                        particle.className = 'particle';
                        particle.textContent = glyphs[Math.floor(Math.random() * glyphs.length)];
                        particle.style.left = `${Math.random() * 100}%`;
                        particle.style.animationDuration = `${8 + Math.random() * 7}s`;
                        particle.style.animationDelay = `${Math.random() * 8}s`;
                        particle.style.fontSize = `${11 + Math.random() * 8}px`;
                        root.appendChild(particle);
                    }
                }

                const kanaRain = document.getElementById('kanaRain');
                if (kanaRain) {
                    const glyphs = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンセキュリティ'.split('');
                    for (let i = 0; i < 18; i += 1) {
                        const stream = document.createElement('div');
                        stream.className = 'kana-stream';
                        stream.style.left = `${Math.random() * 100}%`;
                        stream.style.animationDuration = `${8 + Math.random() * 8}s`;
                        stream.style.animationDelay = `${Math.random() * 4}s`;
                        stream.style.opacity = `${0.18 + Math.random() * 0.34}`;

                        const length = 10 + Math.floor(Math.random() * 12);
                        for (let j = 0; j < length; j += 1) {
                            const char = document.createElement('span');
                            char.textContent = glyphs[Math.floor(Math.random() * glyphs.length)];
                            stream.appendChild(char);
                        }

                        kanaRain.appendChild(stream);
                    }
                }

                const codeRibbons = document.getElementById('codeRibbons');
                if (codeRibbons) {
                    const lines = [
                        'virus_protection::active // roblox_ops::armed // discord_signal::watching // verification_queue::synced',
                        'ticket_desk::ready // moderation_guard::server_wide // sales_feed::hot // deploy_lane::broadcast',
                        'laravel_api::stable // command_center::online // anti_spam::engaged // webhook_bridge::armed',
                    ];

                    lines.forEach((line, index) => {
                        const ribbon = document.createElement('div');
                        ribbon.className = `code-ribbon ribbon-${String.fromCharCode(97 + index)}`;

                        for (let i = 0; i < 5; i += 1) {
                            const chunk = document.createElement('span');
                            chunk.textContent = line;
                            ribbon.appendChild(chunk);
                        }

                        codeRibbons.appendChild(ribbon);
                    });
                }

                startLoader();
            })();
        </script>
    </body>
</html>
