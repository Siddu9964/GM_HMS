<?php
$pageTitle = 'Lab Orders';
$pageIcon  = 'fa-flask';
$navTitle  = 'Lab Orders';
$navSub    = 'View, create and manage laboratory test orders';
require_once 'includes/lab_head.php';
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<style>
/* ══════════════════════════════════════════════════════════════════
   LIMS PREMIUM DESIGN SYSTEM  ·  Brand Colors Only
   Primary  : #1F6B4A  (Deep Emerald Green)
   Secondary: #F3EFE6  (Soft Warm Cream)
══════════════════════════════════════════════════════════════════ */
:root {
  --p:         #1F6B4A;
  --p-dk:      #154c34;
  --p-lt:      #2a8f62;
  --p-05:      rgba(31,107,74,.05);
  --p-10:      rgba(31,107,74,.10);
  --p-15:      rgba(31,107,74,.15);
  --p-20:      rgba(31,107,74,.20);
  --p-30:      rgba(31,107,74,.30);
  --s:         #F3EFE6;
  --s-dk:      #e8e0d0;
  --s-md:      #ddd6c8;
  --txt:       #0f2419;
  --txt-sub:   #2d5240;
  --txt-mut:   #6b8f7a;
  --bdr:       rgba(31,107,74,.12);
  --bdr-str:   rgba(31,107,74,.24);
  --sh-sm:     0 1px 4px rgba(31,107,74,.08), 0 1px 2px rgba(31,107,74,.04);
  --sh-md:     0 4px 16px rgba(31,107,74,.12), 0 2px 6px rgba(31,107,74,.06);
  --sh-lg:     0 16px 40px rgba(31,107,74,.16), 0 6px 12px rgba(31,107,74,.08);
  --sh-xl:     0 28px 60px rgba(31,107,74,.22), 0 10px 20px rgba(31,107,74,.10);
  --r-sm: 8px; --r-md: 12px; --r-lg: 16px; --r-xl: 20px; --r-2xl: 24px;
  --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  --ease: cubic-bezier(.4,0,.2,1);
  --spring: cubic-bezier(.34,1.56,.64,1);
}

/* ── Scrollbar ─────────────────────────────────────────────── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--s)}
::-webkit-scrollbar-thumb{background:var(--p-30);border-radius:10px}
::-webkit-scrollbar-thumb:hover{background:var(--p)}

/* ── Page Wrapper ──────────────────────────────────────────── */
.lo-page{padding:24px;font-family:var(--font);background:var(--s);min-height:100%}

/* ── Stats Grid ────────────────────────────────────────────── */
.lo-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.lo-stat{
  background:white;border-radius:var(--r-lg);padding:20px 22px;
  border:1px solid var(--bdr);box-shadow:var(--sh-sm);
  position:relative;overflow:hidden;transition:all .25s var(--ease);cursor:default;
}
.lo-stat:hover{transform:translateY(-3px);box-shadow:var(--sh-md);border-color:var(--bdr-str)}
.lo-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--p),var(--p-lt))}
.lo-stat-ico{width:42px;height:42px;background:var(--p-10);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;color:var(--p);font-size:1.1rem;margin-bottom:14px}
.lo-stat-val{font-size:2rem;font-weight:900;color:var(--txt);line-height:1;margin-bottom:4px;letter-spacing:-1px}
.lo-stat-lbl{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--txt-mut)}
.lo-stat-bg{position:absolute;right:-10px;bottom:-10px;font-size:3.5rem;opacity:.04;color:var(--p);pointer-events:none}

/* ── Page Header ───────────────────────────────────────────── */
.lo-header{
  background:linear-gradient(135deg,var(--p) 0%,var(--p-dk) 100%);
  border-radius:var(--r-xl);padding:26px 32px;margin-bottom:24px;
  display:flex;justify-content:space-between;align-items:center;
  box-shadow:var(--sh-lg);position:relative;overflow:hidden;
}
.lo-header::before{content:'';position:absolute;top:-70px;right:-70px;width:220px;height:220px;background:rgba(243,239,230,.07);border-radius:50%}
.lo-header::after{content:'';position:absolute;bottom:-50px;left:80px;width:140px;height:140px;background:rgba(243,239,230,.04);border-radius:50%}
.lo-header-left{display:flex;align-items:center;gap:16px;position:relative;z-index:1}
.lo-header-ico{width:50px;height:50px;background:rgba(243,239,230,.15);border:1px solid rgba(243,239,230,.2);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--s)}
.lo-header-left h1{margin:0;font-size:1.5rem;font-weight:800;color:var(--s);letter-spacing:-.5px}
.lo-header-left p{margin:4px 0 0;font-size:.82rem;color:rgba(243,239,230,.75);font-weight:500}
.lo-header-right{display:flex;gap:10px;position:relative;z-index:1}

/* ── Buttons ───────────────────────────────────────────────── */
.lb{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--r-md);font-size:.82rem;font-weight:700;cursor:pointer;border:none;transition:all .2s var(--ease);white-space:nowrap;text-decoration:none;font-family:var(--font)}
.lb-ghost{background:rgba(243,239,230,.15);color:var(--s);border:1px solid rgba(243,239,230,.25)}
.lb-ghost:hover{background:rgba(243,239,230,.28)}
.lb-cream{background:var(--s);color:var(--p);font-weight:800;box-shadow:0 4px 12px rgba(0,0,0,.15)}
.lb-cream:hover{background:white;transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,0,0,.18)}
.lb-primary{background:var(--p);color:var(--s);box-shadow:0 4px 12px var(--p-30)}
.lb-primary:hover{background:var(--p-dk);transform:translateY(-1px);box-shadow:0 6px 18px var(--p-30)}
.lb-outline{background:white;color:var(--p);border:1.5px solid var(--bdr-str);box-shadow:var(--sh-sm)}
.lb-outline:hover{background:var(--s);border-color:var(--p);transform:translateY(-1px)}
.lb-sm{padding:7px 14px;font-size:.77rem}
.lb-icon{padding:0;width:34px;height:34px;justify-content:center}
.lb-icon-sm{padding:0;width:30px;height:30px;justify-content:center;border-radius:var(--r-sm)}

/* ── Filter Panel ──────────────────────────────────────────── */
.lo-filters{background:white;border-radius:var(--r-lg);border:1px solid var(--bdr);box-shadow:var(--sh-sm);margin-bottom:22px;overflow:hidden}
.lo-filter-top{padding:16px 22px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;border-bottom:1px solid var(--bdr)}
.lo-filter-field{display:flex;flex-direction:column;gap:5px}
.lo-flabel{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--txt-mut)}
.lo-finput{padding:9px 13px;border:1.5px solid var(--bdr);border-radius:var(--r-md);font-size:.82rem;color:var(--txt);background:var(--s);outline:none;transition:all .2s;font-family:var(--font)}
.lo-finput:focus{border-color:var(--p);background:white;box-shadow:0 0 0 3px var(--p-10)}
.lo-search-wrap{position:relative;flex:1;min-width:220px}
.lo-search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--txt-mut);font-size:.8rem;pointer-events:none}
.lo-search-wrap .lo-finput{padding-left:36px;width:100%}
.lo-chip-bar{padding:12px 22px;display:flex;gap:8px;align-items:center;background:var(--s);flex-wrap:wrap}
.lo-chip-lbl{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--txt-mut);margin-right:4px}
.lo-chip{padding:5px 14px;border-radius:20px;font-size:.75rem;font-weight:700;cursor:pointer;border:1.5px solid var(--bdr);background:white;color:var(--txt-sub);transition:all .2s;user-select:none;display:inline-flex;align-items:center;gap:5px}
.lo-chip:hover{border-color:var(--p);color:var(--p);background:var(--p-05)}
.lo-chip.active{background:var(--p);color:var(--s);border-color:var(--p);box-shadow:0 2px 8px var(--p-30)}

/* ── Table Card ────────────────────────────────────────────── */
.lo-table-card{background:white;border-radius:var(--r-lg);border:1px solid var(--bdr);box-shadow:var(--sh-sm);overflow:hidden}
.lo-table-toolbar{padding:16px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--bdr);background:white}
.lo-table-title{font-size:.95rem;font-weight:800;color:var(--txt);display:flex;align-items:center;gap:10px}
.lo-count-badge{background:var(--p-10);color:var(--p);font-size:.68rem;font-weight:800;padding:2px 10px;border-radius:20px;border:1px solid var(--p-20)}

/* ── Skeleton ──────────────────────────────────────────────── */
.lo-skel{animation:skel-pulse 1.6s ease-in-out infinite;background:linear-gradient(90deg,var(--s) 25%,#e8e4db 50%,var(--s) 75%);background-size:200% 100%;border-radius:6px}
@keyframes skel-pulse{0%{background-position:200% 0}100%{background-position:-200% 0}}
.lo-skel-row{display:flex;align-items:center;gap:16px;padding:16px 22px;border-bottom:1px solid var(--bdr)}

/* ── Table ─────────────────────────────────────────────────── */
.lo-table{width:100%;border-collapse:collapse;font-size:.8rem}
.lo-table thead tr{background:var(--s)}
.lo-table thead th{padding:11px 14px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--txt-mut);text-align:left;border-bottom:2px solid var(--bdr);white-space:nowrap}
.lo-table tbody tr{border-bottom:1px solid var(--bdr);transition:all .15s}
.lo-table tbody tr:hover{background:var(--p-05)}
.lo-table tbody tr:last-child{border-bottom:none}
.lo-table tbody td{padding:13px 14px;vertical-align:middle}

/* ── Avatar ────────────────────────────────────────────────── */
.lo-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--p) 0%,var(--p-lt) 100%);color:var(--s);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;letter-spacing:.5px}
.lo-pat-cell{display:flex;align-items:center;gap:10px}
.lo-pat-name{font-size:.82rem;font-weight:700;color:var(--txt);line-height:1.2}
.lo-pat-id{font-size:.68rem;color:var(--txt-mut);font-weight:600;margin-top:2px;font-family:monospace}

/* ── Badges ────────────────────────────────────────────────── */
.lo-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.68rem;font-weight:800;letter-spacing:.2px;white-space:nowrap;text-transform:uppercase}
.lo-dot{width:5px;height:5px;border-radius:50%;display:inline-block}

/* Status */
.b-ordered{background:var(--p-10);color:var(--p);border:1px solid var(--p-20)}
.b-ordered .lo-dot{background:var(--p)}
.b-progress{background:var(--p-15);color:var(--p-dk);border:1px solid var(--p-30)}
.b-progress .lo-dot{background:var(--p-dk);animation:blink 1.4s infinite}
.b-completed{background:var(--p);color:var(--s);border:1px solid var(--p)}
.b-completed .lo-dot{background:var(--s)}
.b-reported{background:var(--p-dk);color:var(--s);border:1px solid var(--p-dk)}
.b-reported .lo-dot{background:var(--s)}

/* Priority */
.b-routine{background:var(--s);color:var(--txt-sub);border:1px solid var(--bdr-str)}
.b-stat{background:var(--p-15);color:var(--p);border:1px solid var(--p-30)}
.b-urgent{background:var(--p);color:var(--s);border:1px solid var(--p);animation:ug-pulse 2s ease-in-out infinite}

