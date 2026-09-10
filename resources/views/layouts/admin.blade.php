<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title','پنل') | ادمین شیرازلینوکس</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{--brand:#F1592D;--bg:#f4f1ec;--side:#1c1917;--card:#fff;--border:#e7e0d8;--muted:#57534e}
*{box-sizing:border-box}body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;background:var(--bg);color:#1c1917}
a{color:var(--brand);text-decoration:none}
/* Fixed admin sidebar — permanently mounted (admin-shell.js); does not reflow on nav */
html{scrollbar-gutter:stable}
.layout{min-height:100vh}
.side{
  position:fixed;top:0;bottom:0;inset-inline-start:0;z-index:50;
  width:var(--admin-side,250px);
  background:var(--side);color:#e7e5e4;padding:1.1rem .9rem 1.4rem;
  overflow-y:auto;overflow-x:hidden;
  overscroll-behavior:contain;
  border-inline-end:1px solid #292524;
  -webkit-overflow-scrolling:touch;
  /* permanent chrome: never paint-shift with main content */
  contain:layout paint style;
}
.side a{
  display:block;color:#e7e5e4;padding:.5rem .7rem;border-radius:8px;margin:.12rem 0;font-weight:600;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  transition:background .12s,color .12s;
}
.side a:hover{background:#292524;color:#fdba74}
.side a.is-active,.side a.active{
  background:#292524;color:#fdba74;
  box-shadow:inset 3px 0 0 #F1592D;
}
.side .brand{font-weight:800;color:#F1592D;margin-bottom:.85rem;font-size:1.1rem;padding:0 .35rem}
.side-group{
  margin:.9rem 0 .3rem;padding:.3rem .7rem 0;font-size:.72rem;font-weight:800;
  letter-spacing:.04em;color:#78716c;text-transform:none;
}
.side .btn.gray{width:100%}
.main{
  margin-inline-start:var(--admin-side,250px);
  padding:1.25rem 1.4rem 2rem;
  min-height:100vh;
  min-width:0;
  transition:opacity .12s ease;
}
.main.is-nav-loading{opacity:.45;pointer-events:none;user-select:none}
@media(max-width:860px){
  .side{
    position:relative;inset:auto;width:100%;height:auto;max-height:none;
    border-inline-end:0;border-bottom:1px solid #292524;
    contain:none;
  }
  .main{margin-inline-start:0}
}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1rem 1.1rem;margin-bottom:1rem}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}