@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
@keyframes ug-pulse{0%,100%{box-shadow:0 0 0 0 var(--p-30)}50%{box-shadow:0 0 0 4px var(--p-10)}}

/* ── Action Buttons ────────────────────────────────────────── */
.lo-actions{display:flex;gap:5px;justify-content:center;align-items:center}
.lo-ab{width:30px;height:30px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:.77rem;cursor:pointer;border:none;transition:all .2s;text-decoration:none;position:relative}
.lo-ab-ghost{background:var(--s);color:var(--txt-sub);border:1px solid var(--bdr)}
.lo-ab-ghost:hover{background:var(--p-10);color:var(--p);border-color:var(--p);transform:scale(1.08)}
.lo-ab-prim{background:var(--p);color:var(--s);box-shadow:0 2px 6px var(--p-30)}
.lo-ab-prim:hover{background:var(--p-dk);transform:scale(1.08)}
.lo-ab-outline{background:white;color:var(--p);border:1px solid var(--p-20)}
.lo-ab-outline:hover{background:var(--p);color:var(--s);transform:scale(1.08)}

/* ── Empty State ───────────────────────────────────────────── */
.lo-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 24px;text-align:center}
.lo-empty-ico{width:80px;height:80px;background:var(--p-10);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--p);margin-bottom:20px}
.lo-empty-title{font-size:1.1rem;font-weight:800;color:var(--txt);margin-bottom:8px}
.lo-empty-sub{font-size:.84rem;color:var(--txt-mut);max-width:300px;line-height:1.6}

/* ══════════════════════════════════════════════════════════════
   MODALS
══════════════════════════════════════════════════════════════ */
.lis-modal-overlay{
  position:fixed;inset:0;z-index:9999;
  display:flex;align-items:center;justify-content:center;padding:20px;
  background:rgba(10,25,16,0);backdrop-filter:blur(0px);
  opacity:0;visibility:hidden;
  transition:opacity .3s var(--ease),visibility .3s var(--ease),background .3s var(--ease),backdrop-filter .3s var(--ease);
}
.lis-modal-overlay.open{
  opacity:1;visibility:visible;
  background:rgba(10,25,16,.55);backdrop-filter:blur(8px);
}
.lis-modal{
  background:white;border-radius:var(--r-xl);
  box-shadow:var(--sh-xl);border:1px solid var(--bdr);
  width:100%;max-width:600px;max-height:90vh;overflow-y:auto;
  transform:translateY(28px) scale(.96);
  transition:transform .35s var(--spring);
}
.lis-modal-overlay.open .lis-modal{transform:translateY(0) scale(1)}

/* Standard modal header/footer (Create, Status modals) */
.lis-modal-header{
  padding:20px 24px;border-bottom:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(135deg,var(--p) 0%,var(--p-dk) 100%);
  border-radius:var(--r-xl) var(--r-xl) 0 0;
}
.lis-modal-title{font-size:1rem;font-weight:800;color:var(--s);display:flex;align-items:center;gap:10px}
.lis-modal-title-icon{width:36px;height:36px;background:rgba(243,239,230,.18);border:1px solid rgba(243,239,230,.22);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--s);font-size:.9rem}
.lis-modal-header .lis-modal-close{width:32px;height:32px;background:rgba(243,239,230,.15);border:1px solid rgba(243,239,230,.2);border-radius:50%;color:var(--s);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s}
.lis-modal-header .lis-modal-close:hover{background:rgba(243,239,230,.3)}
.lis-modal-body{padding:24px}
.lis-modal-footer{padding:16px 24px;border-top:1px solid var(--bdr);display:flex;justify-content:flex-end;gap:10px;background:var(--s);border-radius:0 0 var(--r-xl) var(--r-xl)}

/* Form elements in modals */
.lis-form-group{margin-bottom:16px}
.lis-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--txt-mut);margin-bottom:5px;display:block}
.lis-input{width:100%;padding:10px 13px;border:1.5px solid var(--bdr);border-radius:var(--r-md);font-size:.83rem;color:var(--txt);background:var(--s);outline:none;transition:all .2s;font-family:var(--font)}
.lis-input:focus{border-color:var(--p);background:white;box-shadow:0 0 0 3px var(--p-10)}
.lis-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%231F6B4A' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:36px;cursor:pointer}
.lis-spinner{width:16px;height:16px;border:2px solid rgba(243,239,230,.3);border-top-color:var(--s);border-radius:50%;animation:spin .7s linear infinite;display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Search wrap (in create modal) */
.lis-search-wrap{position:relative;max-width:100%}
.lis-search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--txt-mut);font-size:.8rem;pointer-events:none}
.lis-search-wrap .lis-input{padding-left:36px}

/* ══════════════════════════════════════════════════════════════
   RESULT ENTRY MODAL — PREMIUM WORKSPACE
══════════════════════════════════════════════════════════════ */
#resultModal .lis-modal{max-width:960px;width:96%}

/* Result modal header (custom) */
.rm-header{
  padding:22px 28px;
  background:linear-gradient(135deg,var(--p) 0%,var(--p-dk) 100%);
  display:flex;justify-content:space-between;align-items:center;
  position:relative;overflow:hidden;
}
.rm-header::before{content:'';position:absolute;top:-60px;right:-60px;width:180px;height:180px;background:rgba(243,239,230,.07);border-radius:50%}
.rm-header::after{content:'';position:absolute;bottom:-40px;left:120px;width:100px;height:100px;background:rgba(243,239,230,.04);border-radius:50%}
.rm-header-left{display:flex;align-items:center;gap:14px;position:relative;z-index:1}
.rm-header-ico{width:46px;height:46px;background:rgba(243,239,230,.18);border:1px solid rgba(243,239,230,.22);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--s)}
.rm-header-left h3{margin:0;font-size:1.15rem;font-weight:800;color:var(--s);letter-spacing:-.3px}
.rm-header-left p{margin:3px 0 0;font-size:.78rem;color:rgba(243,239,230,.75);font-weight:500}
.rm-close-btn{width:34px;height:34px;background:rgba(243,239,230,.15);border:1px solid rgba(243,239,230,.2);border-radius:50%;color:var(--s);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .2s;position:relative;z-index:1}
.rm-close-btn:hover{background:rgba(243,239,230,.3)}

/* Info bar */
.rm-info-bar{
  background:var(--s);border:1px solid var(--bdr);border-radius:var(--r-md);
  padding:14px 20px;display:flex;justify-content:space-between;align-items:center;
  margin-bottom:20px;
}
.rm-info-item label{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--txt-mut);display:block;margin-bottom:3px}
.rm-info-item strong{font-size:.95rem;color:var(--txt);font-weight:700}
.rm-info-item code{font-size:.9rem;color:var(--p);font-weight:800;font-family:'Courier New',monospace;background:var(--p-10);padding:2px 8px;border-radius:6px}
.rm-info-div{width:1px;height:36px;background:var(--bdr)}

/* Two column layout */
.rm-body{background:var(--s);padding:24px}
.rm-cols{display:grid;grid-template-columns:260px 1fr;gap:20px}

/* Upload Panel */
.rm-upload-panel{background:white;border:1px solid var(--bdr);border-radius:var(--r-lg);overflow:hidden;display:flex;flex-direction:column}
.rm-panel-hdr{padding:13px 16px;background:var(--s);border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:8px}
.rm-panel-hdr-ico{width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.78rem}
.rm-panel-hdr h4{margin:0;font-size:.87rem;font-weight:700;color:var(--txt)}
.rm-upload-zone{
  margin:16px;border:2px dashed var(--bdr-str);border-radius:var(--r-md);
  padding:28px 16px;text-align:center;position:relative;cursor:pointer;
  transition:all .3s;background:var(--s);flex-grow:1;
  display:flex;flex-direction:column;justify-content:center;align-items:center;
}
.rm-upload-zone:hover{border-color:var(--p);background:white}
.rm-upload-zone:hover .rm-upload-zone-ico{background:var(--p);color:var(--s);transform:scale(1.1)}
.rm-upload-zone-ico{width:52px;height:52px;border-radius:50%;background:var(--p-10);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--p);font-size:1.4rem;transition:all .3s}
.rm-upload-zone h5{margin:0 0 5px;font-size:.87rem;font-weight:700;color:var(--txt)}
.rm-upload-zone p{margin:0 0 14px;font-size:.73rem;color:var(--txt-mut);line-height:1.4}
.rm-browse-lbl{
  background:var(--p);color:var(--s);padding:7px 20px;border-radius:var(--r-md);
  font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s;
  box-shadow:0 3px 8px var(--p-30);display:inline-block;
}
.rm-browse-lbl:hover{background:var(--p-dk);transform:translateY(-1px)}
.rm-file-badge{
  display:none;align-items:center;gap:8px;
  margin:12px 16px 16px;padding:10px 14px;
  background:var(--p-10);border:1px solid var(--p-20);border-radius:var(--r-md);
  font-size:.78rem;color:var(--p);font-weight:700;
}

/* Params Panel */
.rm-params-panel{background:white;border:1px solid var(--bdr);border-radius:var(--r-lg);overflow:hidden;display:flex;flex-direction:column}
.rm-params-hdr{padding:13px 16px;background:var(--s);border-bottom:1px solid var(--bdr);display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.rm-params-hdr h4{margin:0;font-size:.87rem;font-weight:700;color:var(--txt)}
.rm-tpl-sel{
  padding:6px 12px;font-size:.75rem;border-radius:var(--r-sm);
  border:1.5px solid var(--p);background:white;color:var(--p);
  font-weight:700;outline:none;cursor:pointer;transition:all .2s;font-family:var(--font);
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%231F6B4A' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;padding-right:28px;
}
.rm-tpl-sel:focus{box-shadow:0 0 0 3px var(--p-10)}
.rm-params-scroll{max-height:310px;overflow-y:auto;padding:14px;flex:1}
.rm-ptable{width:100%;border-collapse:separate;border-spacing:0 6px}
.rm-ptable thead{position:sticky;top:0;background:white;z-index:1}
.rm-ptable thead th{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--txt-mut);padding:0 8px 10px;text-align:left;border-bottom:1px solid var(--bdr)}
.rm-ptable tbody tr td{padding:3px 5px}
.rm-ptable tbody tr td:first-child{padding-left:0}
.rm-ptable tbody tr td:last-child{padding-right:0}
.rm-pinput{
  width:100%;padding:9px 11px;border:1.5px solid var(--bdr);border-radius:var(--r-sm);
  font-size:.8rem;color:var(--txt);background:var(--s);outline:none;
  transition:all .2s;font-family:var(--font);
}
.rm-pinput:focus{border-color:var(--p);background:white;box-shadow:0 0 0 3px var(--p-10)}
.rm-pinput.p-result{font-weight:800;background:white;border-color:var(--bdr-str)}
.rm-pinput.p-result:focus{border-color:var(--p);box-shadow:0 0 0 3px var(--p-10)}
.rm-pinput.p-unit,.rm-pinput.p-range{background:transparent;border:none;font-size:.75rem;color:var(--txt-mut)}
.rm-pinput.p-low{color:#92400e;background:#fefce8;border-color:#d97706}
.rm-pinput.p-high{color:#991b1b;background:#fef2f2;border-color:#dc2626}
.rm-pinput.p-normal{color:var(--p);background:rgba(31,107,74,.04);border-color:var(--p-20)}
.rm-del-btn{width:26px;height:26px;background:transparent;border:none;color:var(--bdr-str);border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:all .2s}
.rm-del-btn:hover{background:#fef2f2;color:#dc2626}
.rm-add-row-wrap{padding:10px 14px;border-top:1px solid var(--bdr);background:var(--s);flex-shrink:0}
.rm-add-row-btn{
  width:100%;padding:9px;background:white;border:1.5px dashed var(--p);
  border-radius:var(--r-md);color:var(--p);font-weight:700;font-size:.8rem;
  cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;
  font-family:var(--font);
}
.rm-add-row-btn:hover{background:var(--p-05)}

/* Result modal footer */
.rm-footer{
  background:white;padding:18px 28px;
  display:flex;justify-content:flex-end;gap:10px;
  border-top:1px solid var(--bdr);
}
.rm-cancel-btn{padding:10px 22px;border:1.5px solid var(--bdr-str);background:white;color:var(--p);border-radius:var(--r-md);font-weight:700;font-size:.85rem;cursor:pointer;transition:all .2s;font-family:var(--font)}
.rm-cancel-btn:hover{background:var(--s)}
.rm-save-btn{
  padding:10px 28px;background:var(--p);border:none;color:var(--s);
  border-radius:var(--r-md);font-weight:800;font-size:.88rem;cursor:pointer;
  box-shadow:0 4px 14px var(--p-30);transition:all .25s var(--ease);
  display:flex;align-items:center;gap:8px;font-family:var(--font);
}
.rm-save-btn:hover{background:var(--p-dk);transform:translateY(-1px);box-shadow:0 6px 20px var(--p-30)}
.rm-save-btn:active{transform:translateY(0)}

/* ── Row Animation ─────────────────────────────────────────── */
.row-anim{animation:rowIn .3s var(--spring) forwards}
@keyframes rowIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}

/* ── Responsive ────────────────────────────────────────────── */
@media(max-width:1100px){.lo-stats{grid-template-columns:repeat(2,1fr)}.rm-cols{grid-template-columns:1fr}}
@media(max-width:768px){.lo-page{padding:14px}.lo-stats{grid-template-columns:1fr 1fr}.lo-header{flex-direction:column;gap:16px;align-items:flex-start}.lo-filter-top{flex-direction:column}}
@media(max-width:480px){.lo-stats{grid-template-columns:1fr}}

/* ── Fade-in animations ────────────────────────────────────── */
.lo-fade{animation:fadeUp .5s var(--ease) both}
.lo-fade-1{animation:fadeUp .5s .08s var(--ease) both}
.lo-fade-2{animation:fadeUp .5s .16s var(--ease) both}
.lo-fade-3{animation:fadeUp .5s .24s var(--ease) both}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content">
<div class="lo-page">

  <!-- ── Premium Header with Integrated Stats ─────── -->
  <div class="lo-header lo-fade" style="flex-direction:column; align-items:stretch; padding:0; gap:0;">

    <!-- Top row: Title + Actions -->
    <div style="display:flex; justify-content:space-between; align-items:center; padding:24px 32px; position:relative; z-index:1;">
      <div class="lo-header-left">
        <div class="lo-header-ico"><i class="fas fa-flask"></i></div>
        <div>
          <h1>Lab Orders</h1>
          <p>Track and manage all laboratory test requests</p>
        </div>
      </div>
      <div class="lo-header-right">
        <button class="lb lb-ghost" onclick="loadOrders()"><i class="fas fa-sync-alt"></i> Refresh</button>
        <button class="lb lb-cream" onclick="openCreateModal()"><i class="fas fa-plus"></i> New Order</button>
      </div>
    </div>

    <!-- Stats row embedded in header -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid rgba(243,239,230,0.15); position:relative; z-index:1;">

      <div style="padding:20px 28px; border-right:1px solid rgba(243,239,230,0.15); display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;background:rgba(243,239,230,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--s);flex-shrink:0;border:1px solid rgba(243,239,230,0.2);">
          <i class="fas fa-flask"></i>
        </div>
        <div>
          <div id="stat-total" style="font-size:2.2rem;font-weight:900;color:var(--s);line-height:1;letter-spacing:-1px;">—</div>
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:rgba(243,239,230,0.65);margin-top:3px;">Total Today</div>
        </div>
      </div>

      <div style="padding:20px 28px; border-right:1px solid rgba(243,239,230,0.15); display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;background:rgba(243,239,230,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--s);flex-shrink:0;border:1px solid rgba(243,239,230,0.2);">
          <i class="fas fa-hourglass-half"></i>
        </div>
        <div>
          <div id="stat-pending" style="font-size:2.2rem;font-weight:900;color:var(--s);line-height:1;letter-spacing:-1px;">—</div>
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:rgba(243,239,230,0.65);margin-top:3px;">Pending</div>
        </div>
      </div>

      <div style="padding:20px 28px; border-right:1px solid rgba(243,239,230,0.15); display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;background:rgba(243,239,230,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--s);flex-shrink:0;border:1px solid rgba(243,239,230,0.2);">
          <i class="fas fa-spinner"></i>
        </div>
        <div>
          <div id="stat-progress" style="font-size:2.2rem;font-weight:900;color:var(--s);line-height:1;letter-spacing:-1px;">—</div>
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:rgba(243,239,230,0.65);margin-top:3px;">In Progress</div>
        </div>
      </div>

      <div style="padding:20px 28px; display:flex; align-items:center; gap:16px;">
        <div style="width:48px;height:48px;background:rgba(243,239,230,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--s);flex-shrink:0;border:1px solid rgba(243,239,230,0.2);">
          <i class="fas fa-check-double"></i>
        </div>
        <div>
          <div id="stat-done" style="font-size:2.2rem;font-weight:900;color:var(--s);line-height:1;letter-spacing:-1px;">—</div>
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:rgba(243,239,230,0.65);margin-top:3px;">Completed</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Filters ────────────────────────────────────────── -->
  <div class="lo-filters lo-fade-2">
    <div class="lo-filter-top">
      <div class="lo-filter-field">
        <label class="lo-flabel">Date</label>
        <input type="date" class="lo-finput" id="filter-date" value="<?= date('Y-m-d') ?>" style="max-width:155px;">
      </div>
      <div class="lo-filter-field">
        <label class="lo-flabel">Status</label>
        <select class="lo-finput" id="filter-status" style="max-width:155px;">
          <option value="">All Statuses</option>
          <option value="Ordered">Ordered</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Reported">Reported</option>
        </select>
      </div>
      <div class="lo-filter-field">
        <label class="lo-flabel">Priority</label>
        <select class="lo-finput" id="filter-priority" style="max-width:140px;">
          <option value="">All Priorities</option>
          <option value="Urgent">Urgent</option>
          <option value="Stat">Stat</option>
          <option value="Routine">Routine</option>
        </select>
      </div>
      <div class="lo-filter-field" style="flex:1;min-width:220px;">
        <label class="lo-flabel">Search</label>
        <div class="lo-search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" class="lo-finput" id="filter-search" placeholder="Test, patient, order ID...">
        </div>
      </div>
      <div class="lo-filter-field" style="padding-bottom:1px;">
        <label class="lo-flabel" style="opacity:0;">Go</label>
        <div style="display:flex;gap:8px;">
          <button class="lb lb-primary" onclick="loadOrders()"><i class="fas fa-filter"></i> Filter</button>
          <button class="lb lb-outline lb-icon" onclick="resetFilters()" title="Reset"><i class="fas fa-times"></i></button>
        </div>
      </div>
    </div>
    <div class="lo-chip-bar">
      <span class="lo-chip-lbl">Quick:</span>
      <span class="lo-chip active" data-filter="today" onclick="quickFilter('today',this)"><i class="fas fa-calendar-day" style="font-size:.65rem;"></i> Today</span>
      <span class="lo-chip" data-filter="pending" onclick="quickFilter('pending',this)"><i class="fas fa-clock" style="font-size:.65rem;"></i> Pending</span>
      <span class="lo-chip" data-filter="urgent" onclick="quickFilter('urgent',this)"><i class="fas fa-exclamation" style="font-size:.65rem;"></i> Urgent</span>
      <span class="lo-chip" data-filter="completed" onclick="quickFilter('completed',this)"><i class="fas fa-check" style="font-size:.65rem;"></i> Completed</span>
      <span class="lo-chip" data-filter="all" onclick="quickFilter('all',this)"><i class="fas fa-database" style="font-size:.65rem;"></i> All Time</span>
    </div>
  </div>

  <!-- ── Orders Table ───────────────────────────────────── -->
  <div class="lo-table-card lo-fade-3">
    <div class="lo-table-toolbar">
      <div class="lo-table-title">
        <i class="fas fa-list-alt" style="color:var(--p);"></i>
        Orders List
        <span class="lo-count-badge" id="orders-count-badge">0</span>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="lb lb-outline lb-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>

    <!-- Skeleton loader -->
    <div id="table-loading" style="display:flex;flex-direction:column;">
      <?php for($i=0;$i<6;$i++): ?>
      <div class="lo-skel-row">
        <div class="lo-skel" style="width:18px;height:14px;"></div>
        <div class="lo-skel" style="width:88px;height:22px;border-radius:6px;"></div>
        <div style="display:flex;align-items:center;gap:10px;flex:1.2;min-width:160px;">
          <div class="lo-skel" style="width:34px;height:34px;border-radius:50%;flex-shrink:0;"></div>
          <div style="display:flex;flex-direction:column;gap:4px;flex:1;">
            <div class="lo-skel" style="width:130px;height:13px;"></div>
            <div class="lo-skel" style="width:80px;height:9px;"></div>
          </div>
        </div>
        <div class="lo-skel" style="width:110px;height:13px;flex:1;"></div>
        <div class="lo-skel" style="width:90px;height:13px;flex:.8;"></div>
        <div class="lo-skel" style="width:58px;height:22px;border-radius:20px;"></div>
        <div class="lo-skel" style="width:80px;height:22px;border-radius:20px;"></div>
        <div class="lo-skel" style="width:65px;height:13px;"></div>
        <div style="display:flex;gap:5px;">
          <div class="lo-skel" style="width:30px;height:30px;border-radius:8px;"></div>
          <div class="lo-skel" style="width:30px;height:30px;border-radius:8px;"></div>
          <div class="lo-skel" style="width:30px;height:30px;border-radius:8px;"></div>
          <div class="lo-skel" style="width:30px;height:30px;border-radius:8px;"></div>
        </div>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Table -->
    <div id="table-wrap" style="display:none;overflow-x:auto;">
      <table class="lo-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Order ID</th>
            <th>Patient</th>
            <th>Test Name</th>
            <th>Doctor</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Date / Time</th>
            <th style="text-align:center;">Data Entry</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="orders-tbody"></tbody>
      </table>
      <div class="lo-empty" id="orders-empty" style="display:none;">
        <div class="lo-empty-ico"><i class="fas fa-flask"></i></div>
        <div class="lo-empty-title">No orders found</div>
        <div class="lo-empty-sub">Adjust the filters or create a new lab order to get started.</div>
        <button class="lb lb-primary" style="margin-top:20px;" onclick="openCreateModal()">
          <i class="fas fa-plus"></i> New Order
        </button>
      </div>
    </div>
  </div>

</div><!-- /.lo-page -->
</div><!-- /.lis-content -->
</div><!-- /.lis-main-content -->

<!-- ══════════════════════════════════════════════════════════
     CREATE ORDER MODAL
══════════════════════════════════════════════════════════ -->
<div class="lis-modal-overlay" id="createModal">
  <div class="lis-modal">
    <div class="lis-modal-header">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-plus"></i></div>
        <div>
          New Lab Order
          <div style="font-size:.68rem;font-weight:500;color:rgba(243,239,230,.7);margin-top:2px;">Create a new laboratory test request</div>
        </div>
      </div>
      <button class="lis-modal-close" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="lis-modal-body">
      
      <!-- Mode Toggle -->
      <div class="lis-mode-toggle" style="display:flex; gap:10px; margin-bottom: 20px;">
         <button class="lb lb-primary" id="btn-mode-inpatient" onclick="setOrderMode('inpatient')" style="flex:1">In-Patient / Registered</button>
         <button class="lb lb-outline" id="btn-mode-walkin" onclick="setOrderMode('walkin')" style="flex:1">Walk-In (New)</button>
      </div>

      <!-- In-Patient Section -->
      <div id="section-inpatient">
        <div class="lis-form-group">
          <label class="lis-label">Search Patient *</label>
          <div class="lis-search-wrap" style="max-width:100%;">
            <i class="fas fa-search"></i>
            <input type="text" class="lis-input" id="modal-patient-search" placeholder="Type patient name or ID..." autocomplete="off">
          </div>
          <div id="patient-results" style="background:white;border:1px solid var(--bdr);border-top:none;border-radius:0 0 10px 10px;max-height:190px;overflow-y:auto;display:none;box-shadow:var(--sh-md);position:absolute;width:calc(100% - 48px);z-index:100;"></div>
          <input type="hidden" id="modal-patient-id">
          <input type="hidden" id="modal-doctor-id">
        </div>
        
        <!-- Profile View -->
        <div id="selected-patient-profile" style="display:none; background:var(--p-05); border:1px solid var(--p-20); border-radius:10px; padding:15px; margin-bottom: 15px;">
           <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <div>
                 <div style="font-weight:700; font-size:1.1rem; color:var(--txt);" id="sp-name"></div>
                 <div style="font-size:0.85rem; color:var(--txt-mut); margin-top:4px;" id="sp-meta"></div>
                 <div style="font-size:0.85rem; color:var(--txt-mut); margin-top:2px;">
                    <i class="fas fa-phone" style="width:14px; text-align:center;"></i> <span id="sp-phone"></span>
                 </div>
                 <div style="font-size:0.85rem; color:var(--txt-mut); margin-top:2px;">
                    <i class="fas fa-user-md" style="width:14px; text-align:center;"></i> Doctor: <span id="sp-ref"></span>
                 </div>
              </div>
              <button onclick="clearPatientSelection()" style="background:none;border:none;color:var(--txt-mut);cursor:pointer;padding:5px;font-size:1.1rem;"><i class="fas fa-times"></i></button>
           </div>
        </div>
      </div>

      <!-- Walk-In Section -->
      <div id="section-walkin" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
           <div class="lis-form-group">
             <label class="lis-label">Patient Name *</label>
             <input type="text" class="lis-input" id="walkin-name" placeholder="John Doe">
           </div>
           <div class="lis-form-group">
             <label class="lis-label">Age *</label>
             <input type="number" class="lis-input" id="walkin-age" placeholder="30">
           </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
           <div class="lis-form-group">
             <label class="lis-label">Phone Number *</label>
             <input type="text" class="lis-input" id="walkin-phone" placeholder="9876543210">
           </div>
           <div class="lis-form-group">
             <label class="lis-label">Reference Doctor</label>
             <input type="text" class="lis-input" id="walkin-doctor" placeholder="Dr. Smith (Outside)">
           </div>
        </div>
      </div>

      <!-- Tests Selection -->
      <div class="lis-form-group">
        <label class="lis-label">Select Tests *</label>
        <div class="lis-search-wrap" style="max-width:100%; position:relative;">
           <i class="fas fa-flask"></i>
           <input type="text" class="lis-input" id="modal-test-search" placeholder="Type to search and add tests..." autocomplete="off">
        </div>
        <div id="test-results" style="background:white;border:1px solid var(--bdr);border-top:none;border-radius:0 0 10px 10px;max-height:150px;overflow-y:auto;display:none;box-shadow:var(--sh-md);position:absolute;width:calc(100% - 48px);z-index:100;"></div>
        <div id="selected-tests-container" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
           <!-- Selected Test Chips Go Here -->
        </div>
      </div>

      <div class="lis-form-group">
        <label class="lis-label">Priority</label>
        <select class="lis-input lis-select" id="modal-priority">
          <option value="Routine">Routine</option>
          <option value="Stat">Stat</option>
          <option value="Urgent">Urgent</option>
        </select>
      </div>

      <div class="lis-form-group">
        <label class="lis-label">Notes</label>
        <textarea class="lis-input" id="modal-notes" rows="2" placeholder="Clinical notes or instructions..." style="resize:vertical;"></textarea>
      </div>
    </div>
    <div class="lis-modal-footer">
      <button class="lb lb-outline" onclick="closeCreateModal()">Cancel</button>
      <button class="lb lb-primary" id="createOrderBtn" onclick="createOrder()">
        <i class="fas fa-paper-plane"></i> Send Order
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     STATUS UPDATE MODAL
══════════════════════════════════════════════════════════ -->
<div class="lis-modal-overlay" id="statusModal">
  <div class="lis-modal" style="max-width:420px;">
    <div class="lis-modal-header">
      <div class="lis-modal-title">
        <div class="lis-modal-title-icon"><i class="fas fa-edit"></i></div>
        Update Status
      </div>
      <button class="lis-modal-close" onclick="closeStatusModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="lis-modal-body">
      <div style="background:var(--p-10);border:1px solid var(--p-20);border-radius:var(--r-md);padding:12px 16px;margin-bottom:18px;">
        <span style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--txt-mut);display:block;margin-bottom:3px;">Order Reference</span>
        <strong id="status-order-id" style="font-size:.95rem;color:var(--p);font-family:monospace;font-weight:800;"></strong>
      </div>
      <div class="lis-form-group">
        <label class="lis-label">New Status</label>
        <select class="lis-input lis-select" id="status-select">
          <option value="Ordered">Ordered</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Reported">Reported</option>
        </select>
      </div>
    </div>
    <div class="lis-modal-footer">
      <button class="lb lb-outline" onclick="closeStatusModal()">Cancel</button>
      <button class="lb lb-primary" onclick="saveStatus()"><i class="fas fa-save"></i> Save Status</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     RESULT ENTRY MODAL — PREMIUM WORKSPACE
══════════════════════════════════════════════════════════ -->
<style>
/* ── Camera Modal ────────────────────────────────────────── */
#cameraModal{position:fixed;inset:0;z-index:10500;background:rgba(0,0,0,.92);display:none;flex-direction:column;align-items:center;justify-content:center;gap:16px}
#cameraModal.show{display:flex}
#cameraVideo{width:min(500px,90vw);border-radius:16px;border:3px solid var(--p);box-shadow:0 0 40px rgba(31,107,74,.4)}
.cam-btn{padding:10px 24px;border-radius:var(--r-md);font-size:.88rem;font-weight:700;cursor:pointer;border:none;font-family:var(--font);display:inline-flex;align-items:center;gap:8px}
/* ── Attachment Thumbnails ────────────────────────────────── */
.att-grid{display:flex;flex-wrap:wrap;gap:8px;padding:12px;min-height:60px}
.att-thumb{width:70px;height:70px;border-radius:var(--r-md);object-fit:cover;border:2px solid var(--bdr);cursor:pointer;transition:all .2s;position:relative}
.att-thumb:hover{border-color:var(--p);transform:scale(1.05)}
.att-del{position:absolute;top:-5px;right:-5px;width:18px;height:18px;background:#ef4444;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;color:white;cursor:pointer;border:none;z-index:1}
.att-wrap{position:relative;display:inline-block}
/* ── Patient Toggle ───────────────────────────────────────── */
.pt-toggle{display:flex;gap:0;background:var(--s);border-radius:var(--r-md);border:1.5px solid var(--bdr);overflow:hidden;margin-bottom:14px}
.pt-toggle-btn{flex:1;padding:9px 0;font-size:.8rem;font-weight:700;border:none;cursor:pointer;transition:all .2s;font-family:var(--font);background:transparent;color:var(--txt-mut)}
.pt-toggle-btn.active{background:var(--p);color:var(--s)}
/* ── Test Search ──────────────────────────────────────────── */
.test-search-wrap{position:relative}
.test-search-dd{position:absolute;top:calc(100% + 4px);left:0;right:0;background:white;border:1px solid var(--bdr);border-radius:var(--r-md);box-shadow:var(--sh-md);max-height:200px;overflow-y:auto;z-index:100;display:none}
.test-dd-cat{padding:6px 12px;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--txt-mut);background:var(--s);border-bottom:1px solid var(--bdr);sticky;top:0}
.test-dd-item{padding:9px 14px;cursor:pointer;font-size:.82rem;color:var(--txt);border-bottom:1px solid var(--bdr);transition:background .15s;display:flex;align-items:center;gap:8px}
.test-dd-item:hover{background:var(--p-05)}
.test-dd-item:last-child{border-bottom:none}
.test-dd-badge{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:10px;border:1px solid var(--bdr)}
.test-dd-badge.lab{background:var(--p-10);color:var(--p);border-color:var(--p-20)}
.test-dd-badge.rad{background:rgba(31,107,74,.08);color:var(--p-dk);border-color:rgba(31,107,74,.2)}
.test-dd-badge.oth{background:var(--s);color:var(--txt-sub);border-color:var(--bdr-str)}
</style>

<!-- Camera capture modal (overlay) -->
<div id="cameraModal">
  <video id="cameraVideo" autoplay playsinline></video>
  <div style="display:flex;gap:12px;">
    <button class="cam-btn" style="background:var(--p);color:var(--s);" onclick="capturePhoto()">
      <i class="fas fa-camera"></i> Capture
    </button>
    <button class="cam-btn" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.3);" onclick="closeCameraModal()">
      <i class="fas fa-times"></i> Cancel
    </button>
  </div>
  <canvas id="cameraCanvas" style="display:none;"></canvas>