/* ========== Post composer (write + settings rail) ========== */
.composer{max-width:1280px}
.composer-top{
  display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;
  gap:.85rem;margin-bottom:1rem;
}
.composer-kicker{
  margin:0 0 .2rem;font-size:.8rem;font-weight:800;color:#c2410c;
  letter-spacing:.02em;
}
.composer-title{margin:0;font-size:1.55rem;font-weight:800;color:#1c1917}
.composer-top-actions{display:flex;flex-wrap:wrap;gap:.45rem}
.composer-grid{
  display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:1.1rem;align-items:start;
}
@media(max-width:1100px){.composer-grid{grid-template-columns:1fr}}
.composer-paper{
  background:#fff;border:1px solid var(--border);border-radius:18px;
  padding:1.15rem 1.25rem 1.4rem;box-shadow:0 10px 30px rgba(40,30,20,.06);
}
.composer-headline{
  width:100%;border:0!important;border-radius:0!important;padding:.35rem 0 .55rem!important;
  font-size:clamp(1.45rem,2.5vw,1.9rem)!important;font-weight:800!important;line-height:1.35!important;
  background:transparent!important;box-shadow:none!important;color:#1c1917;
}
.composer-headline:focus{outline:none;box-shadow:none!important}
.composer-headline::placeholder{color:#d6d3d1}
.composer-slug-row{
  display:flex;align-items:center;gap:.45rem;margin:0 0 1rem;padding:.45rem .6rem;
  background:#fafaf9;border:1px solid var(--border);border-radius:12px;
}
.composer-slug-label{font-size:.78rem;font-weight:800;color:#a8a29e;flex:0 0 auto}
.composer-slug{
  border:0!important;background:transparent!important;padding:.15rem 0!important;
  font-size:.88rem!important;box-shadow:none!important;
}
.composer-sublabel{margin:.2rem 0 .35rem;font-size:.88rem;color:#78716c}
.composer-excerpt{
  min-height:72px!important;margin-bottom:1rem;border-radius:12px!important;
  background:#fafaf9!important;font-size:.95rem!important;
}

/* Settings rail (right in RTL = start side visually next to content) */
.composer-rail{
  display:flex;flex-direction:column;gap:.85rem;
  position:sticky;top:1rem;
}
@media(max-width:1100px){.composer-rail{position:static}}
.rail-card{
  background:#fff;border:1px solid var(--border);border-radius:16px;
  padding:.95rem 1rem 1.05rem;box-shadow:0 8px 22px rgba(40,30,20,.05);
}
.rail-card--publish{
  background:linear-gradient(180deg,#fff 0%,#fffaf5 100%);
  border-color:#f3e7db;
}
.rail-card-head{
  display:flex;align-items:center;justify-content:space-between;gap:.5rem;
  margin-bottom:.85rem;padding-bottom:.65rem;border-bottom:1px solid #f0ebe4;
}
.rail-card-head h2{margin:0;font-size:.98rem;font-weight:800;color:#1c1917}
.rail-status{
  font-size:.72rem;font-weight:800;border-radius:999px;padding:.18rem .55rem;
  border:1px solid var(--border);background:#fafaf9;color:#57534e;
}
.rail-status--published{background:#ecfdf5;border-color:#6ee7b7;color:#047857}
.rail-status--draft{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
.rail-status--hidden{background:#f5f5f4;border-color:#d6d3d1;color:#57534e}
.rail-field{margin:0 0 .85rem}
.rail-field > label{
  display:block;margin:0 0 .35rem;font-size:.8rem;font-weight:800;color:#78716c;
}
.rail-field > label small{font-weight:600;color:#a8a29e}
.rail-field select,.rail-field input[type=text],.rail-field input:not([type]),
.rail-field input[type=url],.rail-field input[type=search]{
  border-radius:11px;background:#fafaf9;border:1px solid var(--border);
  font-size:.9rem;padding:.5rem .7rem;
}
.rail-field select:focus,.rail-field input:focus{
  outline:none;border-color:#fdba74;background:#fff;
  box-shadow:0 0 0 3px rgba(241,89,45,.12);
}
.rail-hint{margin:.35rem 0 0;font-size:.75rem;color:#a8a29e}
.rail-seg{
  display:grid;grid-template-columns:repeat(3,1fr);gap:.3rem;
  padding:.25rem;background:#f5f5f4;border-radius:12px;border:1px solid #ebe6e0;
}
.rail-seg[data-seg="type"]{grid-template-columns:1fr 1fr}
.rail-seg-item{margin:0;cursor:pointer}
.rail-seg-item input{position:absolute;opacity:0;pointer-events:none}
.rail-seg-item span{
  display:flex;align-items:center;justify-content:center;
  padding:.45rem .25rem;border-radius:9px;font-size:.8rem;font-weight:800;
  color:#78716c;transition:background .15s,color .15s,box-shadow .15s;
}
.rail-seg-item:hover span{color:#c2410c}
.rail-seg-item input:checked + span{
  background:#fff;color:#c2410c;box-shadow:0 2px 8px rgba(40,30,20,.08);
}
.rail-toggles{display:flex;flex-direction:column;gap:.55rem;margin:.15rem 0 1rem}
.rail-toggle{
  display:flex;align-items:center;gap:.65rem;margin:0;cursor:pointer;
  padding:.55rem .6rem;border-radius:12px;border:1px solid #f0ebe4;background:#fafaf9;
  transition:border-color .15s,background .15s;
}
.rail-toggle:hover{border-color:#fed7aa;background:#fff7ed}
.rail-toggle input{position:absolute;opacity:0;pointer-events:none}
.rail-toggle-ui{
  position:relative;flex:0 0 42px;width:42px;height:24px;border-radius:999px;
  background:#d6d3d1;transition:background .15s;
}
.rail-toggle-ui::after{
  content:"";position:absolute;top:3px;inset-inline-start:3px;
  width:18px;height:18px;border-radius:999px;background:#fff;
  box-shadow:0 1px 4px rgba(0,0,0,.15);transition:inset-inline-start .15s;
}
.rail-toggle input:checked + .rail-toggle-ui{background:#F1592D}
.rail-toggle input:checked + .rail-toggle-ui::after{inset-inline-start:calc(100% - 21px)}
.rail-toggle-text{display:flex;flex-direction:column;gap:.05rem;min-width:0}
.rail-toggle-text strong{font-size:.88rem;color:#1c1917}
.rail-toggle-text small{font-size:.75rem;color:#a8a29e;font-weight:600}
.rail-save{width:100%;padding:.7rem 1rem;font-size:.95rem;border-radius:12px}
.rail-cover-prev{
  margin-top:.55rem;border-radius:12px;overflow:hidden;border:1px solid var(--border);
  background:#1c1917;max-height:140px;
}
.rail-cover-prev img{display:block;width:100%;height:140px;object-fit:cover}
/* Media upload / library */
.media-drop{
  position:relative;border:1.5px dashed #e7e0d8;border-radius:14px;
  background:#fafaf9;padding:.75rem;cursor:pointer;transition:border-color .15s,background .15s;
  margin-bottom:.55rem;overflow:hidden;
}
.media-drop:hover,.media-drop.is-drag{
  border-color:#F1592D;background:#fff7ed;
}
.media-drop.is-uploading{opacity:.7;pointer-events:none}
.media-drop-inner{
  display:flex;flex-direction:column;align-items:center;gap:.2rem;
  text-align:center;padding:.55rem .25rem;color:#78716c;
}
.media-drop-inner strong{color:#1c1917;font-size:.9rem}
.media-drop-inner span{font-size:.82rem}
.media-drop-inner small{font-size:.72rem;color:#a8a29e}
.media-drop .rail-cover-prev{margin:0;border:0;border-radius:10px}
.media-path-input{font-size:.8rem!important;margin-top:.15rem}
.media-lib-open{white-space:nowrap}
.be-image-actions{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.35rem}
.be-image-btn{padding:.35rem .65rem!important;font-size:.8rem!important;cursor:pointer;margin:0}
.be-image-btn.is-busy{opacity:.6;pointer-events:none}
.media-modal[hidden]{display:none!important}
.media-modal{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:1rem}
.media-modal-backdrop{position:absolute;inset:0;background:rgba(28,25,23,.55);backdrop-filter:blur(2px)}
.media-modal-panel{
  position:relative;z-index:1;width:min(720px,100%);max-height:min(84vh,720px);
  background:#fff;border-radius:18px;border:1px solid var(--border);
  box-shadow:0 24px 60px rgba(0,0,0,.25);display:flex;flex-direction:column;overflow:hidden;
}
.media-modal-head{
  display:flex;align-items:center;justify-content:space-between;gap:.5rem;
  padding:.9rem 1.1rem;border-bottom:1px solid var(--border);
}
.media-modal-head h2{margin:0;font-size:1.05rem}
.media-modal-toolbar{
  display:flex;flex-wrap:wrap;align-items:center;gap:.55rem;
  padding:.7rem 1.1rem;border-bottom:1px solid #f0ebe4;background:#fafaf9;
}
.media-upload-btn{cursor:pointer;margin:0}
.media-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.65rem;
  padding:1rem 1.1rem 1.2rem;overflow:auto;flex:1;
}
.media-grid-item{
  border:1px solid var(--border);border-radius:12px;overflow:hidden;padding:0;
  background:#f5f5f4;cursor:pointer;aspect-ratio:1;transition:border-color .15s,transform .12s;
}
.media-grid-item:hover{border-color:#F1592D;transform:translateY(-2px)}
.media-grid-item img{width:100%;height:100%;object-fit:cover;display:block}
.media-admin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.85rem}
.media-admin-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:.65rem;display:flex;flex-direction:column;gap:.45rem}
.media-admin-thumb{display:block;aspect-ratio:1;border-radius:10px;overflow:hidden;background:#f5f5f4}
.media-admin-thumb img{width:100%;height:100%;object-fit:cover}
.media-admin-path{font-size:.68rem;color:#78716c;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.media-admin-actions{display:flex;gap:.35rem;flex-wrap:wrap}
.menu-builder{display:flex;flex-direction:column;gap:.75rem}
.menu-item-card{border:1px solid var(--border);border-radius:14px;padding:.85rem 1rem;background:#fafaf9}
.menu-item-head{display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.55rem}
.menu-item-tools{display:flex;gap:.3rem}
.menu-children{margin-top:.5rem;padding-top:.5rem;border-top:1px dashed var(--border)}
.menu-child-row{display:grid;grid-template-columns:1fr 1fr auto;gap:.4rem;margin-bottom:.4rem}
.rev-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.45rem}
.rev-list li{display:flex;flex-direction:column;gap:.1rem;font-size:.82rem;padding:.4rem .5rem;border-radius:10px;background:#fafaf9;border:1px solid #f0ebe4}
.rev-list strong{font-size:.84rem}
.rail-meta-desc{min-height:72px!important;font-size:.88rem!important}
.rail-card--tags{padding:0;overflow:visible}
.tag-picker--rail{
  margin:0;border:0;box-shadow:none;border-radius:16px;padding:.95rem 1rem 1rem;
}
.tag-picker--rail .tag-picker-list{max-height:320px}
.rail-danger-link{
  display:block;width:100%;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;
  border-radius:12px;padding:.65rem;font:inherit;font-weight:800;cursor:pointer;
}
.rail-danger-link:hover{background:#fee2e2}

/* Settings page */
.settings-page{max-width:920px}
.settings-hero{margin-bottom:1rem}
.settings-lead{margin:.45rem 0 0;color:#78716c;max-width:40rem;line-height:1.7}
.settings-tabs{
  display:flex;flex-wrap:wrap;gap:.4rem;margin:0 0 1rem;
  padding:.35rem;background:#fff;border:1px solid var(--border);border-radius:14px;
  box-shadow:0 4px 14px rgba(40,30,20,.04);
}
.settings-tab{
  padding:.5rem .9rem;border-radius:10px;font-weight:800;font-size:.9rem;color:#57534e;
}
.settings-tab:hover{background:#fff7ed;color:#c2410c}
.settings-tab.is-active{background:#F1592D;color:#fff!important}
.settings-card{
  background:#fff;border:1px solid var(--border);border-radius:18px;
  padding:1.2rem 1.25rem 1.3rem;box-shadow:0 10px 28px rgba(40,30,20,.06);
}
.settings-section-title{margin:0 0 .85rem;font-size:1.15rem;color:#1c1917}
.settings-subtitle{margin:1.25rem 0 .65rem;font-size:.98rem;color:#c2410c}
.settings-help{margin:-.35rem 0 1rem;font-size:.88rem;color:#78716c;line-height:1.7}
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem 1rem}
@media(max-width:700px){.settings-grid{grid-template-columns:1fr}}
.settings-field--full{grid-column:1/-1}
.settings-field label{margin:0 0 .35rem;font-size:.86rem;color:#57534e}
.settings-field small{display:block;margin-top:.3rem;color:#a8a29e;font-size:.78rem}
.settings-field input,.settings-field select,.settings-field textarea{
  background:#fafaf9;border-radius:11px;
}
.settings-toggles{display:flex;flex-direction:column;gap:.55rem}
.settings-toggle{margin:0}
.settings-actions{
  display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1.25rem;padding-top:1rem;
  border-top:1px solid #f0ebe4;
}
/* Homepage / slider admin */
.slides-list{display:flex;flex-direction:column;gap:.85rem;margin-top:.5rem}
.slide-row{
  border:1px solid var(--border);border-radius:16px;padding:.9rem 1rem;
  background:linear-gradient(180deg,#fff,#fafaf9);
}
.slide-row-head{
  display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.75rem;
}
.slide-row-actions{display:flex;align-items:center;gap:.55rem}
.slide-enabled{display:inline-flex;align-items:center;gap:.35rem;font-size:.85rem;font-weight:700;margin:0;cursor:pointer}
.slide-enabled input{width:auto}
.slide-row-grid{display:grid;grid-template-columns:180px 1fr;gap:1rem}
@media(max-width:700px){.slide-row-grid{grid-template-columns:1fr}}
.slide-row-media{display:flex;flex-direction:column;gap:.45rem}
.slide-prev{
  border-radius:12px;overflow:hidden;border:1px solid var(--border);
  background:#1c1917;aspect-ratio:16/10;
}
.slide-prev img{width:100%;height:100%;object-fit:cover;display:block}
.slide-row-fields label{margin:.45rem 0 .25rem;font-size:.82rem;color:#78716c}
.slide-row-fields label:first-child{margin-top:0}

/* Block type select looks nicer inside editor */
.be-type{
  appearance:none;-webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23a8a29e' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:left .55rem center;padding-inline-end:.55rem!important;padding-inline-start:1.4rem!important;
  border-color:#e7e0d8!important;font-weight:800!important;color:#44403c!important;
}
.be-type:hover{border-color:#fdba74!important;background-color:#fff7ed!important}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem}
.stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:.9rem}
.stat b{display:block;font-size:1.4rem;color:var(--brand)}
table{width:100%;border-collapse:collapse}th,td{padding:.55rem .4rem;border-bottom:1px solid var(--border);text-align:right;font-size:.92rem}
.btn{display:inline-block;background:var(--brand);color:#fff!important;border:0;border-radius:10px;padding:.45rem .85rem;font-weight:700;cursor:pointer}
.btn.gray{background:#44403c}.btn.red{background:#b91c1c}.btn.light{background:#fff7ed;color:#c2410c!important;border:1px solid #fed7aa}
label{display:block;font-weight:600;margin:.55rem 0 .25rem}
input,select,textarea{width:100%;padding:.55rem .7rem;border:1px solid var(--border);border-radius:10px;font:inherit;background:#fff}
textarea{min-height:120px;font:inherit;font-size:.95rem;line-height:1.7}
textarea.mono{min-height:280px;font-family:ui-monospace,monospace;font-size:.88rem}
/* Block editor (Virgool / Publii style) */
.editor-wrap{margin:.35rem 0 1rem;position:relative}
.editor-label-row{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:.5rem;margin:.55rem 0 .25rem}
.editor-label-row label{margin:0}
.editor-hint{font-size:.82rem;color:var(--muted);font-weight:500}
.be-shell{
  border:1px solid var(--border);border-radius:16px;background:#fff;
  box-shadow:0 8px 28px rgba(40,30,20,.06);overflow:hidden;
}
.be-tip{
  padding:.75rem 1rem;background:linear-gradient(90deg,#fff7ed,#fff);
  border-bottom:1px solid var(--border);font-size:.88rem;color:#57534e;line-height:1.7;
}
.be-tip b{color:#c2410c}
.be-tip-keys{display:inline-block;margin-inline-start:.35rem;font-size:.8rem;color:#a8a29e}
.be-palette{
  display:flex;flex-wrap:wrap;gap:.4rem;padding:.7rem .85rem;
  border-bottom:1px solid var(--border);background:#fafaf9;
}
.be-chip{
  display:inline-flex;align-items:center;gap:.3rem;
  border:1px solid var(--border);background:#fff;border-radius:999px;
  padding:.28rem .65rem;font:inherit;font-size:.82rem;font-weight:700;
  color:#44403c;cursor:pointer;transition:border-color .15s,background .15s,color .15s;
}
.be-chip:hover{border-color:#fed7aa;background:#fff7ed;color:#c2410c}
.be-chip-ico{opacity:.7;font-size:.78rem;min-width:1.1rem;text-align:center}
.be-canvas{padding:.6rem .55rem 1.2rem;min-height:320px;background:#fff}
.be-block{
  display:grid;grid-template-columns:42px 1fr;gap:.35rem;
  margin:.45rem 0;padding:.35rem;border-radius:14px;
  border:1px solid transparent;transition:border-color .15s,background .15s,box-shadow .15s;
}
.be-block:hover,.be-block:focus-within{
  border-color:var(--border);background:#fffcfa;
  box-shadow:0 4px 16px rgba(40,30,20,.04);
}
.be-block.is-dragging{opacity:.45}
.be-block.is-drop{border-color:#F1592D;background:#fff7ed}
.be-side{display:flex;flex-direction:column;align-items:center;gap:.35rem;padding-top:.65rem}
.be-add,.be-handle{
  display:inline-flex;align-items:center;justify-content:center;
  width:32px;height:32px;padding:0;border-radius:10px;
  border:1px solid #ebe6e0;background:#fff;
  color:#a8a29e;cursor:pointer;line-height:0;
  box-shadow:0 1px 2px rgba(40,30,20,.04);
  transition:background .15s,border-color .15s,color .15s,box-shadow .15s,transform .12s;
}
.be-ico{display:block;fill:currentColor}
.be-add .be-ico{fill:none}
.be-add:hover,.be-handle:hover{
  background:#fff7ed;border-color:#fdba74;color:#c2410c;
  box-shadow:0 4px 12px rgba(241,89,45,.12);
}
.be-add:active{transform:scale(.96)}
.be-handle{cursor:grab}
.be-handle:active{cursor:grabbing;transform:scale(.96);background:#ffedd5;color:#9a3412}
.be-block.is-dragging .be-handle{
  background:#F1592D;border-color:#F1592D;color:#fff;box-shadow:0 6px 14px rgba(241,89,45,.28);
}
.be-main{min-width:0}
.be-bar{
  display:flex;align-items:center;justify-content:space-between;gap:.5rem;
  margin-bottom:.35rem;opacity:.0;transition:opacity .15s;
}
.be-block:hover .be-bar,.be-block:focus-within .be-bar{opacity:1}
.be-type{
  width:auto!important;max-width:11rem;padding:.28rem .5rem!important;
  border-radius:8px!important;font-size:.8rem!important;font-weight:700;
  border:1px solid var(--border)!important;background:#fff;
}
.be-tools{display:inline-flex;gap:.2rem}
.be-tool{
  width:28px;height:28px;border-radius:8px;border:1px solid var(--border);
  background:#fff;cursor:pointer;font:inherit;font-weight:700;color:#57534e;
}
.be-tool:hover{background:#f5f5f4}
.be-tool-danger:hover{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.be-body{min-width:0}
.be-ce{
  min-height:2.4rem;padding:.55rem .7rem;border-radius:12px;
  border:1px solid #f0ebe4;background:#fff;outline:none;
  font-size:1.02rem;line-height:1.9;direction:rtl;text-align:right;
}
.be-ce:focus{border-color:#fdba74;box-shadow:0 0 0 3px rgba(241,89,45,.12)}
.be-ce:empty:before{
  content:attr(data-placeholder);color:#a8a29e;pointer-events:none;
}
.be-ce--h2{font-size:1.45rem;font-weight:800;color:#c2410c;line-height:1.45}
.be-ce--h3{font-size:1.22rem;font-weight:800;color:#c2410c;line-height:1.45}
.be-ce--h4{font-size:1.08rem;font-weight:800;color:#9a3412;line-height:1.45}
.be-ce--quote{
  border-right:4px solid #F1592D;border-left:0;background:#fff7ed;
  color:#44403c;font-size:1.05rem;
}
.be-list,.be-code{
  width:100%;min-height:5.5rem;padding:.65rem .75rem;border-radius:12px;
  border:1px solid #f0ebe4;font:inherit;line-height:1.75;resize:vertical;background:#fff;
}
.be-code{
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.88rem;
  direction:ltr;text-align:left;background:#1c1917;color:#f5f5f4;border-color:#292524;
  min-height:7rem;
}
.be-list:focus,.be-code:focus,.be-input:focus{
  outline:none;border-color:#fdba74;box-shadow:0 0 0 3px rgba(241,89,45,.12);
}
.be-image{display:flex;flex-direction:column;gap:.45rem}
.be-input{
  width:100%;padding:.55rem .7rem;border:1px solid #f0ebe4;border-radius:10px;font:inherit;background:#fff;
}
.be-image-prev{
  border-radius:12px;overflow:hidden;border:1px solid var(--border);background:#f5f5f4;max-height:280px;
}
.be-image-prev img{display:block;width:100%;height:auto;max-height:280px;object-fit:contain;background:#0c0a09}
.be-toc-preview{
  border:1px dashed #fdba74;background:#fff7ed;border-radius:12px;padding:.85rem 1rem;color:#9a3412;
}
.be-toc-preview strong{display:block;margin-bottom:.25rem}
.be-toc-preview p{margin:0;font-size:.88rem;line-height:1.6;color:#78716c}
.tag-picker-selected{display:flex;flex-wrap:wrap;gap:.35rem;margin:0 0 .55rem}
.tag-picker-selected[hidden]{display:none!important}
.be-hr-preview{
  display:flex;align-items:center;gap:.75rem;padding:.8rem;color:#a8a29e;font-size:.85rem;font-weight:700;
}
.be-hr-preview hr{flex:1;border:0;border-top:2px dashed #d6d3d1;margin:0}
@media(max-width:700px){
  .be-block{grid-template-columns:34px 1fr}
  .be-bar{opacity:1}
  .be-chip{font-size:.78rem;padding:.22rem .5rem}
}
.row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}@media(max-width:700px){.row{grid-template-columns:1fr}}
.ok{background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46;padding:.6rem .8rem;border-radius:10px;margin-bottom:.8rem}
.err{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:.6rem .8rem;border-radius:10px;margin-bottom:.8rem}
.checks label{display:inline-flex;align-items:center;gap:.35rem;font-weight:500;margin-inline-end:1rem}
/* Tag picker (post form) */
.tag-picker{
  margin:.55rem 0 1rem;padding:1rem;
  border:1px solid var(--border);border-radius:16px;background:#fff;
  box-shadow:0 6px 20px rgba(40,30,20,.04);
}
.tag-picker-head{
  display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.55rem;
}
.tag-picker-head label{margin:0;font-size:1rem}
.tag-picker-count{
  font-size:.82rem;font-weight:700;color:#c2410c;
  background:#fff7ed;border:1px solid #fed7aa;border-radius:999px;padding:.15rem .55rem;
}
.tag-picker-filter{
  width:100%;margin-bottom:.75rem;padding:.55rem .8rem;
  border:1px solid var(--border);border-radius:12px;font:inherit;background:#fafaf9;
}
.tag-picker-filter:focus{
  outline:none;border-color:#fdba74;background:#fff;
  box-shadow:0 0 0 3px rgba(241,89,45,.12);
}
.tag-picker-list{
  display:flex;flex-wrap:wrap;gap:.45rem;
  max-height:320px;overflow:auto;padding:.15rem .1rem .25rem;
}
.tag-chip{
  position:relative;
  display:inline-flex;align-items:center;gap:.4rem;
  margin:0;padding:.38rem .75rem .38rem .55rem;
  border:1px solid var(--border);border-radius:999px;
  background:#fafaf9;color:#44403c;font-weight:600;font-size:.88rem;
  cursor:pointer;user-select:none;
  transition:background .15s,border-color .15s,color .15s,box-shadow .15s,transform .12s;
}
.tag-chip input{
  position:absolute;opacity:0;pointer-events:none;width:0;height:0;
}
.tag-chip-dot{
  width:.7rem;height:.7rem;border-radius:999px;
  border:2px solid #d6d3d1;background:#fff;flex:0 0 auto;
  transition:background .15s,border-color .15s,box-shadow .15s;
}
.tag-chip-text{line-height:1.2}
.tag-chip:hover{
  border-color:#fdba74;background:#fff7ed;color:#c2410c;
}
.tag-chip.is-on{
  border-color:#F1592D;background:linear-gradient(180deg,#fff7ed,#ffedd5);
  color:#9a3412;box-shadow:0 2px 10px rgba(241,89,45,.12);
}
.tag-chip.is-on .tag-chip-dot{
  border-color:#F1592D;background:#F1592D;
  box-shadow:inset 0 0 0 2px #fff;
}
.tag-chip.is-hidden{display:none}
.tag-picker-empty{margin:.35rem 0 0;font-size:.9rem;color:var(--muted)}
/* Tags index cards */
.tag-admin-grid{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.85rem;
}
.tag-admin-card{
  background:#fff;border:1px solid var(--border);border-radius:16px;padding:1rem 1.05rem;
  box-shadow:0 6px 18px rgba(40,30,20,.05);
  display:flex;flex-direction:column;gap:.55rem;
  transition:border-color .15s,box-shadow .15s,transform .15s;
}
.tag-admin-card:hover{
  border-color:#fed7aa;box-shadow:0 10px 24px rgba(241,89,45,.1);transform:translateY(-2px);
}
.tag-admin-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem}
.tag-admin-name{margin:0;font-size:1.05rem;font-weight:800;color:#1c1917;line-height:1.35}
.tag-admin-count{
  flex:0 0 auto;font-size:.75rem;font-weight:800;color:#c2410c;
  background:#fff7ed;border:1px solid #fed7aa;border-radius:999px;padding:.15rem .5rem;
}
.tag-admin-slug{
  display:block;font-size:.8rem;color:#78716c;background:#fafaf9;
  border:1px solid var(--border);border-radius:8px;padding:.35rem .55rem;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
}
.tag-admin-actions{display:flex;gap:.4rem;margin-top:auto;padding-top:.25rem}
.tag-admin-actions .btn{padding:.35rem .7rem;font-size:.84rem}
/* legacy */
.tagbox{max-height:180px;overflow:auto;border:1px solid var(--border);border-radius:10px;padding:.5rem;background:#fff}
/* Jalali date picker */
.jdp-field{position:relative;display:flex;gap:.4rem;align-items:stretch}
.jdp-field .jdp-input{flex:1;font-variant-numeric:tabular-nums}
.jdp-btn{
  flex:0 0 44px;border:1px solid var(--border);border-radius:10px;background:#fff7ed;
  cursor:pointer;font-size:1.1rem;line-height:1;
}
.jdp-btn:hover{background:#ffedd5;border-color:#fed7aa}
.jdp-hint{margin:.4rem 0 0;font-size:.82rem;color:var(--muted)}
.jdp-hint b{color:#1c1917}
.jdp-pop{
  position:absolute;z-index:50;inset-inline-start:0;top:calc(100% + 6px);
  width:min(100%,320px);background:#fff;border:1px solid var(--border);
  border-radius:14px;box-shadow:0 14px 36px rgba(40,30,20,.14);padding:.7rem;
}
.jdp-head{display:flex;align-items:center;justify-content:space-between;gap:.4rem;margin-bottom:.55rem}
.jdp-title{font-weight:800;color:#c2410c;font-size:.98rem}
.jdp-nav{
  width:34px;height:34px;border:1px solid var(--border);border-radius:10px;background:#fafaf9;
  cursor:pointer;font-size:1.15rem;line-height:1;color:#44403c;
}
.jdp-nav:hover{background:#fff7ed;border-color:#fed7aa}
.jdp-week{display:grid;grid-template-columns:repeat(7,1fr);gap:.15rem;margin-bottom:.25rem;text-align:center}
.jdp-week span{font-size:.75rem;font-weight:700;color:#a8a29e;padding:.2rem 0}
.jdp-days{display:grid;grid-template-columns:repeat(7,1fr);gap:.2rem}
.jdp-day,.jdp-empty{
  aspect-ratio:1;border:0;border-radius:10px;background:transparent;
  font:inherit;font-weight:600;cursor:pointer;color:#1c1917;
}
.jdp-day:hover{background:#fff7ed;color:#c2410c}
.jdp-day.is-today{box-shadow:inset 0 0 0 1.5px #F1592D}
.jdp-day.is-selected{background:#F1592D;color:#fff}
.jdp-empty{cursor:default;pointer-events:none}
.jdp-time{
  display:flex;flex-wrap:wrap;align-items:center;gap:.4rem;margin-top:.65rem;
  padding-top:.55rem;border-top:1px solid var(--border);font-size:.85rem;
}
.jdp-time label{display:inline-flex;align-items:center;gap:.25rem;margin:0;font-weight:600;font-size:.82rem}
.jdp-time input[type=number]{
  width:3.2rem;padding:.3rem .35rem;border:1px solid var(--border);border-radius:8px;font:inherit
}
.jdp-today,.jdp-clear{
  margin-inline-start:auto;border:1px solid var(--border);background:#fafaf9;
  border-radius:8px;padding:.3rem .55rem;font:inherit;font-weight:700;cursor:pointer;font-size:.8rem
}
.jdp-clear{margin-inline-start:0;color:#b91c1c}
.jdp-today:hover{background:#fff7ed}
/* Umami stats */
.umami-bars{
  display:flex;align-items:flex-end;gap:4px;min-height:150px;
  overflow-x:auto;padding:.5rem .15rem .25rem;
}
.umami-bar-col{
  flex:1 1 0;min-width:22px;max-width:48px;
  display:flex;flex-direction:column;align-items:center;gap:3px;
}
.umami-bar{
  width:100%;background:linear-gradient(180deg,#F1592D,#ea580c);
  border-radius:6px 6px 2px 2px;min-height:4px;
}
.umami-bar-y{font-size:.68rem;color:#78716c;font-variant-numeric:tabular-nums}
.umami-bar-x{font-size:.62rem;color:#a8a29e;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.umami-metric{width:100%;border-collapse:collapse}
.umami-metric td{padding:.4rem .25rem;border-bottom:1px solid var(--border);font-size:.88rem;vertical-align:middle}
.umami-metric-name{max-width:12rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;direction:ltr;text-align:left}
.umami-metric-bar-cell{width:45%}
.umami-metric-bar{height:8px;background:linear-gradient(90deg,#fdba74,#F1592D);border-radius:99px;min-width:6px}
.umami-metric-val{width:3.5rem;text-align:left;font-weight:700;font-variant-numeric:tabular-nums;color:#c2410c}
</style>
</head>
<body>
@php
  $postType = null;
  if (request()->routeIs('admin.posts.*')) {
      $postType = request('type');
      $routePost = request()->route('post');
      if (!$postType && $routePost) {
          $postType = $routePost->type ?? 'post';
      }
      $postType = $postType ?: 'post';
  }
  $isPostCreate = request()->routeIs('admin.posts.create') && $postType !== 'page';
  $isPostsList = request()->routeIs('admin.posts.*') && $postType !== 'page' && !$isPostCreate;
  $isPages = request()->routeIs('admin.posts.*') && $postType === 'page';
  $settingsTab = request()->routeIs('admin.settings.*') ? request('tab', 'general') : null;
  $navActive = function (string $pattern) {
      return request()->routeIs($pattern) ? 'is-active' : '';
  };
@endphp
<div class="layout">
<aside class="side" id="admin-side" data-turbo-permanent>
  <div class="brand">پنل شیرازلینوکس</div>
  <div class="side-group">محتوا</div>
  <a class="{{ $navActive('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">داشبورد</a>
  <a class="{{ $isPostsList ? 'is-active' : '' }}" href="{{ route('admin.posts.index',['type'=>'post']) }}">مطالب</a>
  <a class="{{ $isPages ? 'is-active' : '' }}" href="{{ route('admin.posts.index',['type'=>'page']) }}">صفحات</a>
  <a class="{{ $isPostCreate ? 'is-active' : '' }}" href="{{ route('admin.posts.create',['type'=>'post']) }}">+ مطلب جدید</a>
  <a class="{{ $navActive('admin.tags.*') }}" href="{{ route('admin.tags.index') }}">تگ‌ها</a>
  <a class="{{ $navActive('admin.authors.*') }}" href="{{ route('admin.authors.index') }}">نویسندگان</a>
  <a class="{{ $navActive('admin.comments.*') }}" href="{{ route('admin.comments.index') }}">نظرات</a>
  <div class="side-group">سایت</div>
  <a class="{{ $navActive('admin.home.*') }}" href="{{ route('admin.home.edit') }}">صفحه اصلی و اسلایدر</a>
  <a class="{{ $navActive('admin.menu.*') }}" href="{{ route('admin.menu.edit') }}">منوی سایت</a>
  <a class="{{ $navActive('admin.media.*') }}" href="{{ route('admin.media.index') }}">کتابخانه رسانه</a>
  <a class="{{ $navActive('admin.redirects.*') }}" href="{{ route('admin.redirects.index') }}">ریدایرکت‌ها</a>
  <a class="{{ $settingsTab === 'general' ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">تنظیمات سایت</a>
  <a class="{{ $settingsTab === 'seo' ? 'is-active' : '' }}" href="{{ route('admin.settings.edit',['tab'=>'seo']) }}">سئو</a>
  <a class="{{ $settingsTab === 'display' ? 'is-active' : '' }}" href="{{ route('admin.settings.edit',['tab'=>'display']) }}">حالت نمایشی</a>
  <a class="{{ $navActive('admin.stats') }}" href="{{ route('admin.stats') }}">آمار بازدید</a>
  <div class="side-group">سیستم</div>
  <a class="{{ $navActive('admin.users.*') }}" href="{{ route('admin.users.index') }}">کاربران</a>
  <a class="{{ $navActive('admin.activity.*') }}" href="{{ route('admin.activity.index') }}">لاگ فعالیت</a>
  <a class="{{ $navActive('admin.backup.*') }}" href="{{ route('admin.backup.index') }}">پشتیبان</a>
  <div class="side-group">پیوندها</div>
  <a href="{{ route('home') }}" target="_blank" rel="noopener">مشاهده سایت</a>
  <a href="{{ route('sitemap') }}" target="_blank" rel="noopener">sitemap</a>
  <a href="{{ route('feed') }}" target="_blank" rel="noopener">RSS</a>
  <form method="post" action="{{ route('admin.logout') }}" style="margin-top:1rem" data-full-page="1">@csrf
    <button class="btn gray" type="submit">خروج</button>
  </form>
</aside>
<div class="main">
  @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="err"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</div>
</div>
<script src="{{ asset('js/admin-jalali.js') }}?v=20260818f" defer></script>
<script src="{{ asset('js/admin-shell.js') }}?v=20260818a" defer></script>
@stack('scripts')
</body>
</html>