</div>

<div class="lis-modal-overlay" id="resultModal">
  <div class="lis-modal" style="max-width:1060px;width:96%;">

    <!-- Header -->
    <div class="rm-header">
      <div class="rm-header-left">
        <div class="rm-header-ico"><i class="fas fa-microscope"></i></div>
        <div>
          <h3>Result Entry Data Center</h3>
          <p>Advanced Laboratory Reporting System</p>
        </div>
      </div>
      <button class="rm-close-btn" type="button" onclick="closeResultModal()"><i class="fas fa-times"></i></button>
    </div>

    <!-- Body -->
    <div class="rm-body" style="max-height:80vh;overflow-y:auto;">

      <!-- Order Info Bar -->
      <div class="rm-info-bar">
        <div class="rm-info-item">
          <label>Order Reference</label>
          <code id="result-order-id"></code>
        </div>
        <div class="rm-info-div"></div>
        <div class="rm-info-item">
          <label>Test Protocol</label>
          <strong id="result-test-name"></strong>
        </div>
      </div>

      <!-- ══ TWO-COLUMN LAYOUT ══ -->
      <div style="display:grid;grid-template-columns:280px 1fr;gap:16px;">

        <!-- ▌COL 1: ATTACHMENT PANEL ▌ -->
        <div style="background:white;border:1px solid var(--bdr);border-radius:var(--r-lg);overflow:hidden;display:flex;flex-direction:column;">
          <div style="padding:12px 14px;background:var(--s);border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:8px;">
            <div style="width:22px;height:22px;border-radius:6px;background:rgba(239,68,68,.12);color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:.72rem;"><i class="fas fa-paperclip"></i></div>
            <h4 style="margin:0;font-size:.86rem;font-weight:700;color:var(--txt);">Attachment</h4>
          </div>

          <!-- 3 capture buttons -->
          <div style="padding:12px;display:flex;flex-direction:column;gap:8px;">

            <!-- Camera -->
            <button type="button" onclick="openCameraModal()" style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--p-05);border:1.5px solid var(--bdr);border-radius:var(--r-md);cursor:pointer;transition:all .2s;font-family:var(--font);width:100%;" onmouseover="this.style.borderColor='var(--p)';this.style.background='var(--p-10)';" onmouseout="this.style.borderColor='var(--bdr)';this.style.background='var(--p-05)';">
              <div style="width:38px;height:38px;background:var(--p);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--s);flex-shrink:0;">
                <i class="fas fa-camera"></i>
              </div>
              <div style="text-align:left;">
                <div style="font-size:.82rem;font-weight:700;color:var(--txt);">Camera</div>
                <div style="font-size:.68rem;color:var(--txt-mut);">Capture photo</div>
              </div>
            </button>

            <!-- Scan -->
            <label style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--p-05);border:1.5px solid var(--bdr);border-radius:var(--r-md);cursor:pointer;transition:all .2s;" onmouseover="this.style.borderColor='var(--p)';this.style.background='var(--p-10)';" onmouseout="this.style.borderColor='var(--bdr)';this.style.background='var(--p-05)';">
              <div style="width:38px;height:38px;background:var(--p-dk);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--s);flex-shrink:0;">
                <i class="fas fa-qrcode"></i>
              </div>
              <div style="text-align:left;">
                <div style="font-size:.82rem;font-weight:700;color:var(--txt);">Scan / Doc</div>
                <div style="font-size:.68rem;color:var(--txt-mut);">Upload scanned file</div>
              </div>
              <input type="file" id="result-scan-file" accept="application/pdf,image/*" style="display:none;" onchange="handleAttachFile(this,'scan')">
            </label>

            <!-- Browse -->
            <label style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--p-05);border:1.5px solid var(--bdr);border-radius:var(--r-md);cursor:pointer;transition:all .2s;" onmouseover="this.style.borderColor='var(--p)';this.style.background='var(--p-10)';" onmouseout="this.style.borderColor='var(--bdr)';this.style.background='var(--p-05)';">
              <div style="width:38px;height:38px;background:var(--s-dk);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--p);flex-shrink:0;border:1px solid var(--bdr-str);">
                <i class="fas fa-folder-open"></i>
              </div>
              <div style="text-align:left;">
                <div style="font-size:.82rem;font-weight:700;color:var(--txt);">Browse Files</div>
                <div style="font-size:.68rem;color:var(--txt-mut);">PDF, JPG, PNG</div>
              </div>
              <input type="file" id="result-report-file" accept="application/pdf,image/*" style="display:none;" onchange="handleAttachFile(this,'browse')" multiple>
            </label>
          </div>

          <!-- Thumbnails -->
          <div id="att-grid" class="att-grid" style="border-top:1px solid var(--bdr);flex:1;"></div>
          <div id="att-empty" style="padding:16px;text-align:center;font-size:.75rem;color:var(--txt-mut);"><i class="fas fa-images" style="font-size:1.4rem;color:var(--bdr-str);display:block;margin-bottom:6px;"></i>No attachments yet</div>
        </div>

        <!-- Patient Panel Removed per request -->

        <!-- ▌COL 3: TEST + PARAMETERS PANEL ▌ -->
        <div style="background:white;border:1px solid var(--bdr);border-radius:var(--r-lg);overflow:hidden;display:flex;flex-direction:column;">
          <div style="padding:12px 16px;background:var(--s);border-bottom:1px solid var(--bdr);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;gap:12px;">
            <h4 style="margin:0;font-size:.86rem;font-weight:700;color:var(--txt);white-space:nowrap;"><i class="fas fa-list-ul" style="color:var(--p);margin-right:6px;"></i>Test Parameters</h4>
            
            <div class="test-search-wrap" style="width:100%;max-width:350px;">
              <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--p);font-size:.85rem;"></i>
                <input type="text" id="test-search-input" class="rm-pinput" placeholder="Search Test (Lab/Rad/Other) or Type..." style="width:100%;padding:8px 12px 8px 34px;background:white;border:1.5px solid var(--p-30);border-radius:var(--r-md);font-weight:700;color:var(--txt);box-shadow:var(--sh-sm);transition:all 0.2s;" oninput="searchTestServices(this.value)" onclick="searchTestServices(this.value)" onfocus="this.style.borderColor='var(--p)';this.style.boxShadow='0 0 0 3px var(--p-10)';" onblur="setTimeout(() => { this.style.borderColor='var(--p-30)'; this.style.boxShadow='var(--sh-sm)'; }, 200);">
              </div>
              <div id="test-search-dd" class="test-search-dd" style="top:calc(100% + 4px);"></div>
            </div>
            
            <input type="hidden" id="template-select">
          </div>

          <!-- Parameters table -->
          <div class="rm-params-scroll" style="flex:1;">
            <table class="rm-ptable">
              <thead>
                <tr>
                  <th style="width:32%;">Parameter</th>
                  <th style="width:24%;">Result</th>
                  <th style="width:16%;">Unit</th>
                  <th style="width:22%;">Normal Range</th>
                  <th style="width:6%;"></th>
                </tr>
              </thead>
              <tbody id="result-params-tbody">
                <!-- Rows injected by JS -->
              </tbody>
            </table>
          </div>

          <div class="rm-add-row-wrap">
            <button type="button" class="rm-add-row-btn" onclick="addResultRow()">
              <i class="fas fa-plus-circle"></i> Add Custom Parameter
            </button>
          </div>
        </div>

      </div><!-- /.three-cols -->
    </div><!-- /.rm-body -->

    <!-- Footer -->
    <div class="rm-footer">
      <button type="button" class="rm-cancel-btn" onclick="closeResultModal()">Cancel</button>
      <button type="button" class="rm-save-btn" id="saveResultBtn" onclick="saveResult()">
        <i class="fas fa-check-circle"></i> Submit Final Results
      </button>
    </div>

  </div>
</div>

<?php require_once 'includes/lab_foot.php'; ?>

<script>
let allOrders = [];
let allTests  = [];
let activeOrderId = null;

let prevTotalOrders = null;

// ── Load orders ──────────────────────────────────────────────
async function loadOrders(isBackground = false) {
  if (!isBackground) {
    document.getElementById('table-loading').style.display = 'flex';
    document.getElementById('table-wrap').style.display   = 'none';
  }

  const date     = document.getElementById('filter-date').value;
  const status   = document.getElementById('filter-status').value;
  const priority = document.getElementById('filter-priority').value;
  const search   = document.getElementById('filter-search').value;
  const allMode  = document.querySelector('.lo-chip.active')?.dataset.filter === 'all' ? '1' : '0';

  let url = `/api/laboratory/orders?all=${allMode}&date=${date}&status=${encodeURIComponent(status)}&priority=${encodeURIComponent(priority)}&search=${encodeURIComponent(search)}`;

  try {
    const data = await lisApi('GET', url);
    if (!isBackground) {
      document.getElementById('table-loading').style.display = 'none';
      document.getElementById('table-wrap').style.display    = 'block';
    }
    
    if (data.success) {
      const newOrders = data.data || [];
      
      // Notification logic for background polling
      if (isBackground && prevTotalOrders !== null && newOrders.length > prevTotalOrders) {
        lisToast('New Laboratory Test Ordered!', 'success');
        // If there's an existing sound file, you could play it here
      }
      prevTotalOrders = newOrders.length;
      
      allOrders = newOrders;
      renderOrders(allOrders);
    } else {
      if (!isBackground) renderOrders([]);
    }
  } catch(e) {
    if (!isBackground) {
      document.getElementById('table-loading').style.display = 'none';
      document.getElementById('table-wrap').style.display    = 'block';
      lisToast('Failed to load orders', 'error');
      renderOrders([]);
    }
  }
}

function renderOrders(orders) {
  const tbody = document.getElementById('orders-tbody');
  const empty = document.getElementById('orders-empty');
  document.getElementById('orders-count-badge').textContent = orders.length;

  // Stats
  const total    = orders.length;
  const pending  = orders.filter(o => o.status === 'Ordered').length;
  const progress = orders.filter(o => o.status === 'In Progress').length;
  const done     = orders.filter(o => o.status === 'Completed' || o.status === 'Reported').length;
  document.getElementById('stat-total').textContent   = total;
  document.getElementById('stat-pending').textContent  = pending;
  document.getElementById('stat-progress').textContent = progress;
  document.getElementById('stat-done').textContent     = done;

  if (!orders.length) {
    tbody.innerHTML = '';
    empty.style.display = 'flex';
    return;
  }
  empty.style.display = 'none';

  const statusMap = {
    'Ordered':     'b-ordered',
    'In Progress': 'b-progress',
    'Completed':   'b-completed',
    'Reported':    'b-reported'
  };
  const priorityMap = {
    'Urgent':  'b-urgent',
    'Stat':    'b-stat',
    'Routine': 'b-routine'
  };

  tbody.innerHTML = orders.map((o, i) => {
    const sCls = statusMap[o.status]   || 'b-ordered';
    const pCls = priorityMap[o.priority] || 'b-routine';
    const dt = o.order_date ? o.order_date.slice(5) : '';
    const tm = o.order_time ? o.order_time.slice(0,5) : '';
    const initials = (o.patient_name || 'P').split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase() || 'PT';

    let displayTestName = o.test_name;
    try {
      const parsed = JSON.parse(o.test_name);
      if (Array.isArray(parsed)) {
        displayTestName = parsed.join(', ');
      }
    } catch(e) {}

    return `<tr>
      <td style="color:var(--txt-mut);font-weight:700;font-size:.78rem;">${i+1}</td>
      <td>
        <code style="font-size:.72rem;background:var(--p-10);color:var(--p);padding:3px 8px;border-radius:6px;font-weight:800;">${escHtml(o.order_id)}</code>
      </td>
      <td>
        <div class="lo-pat-cell">
          <div class="lo-avatar">${escHtml(initials)}</div>
          <div>
            <div class="lo-pat-name">${escHtml(o.patient_name || '—')}</div>
            <div class="lo-pat-id">${escHtml(o.patient_id || '')}</div>
          </div>
        </div>
      </td>
      <td>
        <div style="font-weight:700;font-size:.82rem;color:var(--txt);max-width:180px;white-space:normal;word-wrap:break-word;line-height:1.3;">
          ${escHtml(displayTestName)}
        </div>
      </td>
      <td>
        <div style="font-size:.8rem;font-weight:600;color:var(--txt);max-width:140px;white-space:normal;word-wrap:break-word;">${escHtml(o.doctor_name || '—')}</div>
        <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(o.specialization||'')}</div>
      </td>
      <td><span class="lo-badge ${pCls}"><span class="lo-dot"></span>${escHtml(o.priority||'Routine')}</span></td>
      <td><span class="lo-badge ${sCls}"><span class="lo-dot"></span>${escHtml(o.status)}</span></td>
      <td style="font-size:.75rem;color:var(--txt-mut);white-space:nowrap;">${dt} ${tm}</td>
      <td style="text-align:center; vertical-align:middle;">
        <button class="lb lb-primary" style="padding: 4px 10px; font-size: 0.75rem; width:100%; font-weight:700; gap:5px;" title="Enter Results"
                onclick="openResultModal('${escHtml(o.order_id)}','${escHtml(displayTestName)}')">
          <i class="fas fa-file-medical-alt"></i> Data Entry
        </button>
      </td>
      <td>
        <div class="lo-actions">
          <button class="lo-ab lo-ab-ghost" title="Update Status"
                  onclick="openStatusModal('${escHtml(o.order_id)}','${escHtml(o.status)}')">
            <i class="fas fa-edit"></i>
          </button>
          <button class="lo-ab lo-ab-outline" title="Print Result"
             onclick="printOrderResult('${escHtml(o.order_id)}')">
            <i class="fas fa-print"></i>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

// ── Quick filters ────────────────────────────────────────────
function quickFilter(filter, el) {
  document.querySelectorAll('.lo-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  const statusEl   = document.getElementById('filter-status');
  const priorityEl = document.getElementById('filter-priority');
  statusEl.value   = '';
  priorityEl.value = '';
  if (filter === 'pending')   statusEl.value   = 'Ordered';
  if (filter === 'completed') statusEl.value   = 'Completed';
  if (filter === 'urgent')    priorityEl.value = 'Urgent';
  loadOrders();
}

function resetFilters() {
  document.getElementById('filter-date').value     = new Date().toISOString().slice(0,10);
  document.getElementById('filter-status').value   = '';
  document.getElementById('filter-priority').value = '';
  document.getElementById('filter-search').value   = '';
  quickFilter('today', document.querySelector('[data-filter="today"]'));
}

// ── Create Order Modal & UI State ─────────────────────────────
let orderMode = 'inpatient';
let selectedTests = [];
let availableTests = [];

function setOrderMode(mode) {
  orderMode = mode;
  if (mode === 'inpatient') {
    document.getElementById('btn-mode-inpatient').className = 'lb lb-primary';
    document.getElementById('btn-mode-walkin').className = 'lb lb-outline';
    document.getElementById('section-inpatient').style.display = 'block';
    document.getElementById('section-walkin').style.display = 'none';
  } else {
    document.getElementById('btn-mode-walkin').className = 'lb lb-primary';
    document.getElementById('btn-mode-inpatient').className = 'lb lb-outline';
    document.getElementById('section-inpatient').style.display = 'none';
    document.getElementById('section-walkin').style.display = 'block';
  }
}

function openCreateModal() {
  loadTestOptions();
  document.getElementById('createModal').classList.add('open');
}
function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
  clearPatientSelection();
  selectedTests = [];
  renderTestChips();
  document.getElementById('modal-test-search').value = '';
  document.getElementById('walkin-name').value = '';
  document.getElementById('walkin-age').value = '';
  document.getElementById('walkin-phone').value = '';
  document.getElementById('walkin-doctor').value = '';
}

async function loadTestOptions() {
  try {
    const data = await lisApi('GET', '/api/laboratory/services');
    if (data.success) {
      availableTests = [];
      if (data.data.lab?.length)       data.data.lab.forEach(t => availableTests.push(t.test_name));
      if (data.data.radiology?.length) data.data.radiology.forEach(t => availableTests.push(t.billing_name));
      if (data.data.other?.length)     data.data.other.forEach(t => availableTests.push(t.billing_name));
    }
  } catch(e) {
    console.error('Failed to load test options');
  }
}

// ── Test Multi-Select Logic ────────────────────────────────────
document.getElementById('modal-test-search').addEventListener('input', function() {
  const q = this.value.trim().toLowerCase();
  const res = document.getElementById('test-results');
  if (q.length < 1) { res.style.display = 'none'; return; }
  
  const matches = availableTests.filter(t => t.toLowerCase().includes(q) && (!selectedTests.includes(t)));
  if (!matches.length) {
    res.style.display = 'block';
    res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No matches found</div>';
    return;
  }
  
  res.style.display = 'block';
  res.innerHTML = matches.slice(0, 10).map(t => `
    <div onclick="addTest('${escHtml(t).replace(/'/g, "\\'")}')"
         style="padding:8px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);font-size:0.85rem;"
         onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''">
      ${escHtml(t)}
    </div>
  `).join('');
});

function addTest(testName) {
  if (!selectedTests.includes(testName)) {
    selectedTests.push(testName);
    renderTestChips();
  }
  document.getElementById('modal-test-search').value = '';
  document.getElementById('test-results').style.display = 'none';
}

function removeTest(testName) {
  selectedTests = selectedTests.filter(t => t !== testName);
  renderTestChips();
}

function renderTestChips() {
  const container = document.getElementById('selected-tests-container');
  container.innerHTML = selectedTests.map(t => `
    <span style="background:var(--p-10);color:var(--p);padding:6px 12px;border-radius:20px;font-size:.75rem;font-weight:600;border:1px solid var(--p-20);display:flex;align-items:center;gap:6px;">
      ${escHtml(t)}
      <i class="fas fa-times" style="cursor:pointer;opacity:0.7;" onclick="removeTest('${escHtml(t).replace(/'/g, "\\'")}')"></i>
    </span>
  `).join('');
}

// Hide dropdowns when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('#modal-test-search') && !e.target.closest('#test-results')) {
    document.getElementById('test-results').style.display = 'none';
  }
  if (!e.target.closest('#modal-patient-search') && !e.target.closest('#patient-results')) {
    document.getElementById('patient-results').style.display = 'none';
  }
});

// ── Patient Live Search ──────────────────────────────────────
let patientSearchTimer;
document.getElementById('modal-patient-search').addEventListener('input', function() {
  clearTimeout(patientSearchTimer);
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('patient-results').style.display = 'none'; return; }
  patientSearchTimer = setTimeout(() => searchPatients(q), 350);
});

async function searchPatients(q) {
  const res = document.getElementById('patient-results');
  res.style.display = 'block';
  res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">Searching...</div>';
  try {
    const data = await lisApi('GET', `/api/patients?search=${encodeURIComponent(q)}`);
    const patients = data.data?.data || data.data || data.patients || (Array.isArray(data) ? data : []);
    if (!patients.length) {
      res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No patients found</div>';
      return;
    }
    // We pass object as JSON string safely
    res.innerHTML = patients.slice(0,8).map(p => {
       const jStr = encodeURIComponent(JSON.stringify(p));
       return `
      <div onclick="selectPatient('${jStr}')"
           style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);transition:background .15s;"
           onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''">
        <div style="font-weight:700;font-size:.82rem;color:var(--txt);">${escHtml((p.first_name||'') + ' ' + (p.last_name||''))}</div>
        <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(p.patient_id)} &bull; ${p.age||'?'}y &bull; ${p.sex||'?'} &bull; ${escHtml(p.phone||'')}</div>
      </div>`;
    }).join('');
  } catch(e) {
    res.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:#ef4444;">Error searching patients</div>';
  }
}

function selectPatient(jsonStr) {
  const p = JSON.parse(decodeURIComponent(jsonStr));
  document.getElementById('modal-patient-id').value = p.patient_id;
  document.getElementById('modal-doctor-id').value = p.doctor_id || '';
  document.getElementById('modal-patient-search').value = '';
  document.getElementById('patient-results').style.display = 'none';
  
  document.getElementById('sp-name').textContent = (p.first_name||'') + ' ' + (p.last_name||'');
  document.getElementById('sp-meta').textContent = `${p.patient_id} • ${p.age||'?'}y • ${p.sex||'?'}`;
  document.getElementById('sp-phone').textContent = p.phone || 'N/A';
  // Use generic 'Hospital Doctor' if not available in patients response
  document.getElementById('sp-ref').textContent = p.doctor_name || 'Hospital Staff'; 
  
  document.querySelector('.lis-search-wrap').style.display = 'none';
  document.getElementById('selected-patient-profile').style.display = 'block';
}

function clearPatientSelection() {
  document.getElementById('modal-patient-id').value = '';
  document.getElementById('modal-patient-search').value = '';
  document.getElementById('patient-results').style.display = 'none';
  document.querySelector('.lis-search-wrap').style.display = 'flex';
  document.getElementById('selected-patient-profile').style.display = 'none';
}

// ── Submit Order ──────────────────────────────────────────────
async function createOrder() {
  const btn = document.getElementById('createOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Sending...';
  
  try {
    let patientId = '';
    let notes = document.getElementById('modal-notes').value.trim();
    
    // 1. Process Patient based on mode
    if (orderMode === 'inpatient') {
      patientId = document.getElementById('modal-patient-id').value.trim();
      if (!patientId) { lisToast('Please select a patient', 'warning'); btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; return; }
    } else {
      // Walkin Patient (No database registration)
      const name = document.getElementById('walkin-name').value.trim();
      const age = document.getElementById('walkin-age').value.trim();
      const phone = document.getElementById('walkin-phone').value.trim();
      const refDoctor = document.getElementById('walkin-doctor').value.trim();
      
      if (!name || !age || !phone) {
        lisToast('Please fill Name, Age and Phone for Walk-in', 'warning');
        btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; return;
      }
      
      // Use a temporary ID for Walk-in orders
      patientId = 'WLK-' + Date.now().toString().slice(-6);
    }

    if (selectedTests.length === 0) { 
       lisToast('Please select at least one test', 'warning'); 
       btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Order'; 
       return; 
    }

    const priority = document.getElementById('modal-priority').value;
    
    // For walk-in, pack the patient details into the patient_type field (max 150 chars)
    let patientType = 'In-patient';
    let docId = null;
    
    if (orderMode === 'walkin') {
      const wName = document.getElementById('walkin-name').value.trim().replace(/\|/g, '');
      const wAge = document.getElementById('walkin-age').value.trim().replace(/\|/g, '');
      const wPhone = document.getElementById('walkin-phone').value.trim().replace(/\|/g, '');
      patientType = `Walkin:${wName}|${wAge}|${wPhone}`;
      docId = document.getElementById('walkin-doctor').value.trim();
    } else {
      const storedDocId = document.getElementById('modal-doctor-id').value;
      if (storedDocId) docId = storedDocId;
    }
    
    const oData = await lisApi('POST', '/api/laboratory/orders', {
      patient_id: patientId,
      doctor_id:  docId, 
      test_name:  JSON.stringify(selectedTests),
      priority:   priority,
      notes:      notes,
      patient_type: patientType
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Order';

    if (oData.success || oData.order_id) {
      lisToast('Successfully created lab order', 'success');
      closeCreateModal();
      loadOrders();
    } else {
      lisToast('Failed to create orders', 'error');
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Order';
    lisToast(e.message || 'Error occurred while creating order', 'error');
  }
}
// ── Status Modal ─────────────────────────────────────────────
function openStatusModal(orderId, currentStatus) {
  activeOrderId = orderId;
  document.getElementById('status-order-id').textContent = orderId;
  document.getElementById('status-select').value         = currentStatus;
  document.getElementById('statusModal').classList.add('open');
}
function closeStatusModal() {
  document.getElementById('statusModal').classList.remove('open');
  activeOrderId = null;
}

async function saveStatus() {
  if (!activeOrderId) return;
  const status = document.getElementById('status-select').value;
  try {
    const data = await lisApi('PUT', `/api/laboratory/orders/${encodeURIComponent(activeOrderId)}/status`, { status });
    if (data.success) {
      lisToast('Status updated successfully', 'success');
      closeStatusModal();
      loadOrders();
    } else {
      lisToast(data.message || 'Failed to update status', 'error');
    }
  } catch(e) {
    lisToast('Network error', 'error');
  }
}

// ── Helpers ──────────────────────────────────────────────────
function escHtml(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/\n/g,' ').replace(/\r/g,'');
}

// Close modals on overlay click
document.querySelectorAll('.lis-modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// Keyboard shortcut N = new order
document.addEventListener('keydown', e => {
  if (e.key === 'n' && !e.ctrlKey && !e.metaKey &&
      document.activeElement.tagName !== 'INPUT' &&
      document.activeElement.tagName !== 'TEXTAREA') {
    openCreateModal();
  }
  if (e.key === 'Escape') {
    document.querySelectorAll('.lis-modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

// Auto-polling for new tests every 15 seconds
setInterval(() => {
  // Don't poll if a modal is open to prevent UI disruption
  if (document.querySelector('.lis-modal-overlay.open')) return;
  loadOrders(true);
}, 15000);

// Initial load
loadOrders();

// ══════════════════════════════════════════════════════════════
//  RESULT ENTRY MODAL
// ══════════════════════════════════════════════════════════════
let activeResultOrderId = null;
let rmAttachments = [];          // { dataUrl, blob, name, source }
let cameraStream  = null;
let allServiceTests = [];        // populated from API

// ── Load all service tests (lab + radiology + other) ─────────
async function loadAllServiceTests() {
  if (allServiceTests.length) return;
  try {
    const data = await lisApi('GET', '/api/laboratory/services');
    if (data.success) {
      (data.data.lab       || []).forEach(t => allServiceTests.push({ name: t.test_name,    cat: 'lab' }));
      (data.data.radiology || []).forEach(t => allServiceTests.push({ name: t.billing_name, cat: 'rad' }));
      (data.data.other     || []).forEach(t => allServiceTests.push({ name: t.billing_name, cat: 'oth' }));
    }
  } catch(e) { /* silent */ }
}

// ── Test search dropdown ──────────────────────────────────────
function searchTestServices(q) {
  const dd = document.getElementById('test-search-dd');
  
  const ql = (q || '').trim().toLowerCase();
  
  let hits = [];
  if (ql) {
    hits = allServiceTests.filter(t => t.name.toLowerCase().includes(ql)).slice(0, 30);
  } else {
    hits = allServiceTests.slice(0, 60); // Show top 60 tests when clicking empty search
  }

  if (!hits.length && !ql) { dd.style.display = 'none'; return; }
  
  let html = '';
  if (ql) {
    html += `<div class="test-dd-item" onclick="selectSearchTest('${escHtml(q.trim())}')">
      <span class="test-dd-badge oth" style="background:var(--p);color:white;border-color:var(--p-dk);">Custom</span>
      <strong>Use manual test: "${escHtml(q.trim())}"</strong>
    </div>`;
  }
  
  if (hits.length) {
    const catLabels = { lab: 'Lab Services', rad: 'Radiology', oth: 'Other Services' };
    const badgeCls  = { lab: 'lab', rad: 'rad', oth: 'oth' };
    ['lab','rad','oth'].forEach(cat => {
      const catHits = hits.filter(h => h.cat === cat);
      if (!catHits.length) return;
      html += `<div class="test-dd-cat">${catLabels[cat]}</div>`;
      catHits.forEach(h => {
        html += `<div class="test-dd-item" onclick="selectSearchTest('${escHtml(h.name)}')"><span class="test-dd-badge ${badgeCls[h.cat]}">${catLabels[cat]}</span>${escHtml(h.name)}</div>`;
      });
    });
  }
  
  dd.innerHTML = html;
  dd.style.display = 'block';
}

function selectSearchTest(name) {
  document.getElementById('test-search-input').value = ''; // clear input after selection
  document.getElementById('test-search-dd').style.display = 'none';
  // We no longer change result-test-name, to allow appending extra tests to the primary order
  
  // Find category
  const match = allServiceTests.find(st => st.name === name);
  const cat = match ? match.cat : 'lab';
  
  // Auto-detect template
  const tn = name.toLowerCase();
  let prefill = '';
  if (tn.includes('cbc') || tn.includes('blood count'))  prefill = 'cbc';
  else if (tn.includes('lft') || tn.includes('liver'))   prefill = 'lft';
  else if (tn.includes('lipid'))                         prefill = 'lipid';
  else if (tn.includes('kft') || tn.includes('kidney'))  prefill = 'kft';
  else if (tn.includes('tft') || tn.includes('thyroid')) prefill = 'tft';
  if (prefill) {
    document.getElementById('template-select').value = prefill;
    loadTemplate(prefill);
  } else {
    document.getElementById('template-select').value = '';
    addResultRow(name, '', '', cat);
  }
}

// Close test search dd on outside click
document.addEventListener('click', e => {
  if (!e.target.closest('.test-search-wrap')) {
    const dd = document.getElementById('test-search-dd');
    if (dd) dd.style.display = 'none';
  }
});

// ── Patient Mode Toggle ───────────────────────────────────────
function switchPatientMode(mode) {
  const isWalkin = mode === 'walkin';
  document.getElementById('btn-walkin').classList.toggle('active', isWalkin);
  document.getElementById('btn-registered').classList.toggle('active', !isWalkin);
  document.getElementById('walkin-form').style.display     = isWalkin ? 'block' : 'none';
  document.getElementById('registered-form').style.display = isWalkin ? 'none'  : 'block';
}

// ── Registered patient search (inside result modal) ───────────
let rmPatientTimer;
function rmSearchPatient(q) {
  const dd = document.getElementById('rm-patient-dd');
  clearTimeout(rmPatientTimer);
  if (!q || q.length < 2) { dd.style.display = 'none'; return; }
  rmPatientTimer = setTimeout(async () => {
    dd.style.display = 'block';
    dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">Searching...</div>';
    try {
      const data = await lisApi('GET', `/api/patients?search=${encodeURIComponent(q)}`);
      const patients = data.data || data.patients || (Array.isArray(data) ? data : []);
      if (!patients.length) { dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:var(--txt-mut);">No patients found</div>'; return; }
      dd.innerHTML = patients.slice(0,8).map(p => {
        const name = `${p.first_name||''} ${p.last_name||''}`.trim();
        return `<div style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--bdr);transition:background .15s;" onmouseover="this.style.background='var(--p-05)'" onmouseout="this.style.background=''" onclick="selectRmPatient('${escHtml(p.patient_id)}','${escHtml(name)}','${p.age||''}','${escHtml(p.sex||'')}','${escHtml(p.phone||'')}')">
          <div style="font-weight:700;font-size:.82rem;color:var(--txt);">${escHtml(name)}</div>
          <div style="font-size:.68rem;color:var(--txt-mut);">${escHtml(p.patient_id)} &bull; ${p.age||'?'}y &bull; ${p.sex||'?'} &bull; ${escHtml(p.phone||'')}</div>
        </div>`;
      }).join('');
    } catch(e) {
      dd.innerHTML = '<div style="padding:10px 14px;font-size:.78rem;color:#ef4444;">Error</div>';
    }
  }, 300);
}

function selectRmPatient(id, name, age, sex, phone) {
  document.getElementById('rm-patient-id').value          = id;
  document.getElementById('rm-patient-dd').style.display  = 'none';
  document.getElementById('rm-patient-search').value      = name;
  document.getElementById('rm-patient-avatar').textContent = name.split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase();
  document.getElementById('rm-patient-name-disp').textContent = name;
  document.getElementById('rm-patient-meta').textContent  = `${id} · ${age||'?'}y · ${sex||'?'} · ${phone||'N/A'}`;
  document.getElementById('rm-patient-card').style.display = 'block';
}

function clearRmPatient() {
  document.getElementById('rm-patient-id').value           = '';
  document.getElementById('rm-patient-search').value       = '';
  document.getElementById('rm-patient-card').style.display = 'none';
}

// ── Attachment Handling ───────────────────────────────────────
function handleAttachFile(input, source) {
  const files = Array.from(input.files);
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const dataUrl = e.target.result;
      rmAttachments.push({ dataUrl, blob: file, name: file.name, source });
      renderAttachGrid();
    };
    reader.readAsDataURL(file);
  });
  input.value = '';
}

function renderAttachGrid() {
  const grid  = document.getElementById('att-grid');
  const empty = document.getElementById('att-empty');
  if (!rmAttachments.length) {
    grid.innerHTML  = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';
  grid.innerHTML = rmAttachments.map((a, i) => {
    const isImg = a.dataUrl.startsWith('data:image');
    const thumb = isImg
      ? `<img class="att-thumb" src="${a.dataUrl}" title="${escHtml(a.name)}" onclick="previewAttach(${i})">`
      : `<div class="att-thumb" style="display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--s);color:var(--p);font-size:.6rem;font-weight:700;padding:4px;text-align:center;" title="${escHtml(a.name)}"><i class="fas fa-file-pdf" style="font-size:1.5rem;margin-bottom:4px;"></i>${escHtml(a.name.substring(0,8))}...</div>`;
    return `<div class="att-wrap">${thumb}<button class="att-del" onclick="removeAttach(${i})" title="Remove"><i class="fas fa-times"></i></button></div>`;
  }).join('');
}

function removeAttach(i) {
  rmAttachments.splice(i, 1);
  renderAttachGrid();
}

function previewAttach(i) {
  const a = rmAttachments[i];
  if (!a) return;
  const w = window.open();
  w.document.write(`<img src="${a.dataUrl}" style="max-width:100%;height:auto;">`);
}

// ── Camera ────────────────────────────────────────────────────
async function openCameraModal() {
  try {
    cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    document.getElementById('cameraVideo').srcObject = cameraStream;
    document.getElementById('cameraModal').classList.add('show');
  } catch(e) {
    lisToast('Camera access denied or not available', 'error');
  }
}

function closeCameraModal() {
  if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
  document.getElementById('cameraModal').classList.remove('show');
}

function capturePhoto() {
  const video  = document.getElementById('cameraVideo');
  const canvas = document.getElementById('cameraCanvas');
  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  const dataUrl = canvas.toDataURL('image/jpeg', .85);
  canvas.toBlob(blob => {
    rmAttachments.push({ dataUrl, blob, name: `capture_${Date.now()}.jpg`, source: 'camera' });
    renderAttachGrid();
  }, 'image/jpeg', .85);
  closeCameraModal();
  lisToast('Photo captured!', 'success');
}

// ── Smart templates ───────────────────────────────────────────
const testTemplates = {
  cbc: [
    { name: 'Hemoglobin',       unit: 'g/dL',       range: '12.0 - 16.0' },
    { name: 'RBC Count',        unit: 'mill/cu.mm',  range: '4.5 - 5.5'   },
    { name: 'WBC Count',        unit: '/c.mm',       range: '4000 - 11000' },
    { name: 'Platelets',        unit: 'lakhs/c.mm',  range: '1.5 - 4.5'   },
    { name: 'Hematocrit (PCV)', unit: '%',           range: '36 - 46'      }
  ],
  lft: [
    { name: 'Total Bilirubin',      unit: 'mg/dL', range: '0.1 - 1.2'  },
    { name: 'Direct Bilirubin',     unit: 'mg/dL', range: '0.0 - 0.3'  },
    { name: 'SGOT (AST)',           unit: 'U/L',   range: '0 - 40'      },
    { name: 'SGPT (ALT)',           unit: 'U/L',   range: '0 - 40'      },
    { name: 'Alkaline Phosphatase', unit: 'U/L',   range: '40 - 129'    }
  ],
  lipid: [
    { name: 'Total Cholesterol', unit: 'mg/dL', range: '< 200' },
    { name: 'Triglycerides',     unit: 'mg/dL', range: '< 150' },
    { name: 'HDL Cholesterol',   unit: 'mg/dL', range: '> 40'  },
    { name: 'LDL Cholesterol',   unit: 'mg/dL', range: '< 100' },
    { name: 'VLDL Cholesterol',  unit: 'mg/dL', range: '5 - 40'}
  ],
  kft: [
    { name: 'Blood Urea',       unit: 'mg/dL',  range: '15 - 40'   },
    { name: 'Serum Creatinine', unit: 'mg/dL',  range: '0.6 - 1.2' },
    { name: 'Uric Acid',        unit: 'mg/dL',  range: '3.4 - 7.0' },
    { name: 'Sodium',           unit: 'mEq/L',  range: '135 - 145' },
    { name: 'Potassium',        unit: 'mEq/L',  range: '3.5 - 5.1' }
  ],
  tft: [
    { name: 'T3 (Triiodothyronine)', unit: 'ng/dL',  range: '70 - 204'   },
    { name: 'T4 (Thyroxine)',        unit: 'ug/dL',  range: '4.5 - 11.2' },
    { name: 'TSH',                   unit: 'uIU/mL', range: '0.4 - 4.2'  }
  ]
};

function loadTemplate(templateKey) {
  if (!templateKey) return;
  (testTemplates[templateKey] || []).forEach(f => addResultRow(f.name, f.unit, f.range));
}

async function openResultModal(orderId, testName) {
  await loadAllServiceTests(); // Load 3-table data if not loaded
  activeResultOrderId = orderId;
  rmAttachments = [];
  document.getElementById('result-order-id').textContent  = orderId;
  document.getElementById('result-test-name').textContent = testName;

  // Reset attachment grid
  renderAttachGrid();

  // Patient form removed

  // Reset test search
  document.getElementById('test-search-input').value = '';
  document.getElementById('test-search-dd').style.display = 'none';
  document.getElementById('template-select').value = '';
  document.getElementById('result-params-tbody').innerHTML = '';

  // Auto-detect template from multiple assigned tests
  const testArray = String(testName).split(',').map(t => t.trim()).filter(t => t);
  if (testArray.length === 0) testArray.push('');

  testArray.forEach(tName => {
    const match = allServiceTests.find(st => st.name.toLowerCase() === tName.toLowerCase());
    const cat = match ? match.cat : 'lab';

    const tn = tName.toLowerCase();
    let prefill = '';
    if (tn.includes('cbc') || tn.includes('blood count'))       prefill = 'cbc';
    else if (tn.includes('lft') || tn.includes('liver'))        prefill = 'lft';
    else if (tn.includes('lipid'))                              prefill = 'lipid';
    else if (tn.includes('kft') || tn.includes('kidney'))       prefill = 'kft';
    else if (tn.includes('tft') || tn.includes('thyroid'))      prefill = 'tft';

    if (prefill) {
      document.getElementById('template-select').value = prefill;
      loadTemplate(prefill);
    } else {
      addResultRow(tName, '', '', cat);
    }
  });

  document.getElementById('resultModal').classList.add('open');
}

function closeResultModal() {
  document.getElementById('resultModal').classList.remove('open');
  activeResultOrderId = null;
}

function addResultRow(name = '', unit = '', range = '', category = 'lab') {
  const tbody = document.getElementById('result-params-tbody');
  const tr    = document.createElement('tr');
  tr.className = 'row-anim';

  if (category === 'rad' || category === 'oth') {
    const label = category === 'rad' ? 'Radiology Report' : 'Service Notes';
    const icon = category === 'rad' ? 'fa-x-ray' : 'fa-clipboard-list';
    tr.innerHTML = `
      <td colspan="5" style="padding:16px;background:var(--s);border-bottom:1px solid var(--bdr);">
        <div style="font-size:.85rem;font-weight:700;color:var(--p);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;">
          <span><i class="fas ${icon}" style="margin-right:6px;"></i>${escHtml(name)} (${label})</span>
          <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" onclick="document.getElementById('result-report-file').click()" style="background:var(--p-10);color:var(--p);border:1px solid var(--p-30);padding:4px 10px;border-radius:var(--r-sm);font-size:.72rem;font-weight:700;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='var(--p)';this.style.color='white';" onmouseout="this.style.background='var(--p-10)';this.style.color='var(--p)';"><i class="fas fa-paperclip" style="margin-right:4px;"></i> Attach File</button>
            <button type="button" class="rm-del-btn" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button>
          </div>
        </div>
        <input type="hidden" name="param_name" value="${escHtml(name)}">
        <textarea class="rm-pinput" name="param_value" placeholder="Type your ${label.toLowerCase()} or findings here..." style="width:100%;height:80px;resize:vertical;padding:12px;"></textarea>
        <input type="hidden" name="param_unit" value="">
        <input type="hidden" name="param_range" value="">
      </td>`;
    tbody.appendChild(tr);
    return;
  }

  // Live value check
  const checkVal = (input) => {
    const v = parseFloat(input.value);
    if (isNaN(v) || !range) { input.className = 'rm-pinput p-result'; return; }
    const parts = range.replace(/[<>]/g,'').trim().split('-').map(p => parseFloat(p.trim()));
    if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
      if (v < parts[0])      input.className = 'rm-pinput p-result p-low';
      else if (v > parts[1]) input.className = 'rm-pinput p-result p-high';
      else                   input.className = 'rm-pinput p-result p-normal';
    }
  };

  tr.innerHTML = `
    <td><input type="text" class="rm-pinput" placeholder="Parameter name" name="param_name" value="${escHtml(name)}"></td>
    <td><input type="text" class="rm-pinput p-result" placeholder="Value" name="param_value"></td>
    <td><input type="text" class="rm-pinput p-unit" placeholder="Unit" name="param_unit" value="${escHtml(unit)}"></td>
    <td><input type="text" class="rm-pinput p-range" placeholder="Range" name="param_range" value="${escHtml(range)}"></td>
    <td style="text-align:center;">
      <button type="button" class="rm-del-btn" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button>
    </td>`;
  const valInp = tr.querySelector('[name="param_value"]');
  if (valInp) {
    valInp.addEventListener('input', function() { checkVal(this); });
    valInp.addEventListener('keydown', function(e) {
      if ((e.key === 'Tab' && !e.shiftKey) || e.key === 'Enter') {
        e.preventDefault();
        const rows = Array.from(document.querySelectorAll('#result-params-tbody tr'));
        const idx = rows.indexOf(this.closest('tr'));
        if (idx >= 0 && idx < rows.length - 1) {
          const next = rows[idx + 1].querySelector('[name="param_value"]');
          if (next) next.focus();
        }
      } else if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        const rows = Array.from(document.querySelectorAll('#result-params-tbody tr'));
        const idx = rows.indexOf(this.closest('tr'));
        if (idx > 0) {
          const prev = rows[idx - 1].querySelector('[name="param_value"]');
          if (prev) prev.focus();
        }
      }
    });
  }
  tbody.appendChild(tr);
}

async function saveResult() {
  if (!activeResultOrderId) return;

  const rows   = document.querySelectorAll('#result-params-tbody tr');
  const params = [];
  rows.forEach(tr => {
    const name = tr.querySelector('[name="param_name"]').value.trim();
    if (name) {
      params.push({
        name,
        value: tr.querySelector('[name="param_value"]').value.trim(),
        unit:  tr.querySelector('[name="param_unit"]').value.trim(),
        range: tr.querySelector('[name="param_range"]').value.trim(),
      });
    }
  });

  if (params.length === 0 && rmAttachments.length === 0) {
    lisToast('Please enter at least one parameter or add an attachment', 'warning');
    return;
  }

  // Patient info panel removed, only send test name
  let patientInfo = {
    test_name: document.getElementById('test-search-input') ? document.getElementById('test-search-input').value.trim() : ''
  };

  const btn = document.getElementById('saveResultBtn');
  btn.disabled  = true;
  btn.innerHTML = '<div class="lis-spinner"></div> Saving...';

  try {
    const formData = new FormData();
    formData.append('result_data', JSON.stringify(params));
    formData.append('patient_info', JSON.stringify(patientInfo));
    
    // attach all files
    rmAttachments.forEach((a, i) => {
      // The backend LaboratoryController expects $_FILES['report_file']
      const key = (i === 0) ? 'report_file' : `report_file_${i}`;
      formData.append(key, a.blob, a.name);
    });
    // legacy key for compatibility
    if (rmAttachments.length) formData.append('report_file', rmAttachments[0].blob, rmAttachments[0].name);

    const data = await lisApi('POST', `/api/laboratory/orders/${encodeURIComponent(activeResultOrderId)}/result`, formData);

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Final Results';

    if (data.success) {
      lisToast('Results saved successfully!', 'success');
      closeResultModal();
      loadOrders();
    } else {
      lisToast(data.message || 'Error saving results', 'error');
    }
  } catch(e) {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Final Results';
    lisToast(e.message || 'Failed to save results', 'error');
  }
}
function printOrderResult(orderId) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'print_result.php';
  
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'order_id';
  input.value = orderId;
  
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}
</script>
