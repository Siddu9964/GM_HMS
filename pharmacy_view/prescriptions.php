<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
require_once 'includes/db.php';
$pageTitle = 'Prescription Archive';

$docRoot     = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$fullPath    = str_replace('\\', '/', dirname(__DIR__));
$projectRoot = str_ireplace($docRoot, '', $fullPath);
$apiBase     = rtrim($projectRoot, '/') . '/api/';

include 'includes/ph_head.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════
   RX ARCHIVE  —  Old Teal · Three Views + Individual Print
   ═══════════════════════════════════════════════════════════ */
:root {
  --bg:        #F0F4F8;
  --surface:   #FFFFFF;
  --surface-2: #F8FAFC;
  --p-dark:    #011c22;
  --p-mid:     #024D55;
  --p-base:    #1f6b4a;
  --p-light:   #5DE8F0;
  --p-pale:    #E0F7FA;
  --p-dim:     rgba(31, 107, 74,.10);
  --p-dim2:    rgba(31, 107, 74,.18);
  --s-indigo:  #4F46E5;
  --s-emerald: #059669;
  --s-amber:   #D97706;
  --s-rose:    #E11D48;
  --t1: #0F172A; --t2: #334155; --t3: #64748B; --t4: #94A3B8;
  --border:    #E2E8F0;
  --border-2:  #CBD5E1;
  --shadow-sm: 0 1px 4px rgba(0,0,0,.07);
  --shadow:    0 4px 18px rgba(0,0,0,.09);
  --shadow-lg: 0 12px 40px rgba(0,0,0,.12);
  --radius:    12px; --radius-lg: 18px; --radius-sm: 8px;
  --ease:      .22s cubic-bezier(.4,0,.2,1);
  --font:      'Inter', sans-serif;
  --mono:      'JetBrains Mono', monospace;
}

/* ── Base ────────────────────────────────────────────────── */
.ph-page-body { background:var(--bg)!important; font-family:var(--font)!important; color:var(--t1)!important; padding:0!important; min-height:100vh; }
.rx-wrap { display:flex; flex-direction:column; min-height:100vh; }

/* ════════════════════════════════════════════════════════════
   PRINT STYLES  —  Only #rx-print-zone visible when printing
   ════════════════════════════════════════════════════════════ */
#rx-print-zone { display:none; }
@media print {
  /* Hide the main UI completely */
  body > *:not(#rx-print-zone) { display: none !important; }
  .wrapper, .content-wrapper, .main-header, .main-sidebar, .ph-wrap { display: none !important; }

  /* Ensure the body has no extra margins or heights */
  body, html {
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    min-height: auto !important;
    background: #fff !important;
  }

  /* Show and position the print zone */
  #rx-print-zone {
    display: block !important;
    position: relative !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
  }
  
  @page { margin: 10mm; size: A4 portrait; }
}

/* ═══ ADVANCED MODERN RX SLIP STYLES ═══════════════════════ */
.rx-slip { font-family:'Inter', 'Segoe UI', Arial, sans-serif; color:#0F172A; font-size:11px; max-width:100%; position:relative; border: 2px solid #E2E8F0; border-radius: 8px; background: #fff; overflow: hidden; }

/* Top accent */
.rx-slip-top { height:8px; background:var(--p-base); }

/* Slip Content padding */
.rx-slip-content { padding: 15px 25px 25px; }

/* Header */
.rx-header-adv { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid #E2E8F0; }
.rx-header-left h1 { margin:0 0 6px; font-size:28px; font-weight:900; color:#011c22; letter-spacing:-0.5px; line-height:1; }
.rx-header-left p { margin:0; font-size:10px; color:#64748B; line-height:1.5; }
.rx-header-left p i { color:#1f6b4a; margin-right:4px; }
.rx-header-right { text-align:right; }
.rx-barcode { font-family:'Courier New', monospace; font-size:28px; line-height:1; color:#CBD5E1; margin-bottom:6px; font-weight:100; letter-spacing:-2px; }
.rx-num-adv { font-size:13px; font-weight:800; color:#011c22; letter-spacing:0.5px; }
.rx-num-adv span { color:#1f6b4a; }
.rx-meta-adv { font-size:9px; color:#94A3B8; margin-top:4px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }

/* Info Grid */
.rx-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
.rx-info-box { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:14px 18px; position:relative; overflow:hidden; }
.rx-info-box::before { content:''; position:absolute; top:0; left:0; bottom:0; width:5px; background:#1f6b4a; }
.rx-info-box.doc-box::before { background:#024D55; }
.rx-box-title { font-size:8px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#94A3B8; margin-bottom:10px; display:flex; align-items:center; gap:5px; }
.rx-box-name { font-size:16px; font-weight:900; color:#011c22; margin-bottom:8px; }
.rx-box-grid { display:grid; grid-template-columns:1fr; gap:4px; font-size:9.5px; color:#475569; }
.rx-box-grid span { color:#94A3B8; display:inline-block; width:65px; font-weight:600; }
.rx-box-grid strong { color:#0F172A; font-weight:700; }

/* Micro details row */
.rx-micro-row { display:flex; justify-content:space-between; background:#F1F5F9; border-radius:10px; padding:12px 20px; margin-bottom:25px; }
.rx-micro-item { display:flex; flex-direction:column; gap:3px; }
.rx-micro-l { font-size:7.5px; font-weight:800; text-transform:uppercase; color:#94A3B8; letter-spacing:0.08em; }
.rx-micro-v { font-size:11.5px; font-weight:700; color:#0F172A; }

/* Section Title */
.rx-sec-title { display:flex; align-items:center; gap:12px; margin-bottom:10px; margin-top:10px; }
.rx-sec-title h3 { margin:0; font-size:12px; font-weight:900; color:#011c22; text-transform:uppercase; letter-spacing:0.08em; display:flex; align-items:center; }
.rx-sec-title .line { flex:1; height:1px; background:linear-gradient(90deg, #E2E8F0, transparent); }
.rx-sec-icon { font-family:Georgia,serif; font-size:24px; font-weight:bold; color:#1f6b4a; line-height:0.8; margin-right:8px; opacity:0.8; }

/* Modern Table */
.rx-mod-tbl { width:100%; border-collapse:separate; border-spacing:0; margin-bottom:8px; }
.rx-mod-tbl th { padding:10px 12px; text-align:left; font-size:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:#94A3B8; border-bottom:2px solid #E2E8F0; }
.rx-mod-tbl td { padding:12px; font-size:10.5px; color:#334155; border-bottom:1px solid #F1F5F9; vertical-align:middle; }
.rx-mod-tbl tr:last-child td { border-bottom:none; }
.rx-mod-tbl .med-idx { font-weight:900; color:#1f6b4a; font-size:11px; }
.rx-mod-tbl .med-name { font-weight:800; color:#0F172A; font-size:12.5px; display:block; margin-bottom:2px; }
.rx-mod-tbl .med-badge { display:inline-flex; align-items:center; justify-content:center; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; padding:3px 8px; font-size:9.5px; font-weight:700; color:#475569; }
.rx-mod-tbl .med-dur { font-weight:700; color:#024D55; }
.rx-note { font-size:9px; color:#64748B; font-style:italic; font-weight:500; background:#F8FAFC; padding:8px 12px; border-radius:6px; display:inline-block; border-left:3px solid #CBD5E1; margin-bottom:5px; }

/* Image */
.rx-img-box { background:#F8FAFC; border:1px dashed #CBD5E1; border-radius:10px; padding:10px; display:inline-block; }
.rx-img-box img { max-width:280px; max-height:240px; object-fit:contain; border-radius:6px; display:block; }

/* Watermark */
.rx-watermark-adv { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); font-size:350px; font-family:Georgia,serif; font-weight:bold; color:rgba(31, 107, 74,0.03); z-index:-1; pointer-events:none; }

/* Signatures */
.rx-sigs-mod { display:flex; justify-content:space-between; margin-top:20px; padding-top:15px; }
.rx-sig-block { display:flex; flex-direction:column; align-items:center; width:28%; }
.rx-sig-line { width:100%; height:0; border-top:1px dashed #94A3B8; margin-bottom:10px; margin-top:35px; }
.rx-sig-text { font-size:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#64748B; text-align:center; }
.rx-stamp-box { border:2px solid #E2E8F0; border-radius:8px; width:100px; height:45px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; }
.rx-stamp-text { font-size:8px; font-weight:800; color:#CBD5E1; letter-spacing:1px; }

/* Footer */
.rx-foot-mod { margin-top:40px; padding-top:15px; border-top:1px solid #E2E8F0; display:flex; justify-content:space-between; font-size:8.5px; color:#94A3B8; align-items:center; }
.rx-foot-mod strong { color:#1f6b4a; font-weight:800; font-size:10px; }
.rx-validity-badge { background:#FFFBEB; border:1px solid #FDE68A; color:#92400E; padding:4px 10px; border-radius:99px; font-weight:700; font-size:8.5px; display:inline-flex; align-items:center; gap:4px; }

/* ════════════════════════════════════════════════════════════
   HEADER
   ════════════════════════════════════════════════════════════ */
.rx-header {
  background:#f3efe6;
  padding:1rem 1.75rem 1.5rem; position:relative; overflow:hidden;
}
.rx-header::before, .rx-header::after { display: none; }
.rx-hinner { position:relative;z-index:1; }
.rx-htag { display:inline-flex;align-items:center;gap:.4rem;background:rgba(31,107,74,.1);border:1px solid rgba(31,107,74,.2);padding:.28rem .85rem;border-radius:99px;font-size:.6rem;font-weight:700;color:#1f6b4a;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem; }
.rx-ht  { font-size:1.5rem;font-weight:900;color:#0f172a;letter-spacing:-.5px;line-height:1.1;margin-bottom:.2rem; }
.rx-ht em { font-style:normal;color:#1f6b4a; }
.rx-hsub { font-size:.75rem;color:#64748b;max-width:460px;line-height:1.5;margin-bottom:1rem; }
.rx-hstats { display:flex;gap:1rem;flex-wrap:wrap; }
.rxhs { background:#fff;border:1px solid #e2e8f0;border-radius:var(--radius);padding:.65rem 1rem;min-width:100px;box-shadow: 0 2px 8px rgba(15,23,42,.05);transition:var(--ease); }
.rxhs:hover { box-shadow: 0 4px 12px rgba(15,23,42,.08); transform: translateY(-2px); }
.rxhs-v { font-size:1.35rem;font-weight:900;color:#0f172a;line-height:1; }
.rxhs-l { font-size:.55rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;margin-top:.2rem; }

/* ════════════════════════════════════════════════════════════
   CONTROL BAR  (sticky)
   ════════════════════════════════════════════════════════════ */
.rx-cbar {
  position:sticky; top:0; z-index:200;
  background:var(--surface); border-bottom:2px solid var(--p-base);
  padding:.65rem 2rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
  box-shadow:var(--shadow-sm);
}

/* Search */
.rx-srch-wrap { flex:1; min-width:180px; position:relative; }
.rx-srch-wrap i { position:absolute;left:.82rem;top:50%;transform:translateY(-50%);color:var(--t4);font-size:.78rem;pointer-events:none;transition:var(--ease); }
.rx-srch-wrap:focus-within i { color:var(--p-base); }
.rx-srch { width:100%;padding:.55rem .85rem .55rem 2.25rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:var(--font);font-size:.82rem;font-weight:500;color:var(--t1);background:var(--surface-2);outline:none;transition:var(--ease); }
.rx-srch::placeholder { color:var(--t4); }
.rx-srch:focus { border-color:var(--p-base);background:#fff;box-shadow:0 0 0 3px var(--p-dim); }

/* ─── NEW USEFUL FILTERS ───────────────────────────────── */
/* Doctor filter */
.rx-doc-filter {
  padding:.52rem .82rem; border:1.5px solid var(--border); border-radius:var(--radius-sm);
  background:var(--surface-2); color:var(--t2); font-size:.78rem; font-weight:500;
  font-family:var(--font); outline:none; cursor:pointer; transition:var(--ease); min-width:160px;
}
.rx-doc-filter:focus { border-color:var(--p-base); }
.rx-doc-filter option { background:#fff; color:var(--t1); }

/* Date range */
.rx-date-wrap { display:flex; align-items:center; gap:.35rem; flex-shrink:0; }
.rx-date-lbl  { font-size:.72rem; font-weight:700; color:var(--t3); white-space:nowrap; }
.rx-date-inp  { padding:.5rem .65rem; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:var(--font); font-size:.77rem; color:var(--t1); background:var(--surface-2); outline:none; transition:var(--ease); }
.rx-date-inp:focus { border-color:var(--p-base); }

/* Has-Medications toggle */
.rx-med-tog {
  display:flex; align-items:center; gap:.38rem; padding:.5rem .82rem;
  border:1.5px solid var(--border); border-radius:var(--radius-sm);
  background:var(--surface-2); color:var(--t3); font-size:.76rem; font-weight:600;
  cursor:pointer; transition:var(--ease); font-family:var(--font); white-space:nowrap;
}
.rx-med-tog i { font-size:.68rem; }
.rx-med-tog.on { background:var(--p-dim); border-color:var(--p-base); color:var(--p-mid); }

/* Clear filters */
.rx-clr-btn {
  padding:.5rem .8rem; border:1.5px solid var(--border); border-radius:var(--radius-sm);
  background:var(--surface-2); color:var(--t3); font-size:.75rem; font-weight:600;
  cursor:pointer; transition:var(--ease); font-family:var(--font); display:flex; align-items:center; gap:.32rem;
}
.rx-clr-btn:hover { border-color:var(--s-rose); color:var(--s-rose); background:#FFF5F5; }
.rx-clr-btn.has-filters { border-color:var(--s-amber); color:var(--s-amber); background:#FFFBF0; }

/* Sort */
.rx-sort { padding:.52rem .85rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface-2);color:var(--t2);font-size:.78rem;font-weight:600;font-family:var(--font);outline:none;cursor:pointer;transition:var(--ease); }
.rx-sort:focus { border-color:var(--p-base); }

/* View toggle */
.rx-vtog { display:flex;border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0; }
.rx-vb { padding:.46rem .68rem;background:transparent;border:none;font-size:.8rem;color:var(--t4);cursor:pointer;transition:var(--ease);font-family:var(--font); }
.rx-vb:hover { background:var(--bg);color:var(--t2); }
.rx-vb.on { background:var(--p-dim);color:var(--p-mid); }

/* Count pill */
.rx-cnt-pill { margin-left:auto;padding:.4rem .85rem;border-radius:99px;background:var(--p-dim);color:var(--p-mid);font-size:.74rem;font-weight:700;border:1px solid var(--p-dim2);white-space:nowrap; }

/* ════════════════════════════════════════════════════════════
   PRINT BUTTON — shared across all views
   ════════════════════════════════════════════════════════════ */
.rx-print-btn {
  display:inline-flex; align-items:center; gap:.4rem;
  padding:.5rem 1.2rem; border-radius:var(--radius-sm);
  border:none; background:var(--s-emerald);
  color:#fff; font-size:.85rem; font-weight:800;
  cursor:pointer; transition:var(--ease); font-family:var(--font);
  white-space:nowrap; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}
.rx-print-btn:hover { background:#047857; color:#fff; box-shadow:0 6px 16px rgba(5, 150, 105, 0.4); transform: translateY(-1px); }
.rx-print-btn i { font-size:.85rem; }

/* small icon-only variant */
.rx-print-ic {
  width:30px; height:30px; border-radius:var(--radius-sm);
  border:1.5px solid var(--border); background:var(--surface-2);
  color:var(--t3); font-size:.78rem; cursor:pointer;
  display:inline-flex; align-items:center; justify-content:center;
  transition:var(--ease); flex-shrink:0;
}
.rx-print-ic:hover { border-color:var(--p-base); color:var(--p-mid); background:var(--p-dim); }

/* ════════════════════════════════════════════════════════════
   VIEW A  —  SPLIT PANEL
   ════════════════════════════════════════════════════════════ */
.rx-split { display:flex; height:calc(100vh - 195px); overflow:hidden; }
.rx-split-left { width:340px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);overflow-y:auto;display:flex;flex-direction:column; }
.rx-split-left::-webkit-scrollbar { width:4px; }
.rx-split-left::-webkit-scrollbar-thumb { background:var(--border-2);border-radius:99px; }
.sp-header { padding:.75rem 1rem;border-bottom:1px solid var(--border);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--t4);background:var(--surface-2);flex-shrink:0; }
.sp-item { padding:.85rem 1rem;border-bottom:1px solid var(--border-2);cursor:pointer;transition:var(--ease);position:relative;display:flex;gap:.75rem;align-items:center; }
.sp-item::before { content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:transparent;transition:var(--ease);border-radius:0 3px 3px 0; }
.sp-item:hover { background:var(--p-pale); }
.sp-item.active { background:var(--p-pale); }
.sp-item.active::before { background:var(--p-base); }
.sp-avt { width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:900;color:#fff; }
.sp-info { flex:1;min-width:0; }
.sp-name { font-size:.84rem;font-weight:700;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.sp-meta { font-size:.68rem;color:var(--t3);margin-top:.08rem; }
.sp-date { font-size:.65rem;color:var(--t4);font-family:var(--mono);flex-shrink:0;text-align:right; }
.sp-imgdot { width:7px;height:7px;border-radius:50%;background:var(--p-base);margin-top:.3rem; }
.rx-split-right { flex:1;overflow-y:auto;background:var(--bg);padding:1.75rem 2rem; }
.rx-split-right::-webkit-scrollbar { width:5px; }
.rx-split-right::-webkit-scrollbar-thumb { background:var(--border-2);border-radius:99px; }

/* Detail Panel */
.dp-hero { background:linear-gradient(135deg,var(--p-dark),var(--p-mid));border-radius:var(--radius-lg);padding:1.4rem;margin-bottom:1.25rem;position:relative;overflow:hidden; }
.dp-hero::after { content:'';position:absolute;width:240px;height:240px;top:-80px;right:-50px;border-radius:50%;background:rgba(93,232,240,.08);pointer-events:none; }
.dp-hero-row { display:flex;align-items:center;gap:1rem;position:relative;z-index:1; }
.dp-avt-lg { width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;color:#fff;flex-shrink:0;border:2px solid rgba(255,255,255,.2); }
.dp-pname { font-size:1.15rem;font-weight:900;color:#fff;line-height:1.1; }
.dp-pid   { font-size:.67rem;font-family:var(--mono);color:var(--p-light);margin-top:.18rem; }
.dp-rxbadge { padding:.26rem .72rem;background:rgba(93,232,240,.18);border:1px solid rgba(93,232,240,.3);border-radius:99px;font-size:.64rem;font-weight:800;color:var(--p-light);text-transform:uppercase;letter-spacing:.06em; }
.dp-section { background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);margin-bottom:1rem;overflow:hidden; }
.dp-sec-hdr { padding:.65rem 1rem;background:var(--surface-2);border-bottom:1px solid var(--border);font-size:.64rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--t3);display:flex;align-items:center;gap:.4rem;justify-content:space-between; }
.dp-sec-hdr i { color:var(--p-base);font-size:.68rem; }
.dp-sec-hdr-l { display:flex;align-items:center;gap:.4rem; }
.dp-grid2 { display:grid;grid-template-columns:1fr 1fr; }
.dp-cell { padding:.72rem 1rem;border-bottom:1px solid var(--border); }
.dp-cell:nth-child(odd) { border-right:1px solid var(--border); }
.dp-cell-l { font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--t4);margin-bottom:.18rem; }
.dp-cell-v { font-size:.83rem;font-weight:700;color:var(--t1); }
.dp-med-tbl { width:100%;border-collapse:collapse; }
.dp-med-tbl th { font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--t4);padding:.58rem 1rem;text-align:left;background:var(--surface-2);border-bottom:1px solid var(--border); }
.dp-med-tbl td { padding:.62rem 1rem;font-size:.8rem;border-bottom:1px solid var(--border);color:var(--t2); }
.dp-med-tbl td:first-child { font-weight:700;color:var(--t1); }
.dp-med-tbl tr:last-child td { border-bottom:none; }
.dp-med-tbl tr:hover td { background:var(--p-pale); }
.dp-mpill { display:inline-block;padding:.1rem .4rem;border-radius:4px;font-size:.7rem;font-weight:700;background:var(--p-dim);color:var(--p-mid); }
.dp-img { width:100%;border-radius:var(--radius);border:2px solid var(--border);cursor:zoom-in;transition:var(--ease); }
.dp-img:hover { border-color:var(--p-base);box-shadow:var(--shadow); }
.dp-noimg { padding:2rem;text-align:center;color:var(--t4); }
.dp-noimg i { font-size:1.8rem;display:block;margin-bottom:.5rem;opacity:.3; }
.dp-noimg p { font-size:.8rem;font-weight:600;opacity:.5; }
.dp-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:50vh;text-align:center; }
.dp-empty-ic { width:72px;height:72px;border-radius:18px;background:var(--p-pale);border:1px solid var(--p-dim2);display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:var(--p-base);margin-bottom:1rem; }
.dp-empty h3 { font-size:1rem;font-weight:800;color:var(--t1);margin-bottom:.35rem; }
.dp-empty p  { font-size:.82rem;color:var(--t3); }

/* ════════════════════════════════════════════════════════════
   VIEW B  —  DATA TABLE
   ════════════════════════════════════════════════════════════ */
.rx-table-wrap { background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);overflow:hidden;box-shadow:var(--shadow-sm); }
.rx-tbl { width:100%;border-collapse:collapse; }
.rx-tbl thead { position:sticky;top:0;z-index:5; }
.rx-tbl th { padding:.72rem 1rem;background:linear-gradient(135deg,var(--p-dark),var(--p-mid));color:rgba(255,255,255,.75);font-size:.64rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;text-align:left;white-space:nowrap;user-select:none; }
.rx-tbl th:first-child { padding-left:1.25rem; }
.rx-tbl td { padding:.78rem 1rem;font-size:.82rem;color:var(--t2);border-bottom:1px solid var(--border);vertical-align:middle; }
.rx-tbl td:first-child { padding-left:1.25rem; }
.rx-tbl tr.data-row { cursor:pointer;transition:background var(--ease); }
.rx-tbl tr.data-row:hover td { background:var(--p-pale); }
.rx-tbl tr.expanded td { background:var(--p-pale)!important; }
.rx-tbl tr.expand-row td { padding:0;background:var(--p-pale)!important;border-bottom:2px solid var(--p-base); }
.rx-expand-inner { padding:.9rem 1.25rem 1.25rem;display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem; }
.rx-exp-block { background:var(--surface);border-radius:var(--radius-sm);border:1px solid var(--border);overflow:hidden; }
.rx-exp-block-hdr { padding:.48rem .75rem;background:var(--surface-2);border-bottom:1px solid var(--border);font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--t3);display:flex;align-items:center;gap:.35rem; }
.rx-exp-block-hdr i { color:var(--p-base); }
.rx-exp-block-body { padding:.7rem .75rem; }
.rx-exp-field { display:flex;justify-content:space-between;padding:.28rem 0;border-bottom:1px dashed var(--border);font-size:.77rem; }
.rx-exp-field:last-child { border-bottom:none; }
.rx-exp-fl { color:var(--t3);font-weight:500; }
.rx-exp-fv { color:var(--t1);font-weight:700;text-align:right; }
.rx-exp-img { width:100%;border-radius:6px;border:1px solid var(--border);cursor:zoom-in; }
.tbl-avt  { width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:900;color:#fff;vertical-align:middle;margin-right:.5rem; }
.tbl-name { font-weight:700;color:var(--t1); }
.tbl-pid  { font-size:.68rem;font-family:var(--mono);color:var(--p-base);display:block;margin-top:.08rem; }
.tbl-badge { display:inline-block;padding:.15rem .5rem;border-radius:5px;font-size:.66rem;font-weight:700; }
.tbl-b-img  { background:var(--p-dim);color:var(--p-mid); }
.tbl-b-nimg { background:#F1F5F9;color:var(--t3); }
.tbl-b-med  { background:rgba(79,70,229,.08);color:var(--s-indigo); }
.tbl-expand-btn { background:none;border:1.5px solid var(--border);border-radius:6px;padding:.26rem .52rem;cursor:pointer;font-size:.7rem;color:var(--t3);transition:var(--ease); }
.tbl-expand-btn:hover { border-color:var(--p-base);color:var(--p-base); }
.tbl-expand-btn.open { background:var(--p-dim);border-color:var(--p-base);color:var(--p-mid); }

/* ════════════════════════════════════════════════════════════
   VIEW C  —  ACTIVITY FEED
   ════════════════════════════════════════════════════════════ */
.rx-feed { display:flex;flex-direction:column;gap:0;padding:1.5rem 2rem 3rem; }
.rx-feed-daylbl { font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--t4);padding:.5rem 0;border-bottom:2px solid var(--border);margin-bottom:.85rem;display:flex;align-items:center;gap:.5rem; }
.rx-feed-daylbl::before { content:'';width:8px;height:8px;border-radius:50%;background:var(--p-base);flex-shrink:0; }
.rx-feed-day { margin-bottom:1.5rem; }
.rx-fentry-wrap { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:.65rem;overflow:hidden;transition:var(--ease);position:relative; }
.rx-fentry-wrap::before { content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--border);transition:var(--ease); }
.rx-fentry-wrap:hover { border-color:var(--p-base);box-shadow:var(--shadow); }
.rx-fentry-wrap:hover::before { background:var(--p-base); }
.rx-fentry { display:flex;align-items:stretch;gap:0; }
.fe-time { width:70px;flex-shrink:0;padding:.85rem .5rem;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;border-right:1px solid var(--border);background:var(--surface-2);gap:.25rem; }
.fe-time-val { font-size:.72rem;font-weight:800;font-family:var(--mono);color:var(--t2); }
.fe-time-dot { width:6px;height:6px;border-radius:50%;background:var(--p-base); }
.fe-patient { width:195px;flex-shrink:0;padding:.85rem .9rem;border-right:1px solid var(--border);display:flex;align-items:center;gap:.65rem; }
.fe-avt { width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:900;color:#fff;flex-shrink:0; }
.fe-pname { font-size:.82rem;font-weight:700;color:var(--t1);line-height:1.2; }
.fe-pid   { font-size:.63rem;font-family:var(--mono);color:var(--p-base);margin-top:.1rem; }
.fe-doctor { width:175px;flex-shrink:0;padding:.85rem .9rem;border-right:1px solid var(--border);display:flex;align-items:center;gap:.6rem; }
.fe-dav { width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--p-mid),var(--p-base));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.65rem;flex-shrink:0;overflow:hidden; }
.fe-dav img { width:100%;height:100%;object-fit:cover; }
.fe-dname { font-size:.78rem;font-weight:700;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.fe-dspec { font-size:.64rem;color:var(--t3);font-weight:500; }
.fe-meds { flex:1;padding:.85rem .9rem;display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;border-right:1px solid var(--border);overflow:hidden; }
.fe-med { display:inline-flex;align-items:center;gap:.25rem;padding:.22rem .55rem;border-radius:5px;font-size:.68rem;font-weight:600;background:var(--p-dim);color:var(--p-mid);border:1px solid var(--p-dim2);white-space:nowrap; }
.fe-med i { font-size:.6rem; }
.fe-med-more { font-size:.7rem;color:var(--t4);font-weight:600; }
.fe-noplan { font-size:.75rem;color:var(--t4);font-weight:500; }
.fe-actions { width:120px;flex-shrink:0;padding:.75rem .7rem;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem; }
.fe-imgbadge { padding:.2rem .55rem;border-radius:5px;font-size:.64rem;font-weight:700; }
.fe-imgbadge.has { background:var(--p-dim);color:var(--p-mid); }
.fe-imgbadge.no  { background:#F1F5F9;color:var(--t3); }
.fe-view-btn { width:100%;padding:.36rem .5rem;border-radius:6px;border:1.5px solid var(--p-base);background:transparent;color:var(--p-base);font-size:.7rem;font-weight:700;cursor:pointer;transition:var(--ease);font-family:var(--font);display:flex;align-items:center;justify-content:center;gap:.3rem; }
.fe-view-btn:hover { background:var(--p-base);color:#fff; }
.rx-fexpand { display:none;border-top:1px solid var(--border);background:var(--surface-2);padding:1rem 1.1rem;grid-template-columns:1fr 1fr 1fr;gap:.85rem; }
.rx-fexpand.open { display:grid; }

/* ── Shared ─────────────────────────────────────────────── */
.rx-skel-row { display:flex;gap:.75rem;align-items:center;padding:.85rem 1rem;border-bottom:1px solid var(--border);background:var(--surface); }
.sk { background:linear-gradient(90deg,#f1f5f9 25%,#e8eef4 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.6s infinite;border-radius:5px; }
@keyframes shimmer { from{background-position:200% 0} to{background-position:-200% 0} }
.rx-empty { display:flex;flex-direction:column;align-items:center;padding:4rem 2rem;text-align:center; }
.rx-empty-ic { width:68px;height:68px;border-radius:16px;background:var(--p-pale);border:1.5px solid var(--p-dim2);display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--p-base);margin-bottom:1rem; }
.rx-empty h3 { font-size:1rem;font-weight:800;color:var(--t1);margin-bottom:.32rem; }
.rx-empty p  { font-size:.81rem;color:var(--t3); }

/* Modal */
.rx-mback { position:fixed;inset:0;background:rgba(1,28,34,.85);backdrop-filter:blur(16px);z-index:9000;display:none;align-items:center;justify-content:center;padding:1.5rem; }
.rx-mback.open { display:flex;animation:fadeIn .22s ease; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
.rx-mbox { background:var(--surface);border-radius:var(--radius-lg);max-width:820px;width:100%;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;animation:mboxIn .32s cubic-bezier(.34,1.56,.64,1);box-shadow:0 40px 100px rgba(0,0,0,.45); }
@keyframes mboxIn { from{opacity:0;transform:scale(.93) translateY(24px)} to{opacity:1;transform:none} }
.rx-mhdr { padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--p-dark),var(--p-mid));display:flex;justify-content:space-between;align-items:center;flex-shrink:0; }
.rx-m-tit { font-size:.95rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:.5rem; }
.rx-m-tit i { color:var(--p-light); }
.rx-m-cls { width:30px;height:30px;border-radius:7px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);cursor:pointer;font-size:.82rem;display:flex;align-items:center;justify-content:center;transition:var(--ease); }
.rx-m-cls:hover { background:rgba(255,255,255,.2); }
.rx-mbody { overflow-y:auto;padding:1.35rem 1.5rem; }
.rx-mbody::-webkit-scrollbar { width:4px; }
.rx-mbody::-webkit-scrollbar-thumb { background:var(--border-2);border-radius:99px; }

/* Lightbox */
.rx-lbx { position:fixed;inset:0;background:rgba(0,0,0,.96);z-index:10000;display:none;align-items:center;justify-content:center;cursor:zoom-out; }
.rx-lbx.open { display:flex;animation:fadeIn .2s ease; }
.rx-lbx img { max-width:90vw;max-height:90vh;object-fit:contain;border-radius:var(--radius);box-shadow:0 40px 100px rgba(0,0,0,.8); }
.lbx-cls { position:absolute;top:1.25rem;right:1.25rem;width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:var(--ease); }
.lbx-cls:hover { background:rgba(255,255,255,.22); }

/* Toast */
.rx-toasts { position:fixed;bottom:1.5rem;right:1.5rem;z-index:10001;display:flex;flex-direction:column;gap:.4rem;pointer-events:none; }
.rx-toast { padding:.65rem 1rem;border-radius:var(--radius-sm);background:var(--p-dark);color:#fff;font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:.5rem;box-shadow:var(--shadow-lg);animation:fadeIn .28s ease;pointer-events:all;max-width:280px; }
.rx-toast i { color:var(--p-light); }
.rx-toast.err { background:var(--s-rose); }
.rx-toast.err i { color:#fff; }

@media(max-width:900px){
  .rx-split { flex-direction:column;height:auto; }
  .rx-split-left { width:100%;height:280px;border-right:none;border-bottom:1px solid var(--border); }
  .fe-doctor,.fe-meds { display:none; }
  .rx-expand-inner { grid-template-columns:1fr; }
  .rx-cbar { gap:.4rem; }
  .rx-date-wrap { display:none; }
}
</style>

<!-- ═══════ PRINT ZONE ═══════ -->
<div id="rx-print-zone"></div>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body" style="padding:0;">

  <!-- ══ HEADER ══ -->
  <div class="rx-header">
    <div class="rx-hinner">
      <div class="rx-htag"><i class="fas fa-file-prescription"></i> Pharmacy · Clinical Archive</div>
      <h1 class="rx-ht">Clinical <em>Prescriptions</em></h1>
      <p class="rx-hsub">Complete log of all doctor consultations, treatment plans and digital prescriptions.</p>
      <div class="rx-hstats">
        <div class="rxhs"><div class="rxhs-v" id="h-rx">—</div><div class="rxhs-l">Total Rx</div></div>
        <div class="rxhs"><div class="rxhs-v" id="h-dr">—</div><div class="rxhs-l">Physicians</div></div>
        <div class="rxhs"><div class="rxhs-v" id="h-pt">—</div><div class="rxhs-l">Patients</div></div>
        <div class="rxhs"><div class="rxhs-v" id="h-im">—</div><div class="rxhs-l">With Image</div></div>
      </div>
    </div>
  </div>

  <!-- ══ CONTROL BAR ══ -->
  <div class="rx-cbar">
    <!-- 1. Search -->
    <div class="rx-srch-wrap">
      <i class="fas fa-search"></i>
      <input type="text" class="rx-srch" id="q" placeholder="Search patient, doctor, medication…" autocomplete="off">
    </div>

    <!-- 2. Doctor Filter (populated from data) -->
    <select class="rx-doc-filter" id="doctorFilter" title="Filter by Doctor">
      <option value="">All Doctors</option>
    </select>

    <!-- 3. Date Range -->
    <div class="rx-date-wrap">
      <span class="rx-date-lbl"><i class="far fa-calendar-alt" style="color:var(--p-base)"></i> From</span>
      <input type="date" class="rx-date-inp" id="dateFrom">
      <span class="rx-date-lbl">To</span>
      <input type="date" class="rx-date-inp" id="dateTo">
    </div>

    <!-- 4. Has Medications Toggle -->
    <button class="rx-med-tog" id="medToggle" title="Show only records with a treatment plan">
      <i class="fas fa-pills"></i> Has Meds
    </button>

    <!-- 5. Clear Filters -->
    <button class="rx-clr-btn" id="clrBtn" onclick="clearFilters()" title="Clear all filters">
      <i class="fas fa-times"></i> Clear
    </button>

    <!-- Sort -->
    <select class="rx-sort" id="sortBy">
      <option value="date_d">↓ Newest</option>
      <option value="date_a">↑ Oldest</option>
      <option value="name_a">Name A→Z</option>
      <option value="name_d">Name Z→A</option>
      <option value="doctor">By Doctor</option>
      <option value="meds">Med Count</option>
    </select>

    <!-- View Toggle -->
    <div class="rx-vtog">
      <button class="rx-vb on" data-v="split"    title="Split Panel View"><i class="fas fa-columns"></i></button>
      <button class="rx-vb"    data-v="table"    title="Data Table View"><i class="fas fa-table"></i></button>
      <button class="rx-vb"    data-v="feed"     title="Activity Feed View"><i class="fas fa-stream"></i></button>
    </div>

    <div class="rx-cnt-pill" id="cnt">Loading…</div>
  </div>

  <!-- ══ VIEWS ══ -->

  <!-- VIEW A: SPLIT PANEL -->
  <div id="view-split" style="display:flex;">
    <div class="rx-split" style="width:100%;">
      <div class="rx-split-left">
        <div class="sp-header"><i class="fas fa-list" style="color:var(--p-base);margin-right:.35rem;"></i> Prescription List</div>
        <div id="sp-list">
          <?php for($i=0;$i<8;$i++): ?>
          <div class="rx-skel-row">
            <div class="sk" style="width:38px;height:38px;border-radius:10px;flex-shrink:0;"></div>
            <div style="flex:1;"><div class="sk" style="height:12px;width:70%;margin-bottom:.35rem;"></div><div class="sk" style="height:10px;width:50%;"></div></div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="rx-split-right">
        <div id="sp-detail">
          <div class="dp-empty">
            <div class="dp-empty-ic"><i class="fas fa-hand-pointer"></i></div>
            <h3>Select a Prescription</h3>
            <p>Click any item from the list to view full details here.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- VIEW B: DATA TABLE -->
  <div id="view-table" style="display:none; padding:1.5rem 2rem 3rem;">
    <div class="rx-table-wrap">
      <table class="rx-tbl">
        <thead>
          <tr>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Date</th>
            <th>Medications</th>
            <th>Image</th>
            <th style="width:90px;text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="tbl-body">
          <?php for($i=0;$i<6;$i++): ?>
          <tr><td colspan="6"><div class="rx-skel-row" style="border-bottom:none;"><div class="sk" style="width:32px;height:32px;border-radius:8px;flex-shrink:0;"></div><div style="flex:1;"><div class="sk" style="height:11px;width:55%;margin-bottom:.32rem;"></div><div class="sk" style="height:9px;width:35%;"></div></div></div></td></tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- VIEW C: ACTIVITY FEED -->
  <div id="view-feed" style="display:none;">
    <div class="rx-feed" id="feed-body">
      <div class="rx-empty">
        <div class="rx-empty-ic"><i class="fas fa-spinner fa-spin"></i></div>
        <h3>Loading…</h3><p>Fetching prescription records.</p>
      </div>
    </div>
  </div>

</div><!-- ph-page-body -->
</div><!-- ph-content -->
</div><!-- ph-wrap -->

<!-- ══ MODAL ══ -->
<div class="rx-mback" id="rxModal">
  <div class="rx-mbox">
    <div class="rx-mhdr">
      <div class="rx-m-tit"><i class="fas fa-file-prescription"></i><span id="mTitle">Details</span></div>
      <button class="rx-m-cls" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="rx-mbody" id="mBody"></div>
  </div>
</div>

<!-- ══ LIGHTBOX ══ -->
<div class="rx-lbx" id="lightbox" onclick="closeLbx()">
  <img id="lbx-img" src="" alt="Prescription">
  <button class="lbx-cls" onclick="closeLbx()"><i class="fas fa-times"></i></button>
</div>

<!-- ══ TOASTS ══ -->
<div class="rx-toasts" id="toasts"></div>

<?php include 'includes/ph_foot.php'; ?>

<script>
'use strict';
/* ═══════════════════════════════════════════════════════════
   RX ARCHIVE  ·  Three Views + Individual Print Engine
   ═══════════════════════════════════════════════════════════ */
let ALL = [], RENDERED = [];
let curView = 'split', selectedIdx = -1;
let medToggleOn = false;

const PAL = ['#1f6b4a','#4F46E5','#059669','#D97706','#E11D48','#7C3AED','#0891B2','#B45309'];
function hsh(s){ let h=0; for(let i=0;i<s.length;i++) h=Math.imul(31,h)+s.charCodeAt(i)|0; return Math.abs(h); }
function esc(s){ if(s==null)return''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function ini(n){ if(!n||!n.trim())return'?'; const p=n.trim().split(/\s+/); return(p[0][0]+(p[1]?p[1][0]:p[0][1]||'')).toUpperCase(); }
function col(id){ return PAL[hsh(id||'x')%PAL.length]; }
function parsePlan(raw){ if(!raw)return[]; try{ const d=JSON.parse(raw); const m=Array.isArray(d)?d:(d.medications||[]); if(Array.isArray(m))return m; }catch(e){} return[]; }

function animCount(el,target){
  const dur=1000,ease=t=>1-Math.pow(1-t,3),s=performance.now();
  (function step(now){ const p=Math.min((now-s)/dur,1); el.textContent=Math.round(ease(p)*target); if(p<1)requestAnimationFrame(step); })(s);
}
function toast(msg,type='ok'){
  const t=document.createElement('div');
  t.className='rx-toast'+(type==='err'?' err':'');
  t.innerHTML=`<i class="fas fa-${type==='err'?'exclamation-triangle':'check-circle'}"></i>${esc(msg)}`;
  document.getElementById('toasts').appendChild(t);
  setTimeout(()=>{ t.style.cssText='opacity:0;transition:.25s'; setTimeout(()=>t.remove(),260); },3000);
}

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded',()=>{
  if(typeof phGet!=='function'){ showErr('System error. Please refresh.'); return; }
  load();
  document.getElementById('q').addEventListener('input', render);
  document.getElementById('sortBy').addEventListener('change', render);
  document.getElementById('doctorFilter').addEventListener('change', render);
  document.getElementById('dateFrom').addEventListener('change', render);
  document.getElementById('dateTo').addEventListener('change', render);
  document.getElementById('medToggle').addEventListener('click', function(){
    medToggleOn = !medToggleOn;
    this.classList.toggle('on', medToggleOn);
    updateClearBtn();
    render();
  });
  document.querySelectorAll('.rx-vb').forEach(b=>b.addEventListener('click',function(){
    document.querySelectorAll('.rx-vb').forEach(x=>x.classList.remove('on'));
    this.classList.add('on'); switchView(this.dataset.v);
  }));
  document.getElementById('rxModal').addEventListener('click',function(e){ if(e.target===this)closeModal(); });
  document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ closeModal(); closeLbx(); } });
});

/* ── Load ─────────────────────────────────────────────────── */
async function load(){
  try{
    const j=await phGet(API_BASE+'pharmacy/prescriptions');
    if(j.success){
      ALL=j.data||[];
      doStats();
      populateDoctorFilter();
      render();
      toast(`${ALL.length} prescriptions loaded`);
    } else showErr(j.message||'Failed to load');
  }catch(e){ console.error(e); showErr('Network error'); }
}

function doStats(){
  animCount(document.getElementById('h-rx'),ALL.length);
  animCount(document.getElementById('h-dr'),new Set(ALL.map(i=>i.doctor_id)).size);
  animCount(document.getElementById('h-pt'),new Set(ALL.map(i=>i.patient_id)).size);
  animCount(document.getElementById('h-im'),ALL.filter(i=>i.prescription_image&&i.prescription_image.length>10).length);
}

/* ── Doctor filter dropdown — built from real data ─────────── */
function populateDoctorFilter(){
  const doctors=[...new Set(ALL.map(x=>(x.doctor_name||'').trim()).filter(Boolean))].sort();
  const sel=document.getElementById('doctorFilter');
  sel.innerHTML='<option value="">All Doctors</option>'+doctors.map(d=>`<option value="${esc(d)}">${esc(d)}</option>`).join('');
}

/* ── Clear all filters ───────────────────────────────────── */
function clearFilters(){
  document.getElementById('q').value='';
  document.getElementById('doctorFilter').value='';
  document.getElementById('dateFrom').value='';
  document.getElementById('dateTo').value='';
  document.getElementById('sortBy').value='date_d';
  medToggleOn=false;
  document.getElementById('medToggle').classList.remove('on');
  updateClearBtn();
  render();
  toast('Filters cleared');
}

function updateClearBtn(){
  const q=document.getElementById('q').value;
  const df=document.getElementById('doctorFilter').value;
  const f=document.getElementById('dateFrom').value;
  const t=document.getElementById('dateTo').value;
  const hasAny = q||df||f||t||medToggleOn;
  document.getElementById('clrBtn').classList.toggle('has-filters',!!hasAny);
}

/* ── Filter + Sort ───────────────────────────────────────── */
function filterSort(){
  const q=(document.getElementById('q').value||'').toLowerCase().trim();
  const doc=document.getElementById('doctorFilter').value;
  const from=document.getElementById('dateFrom').value;
  const to=document.getElementById('dateTo').value;
  const sv=document.getElementById('sortBy').value;

  let d=ALL.filter(x=>{
    if(doc && (x.doctor_name||'').trim()!==doc) return false;
    if(from && x.consultation_date && x.consultation_date<from) return false;
    if(to   && x.consultation_date && x.consultation_date>to)   return false;
    if(medToggleOn && parsePlan(x.soap_plan).length===0) return false;
    if(q){
      const b=[(x.patient_name||''),(x.patient_id||''),(x.doctor_name||''),(x.soap_plan||'')].join(' ').toLowerCase();
      if(!b.includes(q)) return false;
    }
    return true;
  });
  d.sort((a,b)=>{
    if(sv==='date_d') return(b.consultation_date||'').localeCompare(a.consultation_date||'');
    if(sv==='date_a') return(a.consultation_date||'').localeCompare(b.consultation_date||'');
    if(sv==='name_a') return(a.patient_name||'').localeCompare(b.patient_name||'');
    if(sv==='name_d') return(b.patient_name||'').localeCompare(a.patient_name||'');
    if(sv==='doctor') return(a.doctor_name||'').localeCompare(b.doctor_name||'');
    if(sv==='meds')   return parsePlan(b.soap_plan).length-parsePlan(a.soap_plan).length;
    return 0;
  });
  return d;
}

/* ── Render dispatcher ───────────────────────────────────── */
function render(){
  updateClearBtn();
  RENDERED=filterSort();
  const n=RENDERED.length;
  document.getElementById('cnt').textContent=`${n} record${n!==1?'s':''}`;
  if(curView==='split') renderSplit();
  else if(curView==='table') renderTable();
  else renderFeed();
}

function switchView(v){
  curView=v;
  document.getElementById('view-split').style.display=v==='split'?'flex':'none';
  document.getElementById('view-table').style.display=v==='table'?'block':'none';
  document.getElementById('view-feed').style.display =v==='feed' ?'block':'none';
  render();
}

/* ════════════════════════════════════════════════════════════
   ██  INDIVIDUAL PRINT  ██
   Advanced Modern Medical Prescription Slip
   ════════════════════════════════════════════════════════════ */
function printRx(idx){
  const x=RENDERED[idx]; if(!x) return;
  const pn=(x.patient_name&&x.patient_name.trim())?x.patient_name.trim():x.patient_id;
  const dn=(x.doctor_name&&x.doctor_name.trim())?x.doctor_name.trim():'Unknown Doctor';
  const meds=parsePlan(x.soap_plan);
  const hi=x.prescription_image&&x.prescription_image.length>10;
  const now=new Date();
  const printDate=now.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});
  const printTime=now.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',hour12:true});
  
  // Fake barcode pattern based on ID length
  const fakeBarcode = '||| |||| | ||||| | ||| ||'.repeat(Math.ceil((x.consultation_id||'x').length/5)).substring(0, 25);

  document.getElementById('rx-print-zone').innerHTML=`
  <div class="rx-slip">
    <div class="rx-watermark-adv">&#8478;</div>
    
    <div class="rx-slip-top"></div>
    <div class="rx-slip-content">

    <!-- ── HEADER ─────────────────────────────────────── -->
    <div class="rx-header-adv">
      <div class="rx-header-left">
        <h1>GM Hospital</h1>
        <p><i class="fas fa-map-marker-alt"></i> 612, Nagarabhavi Main Rd, Vinayaka Layout, Bengaluru - 560072</p>
        <p><i class="fas fa-phone-alt"></i> +91 80 1234 5678 &nbsp;&middot;&nbsp; <i class="fas fa-envelope"></i> contact@gmhospital.com</p>
      </div>
      <div class="rx-header-right" style="text-align:right">
        <div class="rx-meta-adv" style="margin-top:0">Printed: ${printDate} ${printTime}</div>
      </div>
    </div>

    <!-- ── PATIENT & DOCTOR CARDS ─────────────────────── -->
    <div class="rx-info-grid">
      <div class="rx-info-box">
        <div class="rx-box-title"><i class="fas fa-user-injured"></i> Patient Information</div>
        <div class="rx-box-name">${esc(pn)}</div>
        <div class="rx-box-grid">
          <div><span>Patient ID:</span> <strong>${esc(x.patient_id||'—')}</strong></div>
          <div><span>Age / Sex:</span> <strong>${x.patient_age?esc(x.patient_age)+' Yrs':'—'} / ${esc(x.patient_sex||'—')}</strong></div>
          ${x.patient_phone ? `<div><span>Phone:</span> <strong>${esc(x.patient_phone)}</strong></div>` : ''}
          ${x.patient_address ? `<div><span>Address:</span> <strong>${esc(x.patient_address)}</strong></div>` : ''}
        </div>
      </div>
      <div class="rx-info-box doc-box">
        <div class="rx-box-title"><i class="fas fa-user-md"></i> Prescribing Physician</div>
        <div class="rx-box-name">Dr. ${esc(dn)}</div>
        <div class="rx-box-grid">
          <div><span>Specialty:</span> <strong>${esc(x.doctor_specialization||'General Physician')}</strong></div>
          <div><span>Department:</span> <strong>Pharmacy / Clinical</strong></div>
          ${x.doctor_phone ? `<div><span>Contact:</span> <strong>${esc(x.doctor_phone)}</strong></div>` : ''}
        </div>
      </div>
    </div>

    <!-- ── TREATMENT PLAN ─────────────────────────────── -->
    ${meds.length ? `
    <div class="rx-sec-title">
      <h3><span class="rx-sec-icon">&#8478;</span> Prescribed Treatment Plan</h3>
      <div class="line"></div>
    </div>
    
    <table class="rx-mod-tbl">
      <thead>
        <tr>
          <th style="width:30px;text-align:center">#</th>
          <th>Medication Details</th>
          <th>Dosage</th>
          <th>Frequency</th>
          <th>Duration</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        ${meds.map((m,i)=>`
        <tr>
          <td align="center" class="med-idx">${String(i+1).padStart(2,'0')}</td>
          <td>
            <span class="med-name">${esc(m.name||'—')}</span>
          </td>
          <td>${m.dosage?`<span class="med-badge">${esc(m.dosage)}</span>`:'—'}</td>
          <td><strong style="color:#011c22">${esc(m.freq||'—')}</strong></td>
          <td><span class="med-dur">${m.duration?esc(m.duration)+' Days':'—'}</span></td>
          <td style="font-size:9.5px;color:#64748B">${esc(m.instructions||'Take as directed')}</td>
        </tr>`).join('')}
      </tbody>
    </table>
    <div class="rx-note"><i class="fas fa-info-circle"></i> Note: Please complete the full course of medication. Do not alter dosage without consultation.</div>
    ` : `
    <div class="rx-sec-title">
      <h3><span class="rx-sec-icon">&#8478;</span> Prescribed Treatment Plan</h3>
      <div class="line"></div>
    </div>
    <div style="padding:20px;background:#F8FAFC;border:1px dashed #CBD5E1;border-radius:10px;text-align:center;color:#94A3B8;font-size:11px;">
      <i class="fas fa-notes-medical" style="font-size:24px;margin-bottom:8px;opacity:0.5;display:block"></i>
      No medications prescribed during this consultation.
    </div>
    `}

    <!-- ── DIGITAL IMAGE ──────────────────────────────── -->
    ${hi ? `
    <div class="rx-sec-title">
      <h3><i class="fas fa-file-image" style="color:#1f6b4a;margin-right:6px"></i> Digital Prescription Scan</h3>
      <div class="line"></div>
    </div>
    <div class="rx-img-box">
      <img src="${esc(x.prescription_image)}" alt="Prescription Scan">
    </div>
    ` : ''}

    <!-- ── SIGNATURES ─────────────────────────────────── -->
    <div class="rx-sigs-mod">
      <div class="rx-sig-block">
        <div class="rx-sig-line"></div>
        <div class="rx-sig-text">Patient / Guardian Signature</div>
      </div>
      <div class="rx-sig-block">
        <div class="rx-stamp-box">
          <span class="rx-stamp-text">HOSPITAL STAMP</span>
        </div>
        <div class="rx-sig-text">Official Seal</div>
      </div>
      <div class="rx-sig-block">
        <div class="rx-sig-line"></div>
        <div class="rx-sig-text">Doctor's Signature &amp; Reg. No.</div>
      </div>
    </div>

    <!-- ── FOOTER ─────────────────────────────────────── -->
    <div class="rx-foot-mod" style="display:none;"></div>

    </div> <!-- rx-slip-content -->
  </div>`;

  setTimeout(()=>window.print(), 200);
}

/* ════════════════════════════════════════════════════════════
   VIEW A — SPLIT PANEL
   ════════════════════════════════════════════════════════════ */
function renderSplit(){
  if(!RENDERED.length){ document.getElementById('sp-list').innerHTML=emptyHTML(); return; }
  document.getElementById('sp-list').innerHTML=RENDERED.map((x,i)=>spItem(x,i)).join('');
  if(selectedIdx>=0&&selectedIdx<RENDERED.length) showDetail(selectedIdx);
  else { selectedIdx=-1; document.getElementById('sp-detail').innerHTML=`<div class="dp-empty"><div class="dp-empty-ic"><i class="fas fa-hand-pointer"></i></div><h3>Select a Prescription</h3><p>Click any item from the list to view full details.</p></div>`; }
}

function spItem(x,i){
  const pn=(x.patient_name&&x.patient_name.trim())?x.patient_name.trim():x.patient_id;
  const dn=(x.doctor_name&&x.doctor_name.trim())?x.doctor_name.trim():'Unknown';
  const hi=x.prescription_image&&x.prescription_image.length>10;
  const meds=parsePlan(x.soap_plan);
  return `<div class="sp-item${i===selectedIdx?' active':''}" onclick="showDetail(${i})">
    <div class="sp-avt" style="background:${col(x.patient_id)}">${ini(pn)}</div>
    <div class="sp-info">
      <div class="sp-name">${esc(pn)}</div>
      <div class="sp-meta">Dr. ${esc(dn)} &middot; ${meds.length} med${meds.length!==1?'s':''}</div>
    </div>
    <div style="flex-shrink:0;text-align:right;">
      <div class="sp-date">${esc(x.consultation_date||'—')}</div>
      ${hi?'<div class="sp-imgdot" title="Has Image"></div>':''}
    </div>
  </div>`;
}

function showDetail(idx){
  selectedIdx=idx;
  document.querySelectorAll('.sp-item').forEach((el,i)=>el.classList.toggle('active',i===idx));
  const x=RENDERED[idx]; if(!x) return;
  const pn=(x.patient_name&&x.patient_name.trim())?x.patient_name.trim():x.patient_id;
  const dn=(x.doctor_name&&x.doctor_name.trim())?x.doctor_name.trim():'Unknown Doctor';
  const meds=parsePlan(x.soap_plan);
  const hi=x.prescription_image&&x.prescription_image.length>10;
  const c=col(x.patient_id);
  document.getElementById('sp-detail').innerHTML=`
    <div class="dp-hero">
      <div class="dp-hero-row">
        <div class="dp-avt-lg" style="background:linear-gradient(135deg,${c},var(--p-base))">${ini(pn)}</div>
        <div class="dp-patient-info">
          <div class="dp-pname">${esc(pn)}</div>
          <div class="dp-pid">${esc(x.patient_id||'')}</div>
        </div>
        <span class="dp-rxbadge">Rx</span>
        <button class="rx-print-btn" onclick="printRx(${idx})" style="margin-left:.5rem;">
          <i class="fas fa-print"></i> Print Rx
        </button>
      </div>
    </div>
    <div class="dp-section">
      <div class="dp-sec-hdr">
        <div class="dp-sec-hdr-l"><i class="fas fa-id-card"></i> Consultation Details</div>
      </div>
      <div class="dp-grid2">
        <div class="dp-cell"><div class="dp-cell-l">Patient</div><div class="dp-cell-v">${esc(pn)}</div></div>
        <div class="dp-cell"><div class="dp-cell-l">Patient ID</div><div class="dp-cell-v" style="font-family:var(--mono);color:var(--p-base);font-size:.8rem">${esc(x.patient_id||'—')}</div></div>
        <div class="dp-cell"><div class="dp-cell-l">Physician</div><div class="dp-cell-v">Dr. ${esc(dn)}</div></div>
        <div class="dp-cell"><div class="dp-cell-l">Specialization</div><div class="dp-cell-v">${esc(x.doctor_specialization||'General Physician')}</div></div>
        <div class="dp-cell"><div class="dp-cell-l">Date</div><div class="dp-cell-v">${esc(x.consultation_date||'—')}</div></div>
        <div class="dp-cell"><div class="dp-cell-l">Time</div><div class="dp-cell-v">${esc(x.consultation_time||'—')}</div></div>
        ${x.patient_sex?`<div class="dp-cell"><div class="dp-cell-l">Sex</div><div class="dp-cell-v">${esc(x.patient_sex)}</div></div>`:''}
        ${x.patient_age?`<div class="dp-cell"><div class="dp-cell-l">Age</div><div class="dp-cell-v">${esc(x.patient_age)} years</div></div>`:''}
      </div>
    </div>
    ${meds.length?`<div class="dp-section">
      <div class="dp-sec-hdr"><div class="dp-sec-hdr-l"><i class="fas fa-pills"></i> Treatment Plan — ${meds.length} Medication${meds.length!==1?'s':''}</div></div>
      <table class="dp-med-tbl">
        <thead><tr><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Duration</th></tr></thead>
        <tbody>${meds.map(m=>`<tr>
          <td>${esc(m.name||'—')}</td>
          <td>${m.dosage?`<span class="dp-mpill">${esc(m.dosage)}</span>`:'—'}</td>
          <td>${esc(m.freq||'—')}</td>
          <td>${m.duration?esc(m.duration)+'d':'—'}</td>
        </tr>`).join('')}</tbody>
      </table>
    </div>`:''}
    <div class="dp-section">
      <div class="dp-sec-hdr"><div class="dp-sec-hdr-l"><i class="fas fa-image"></i> Digital Prescription</div></div>
      <div style="padding:.9rem">
        ${hi?`<img class="dp-img" src="${esc(x.prescription_image)}" alt="Prescription" onclick="openLbx('${esc(x.prescription_image)}')">`
            :`<div class="dp-noimg"><i class="fas fa-file-image"></i><p>No digital image attached.</p></div>`}
      </div>
    </div>`;
}

/* ════════════════════════════════════════════════════════════
   VIEW B — DATA TABLE
   ════════════════════════════════════════════════════════════ */
function renderTable(){
  const tbody=document.getElementById('tbl-body');
  if(!RENDERED.length){ tbody.innerHTML=`<tr><td colspan="6">${emptyHTML()}</td></tr>`; return; }
  tbody.innerHTML=RENDERED.map((x,i)=>tblRow(x,i)).join('');
}

function tblRow(x,i){
  const pn=(x.patient_name&&x.patient_name.trim())?x.patient_name.trim():x.patient_id;
  const dn=(x.doctor_name&&x.doctor_name.trim())?x.doctor_name.trim():'Unknown';
  const meds=parsePlan(x.soap_plan);
  const hi=x.prescription_image&&x.prescription_image.length>10;
  const c=col(x.patient_id);
  return `<tr class="data-row" id="dr-${i}">
    <td>
      <span class="tbl-avt" style="background:${c}">${ini(pn)}</span>
      <span class="tbl-name">${esc(pn)}</span>
      <span class="tbl-pid">${esc(x.patient_id||'')}</span>
    </td>
    <td>Dr. ${esc(dn)}<br><span style="font-size:.7rem;color:var(--t3)">${esc(x.doctor_specialization||'General')}</span></td>
    <td style="font-family:var(--mono);font-size:.78rem">${esc(x.consultation_date||'—')}<br><span style="font-size:.7rem;color:var(--t4)">${esc(x.consultation_time||'')}</span></td>
    <td>
      ${meds.length
        ?`<span class="tbl-badge tbl-b-med"><i class="fas fa-pills"></i> ${meds.length} med${meds.length!==1?'s':''}</span>
          <div style="font-size:.7rem;color:var(--t3);margin-top:.25rem">${meds.slice(0,2).map(m=>esc(m.name)).join(', ')}${meds.length>2?' +more':''}</div>`
        :'<span style="font-size:.76rem;color:var(--t4)">None</span>'}
    </td>
    <td><span class="tbl-badge ${hi?'tbl-b-img':'tbl-b-nimg'}">${hi?'<i class="fas fa-image"></i> Yes':'No image'}</span></td>
    <td style="text-align:center;">
      <div style="display:flex;gap:.35rem;justify-content:center;">
        <button class="tbl-expand-btn" id="eb-${i}" onclick="toggleExpand(${i})" title="Expand row"><i class="fas fa-chevron-down"></i></button>
        <button class="rx-print-ic" onclick="printRx(${i})" title="Print this prescription"><i class="fas fa-print"></i></button>
      </div>
    </td>
  </tr>
  <tr class="expand-row" id="er-${i}" style="display:none">
    <td colspan="6">
      <div class="rx-expand-inner">
        <div class="rx-exp-block">
          <div class="rx-exp-block-hdr"><i class="fas fa-id-card"></i> Patient Info</div>
          <div class="rx-exp-block-body">
            <div class="rx-exp-field"><span class="rx-exp-fl">Patient ID</span><span class="rx-exp-fv" style="font-family:var(--mono);color:var(--p-base)">${esc(x.patient_id||'—')}</span></div>
            ${x.patient_sex?`<div class="rx-exp-field"><span class="rx-exp-fl">Sex</span><span class="rx-exp-fv">${esc(x.patient_sex)}</span></div>`:''}
            ${x.patient_age?`<div class="rx-exp-field"><span class="rx-exp-fl">Age</span><span class="rx-exp-fv">${esc(x.patient_age)} yrs</span></div>`:''}
            <div class="rx-exp-field"><span class="rx-exp-fl">Consult ID</span><span class="rx-exp-fv" style="font-family:var(--mono)">${esc(x.consultation_id||'—')}</span></div>
          </div>
        </div>
        <div class="rx-exp-block">
          <div class="rx-exp-block-hdr"><i class="fas fa-pills"></i> Medications</div>
          <div class="rx-exp-block-body">
            ${meds.length?meds.map(m=>`<div class="rx-exp-field">
              <span class="rx-exp-fl">${esc(m.name||'—')}</span>
              <span class="rx-exp-fv">${[m.dosage,m.freq,m.duration?m.duration+'d':''].filter(Boolean).join(' · ')}</span>
            </div>`).join('')
            :'<p style="font-size:.78rem;color:var(--t4);text-align:center;padding:.5rem 0">No treatment plan</p>'}
          </div>
        </div>
        <div class="rx-exp-block">
          <div class="rx-exp-block-hdr"><i class="fas fa-image"></i> Prescription Image</div>
          <div class="rx-exp-block-body">
            ${hi?`<img class="rx-exp-img" src="${esc(x.prescription_image)}" alt="Rx" onclick="openLbx('${esc(x.prescription_image)}')" onerror="this.style.display='none'">`
              :`<p style="font-size:.78rem;color:var(--t4);text-align:center;padding:.75rem 0"><i class="fas fa-file-image" style="display:block;font-size:1.5rem;opacity:.2;margin-bottom:.3rem"></i>No image</p>`}
          </div>
        </div>
      </div>
    </td>
  </tr>`;
}

function toggleExpand(i){
  const er=document.getElementById('er-'+i);
  const eb=document.getElementById('eb-'+i);
  const dr=document.getElementById('dr-'+i);
  const open=er.style.display==='none';
  er.style.display=open?'table-row':'none';
  eb.classList.toggle('open',open);
  dr.classList.toggle('expanded',open);
  eb.innerHTML=open?'<i class="fas fa-chevron-up"></i>':'<i class="fas fa-chevron-down"></i>';
}

/* ════════════════════════════════════════════════════════════
   VIEW C — ACTIVITY FEED
   ════════════════════════════════════════════════════════════ */
function renderFeed(){
  const el=document.getElementById('feed-body');
  if(!RENDERED.length){ el.innerHTML=emptyHTML(); return; }
  const groups={};
  RENDERED.forEach((x,i)=>{ const d=x.consultation_date||'Unknown Date'; if(!groups[d])groups[d]=[]; groups[d].push({x,i}); });
  el.innerHTML=Object.entries(groups).map(([date,items])=>`
    <div class="rx-feed-day">
      <div class="rx-feed-daylbl"><i class="far fa-calendar-alt" style="color:var(--p-base)"></i> ${esc(date)}</div>
      ${items.map(({x,i})=>feedEntry(x,i)).join('')}
    </div>`).join('');
}

function feedEntry(x,i){
  const pn=(x.patient_name&&x.patient_name.trim())?x.patient_name.trim():x.patient_id;
  const dn=(x.doctor_name&&x.doctor_name.trim())?x.doctor_name.trim():'Unknown Doctor';
  const meds=parsePlan(x.soap_plan);
  const hi=x.prescription_image&&x.prescription_image.length>10;
  const c=col(x.patient_id);
  const docImg=x.doctor_photo&&x.doctor_photo.length>5
    ?`<img src="${esc(x.doctor_photo)}" alt="" onerror="this.style.display='none'">`
    :`<i class="fas fa-user-md" style="font-size:.65rem;"></i>`;
  return `<div class="rx-fentry-wrap">
    <div class="rx-fentry">
      <div class="fe-time">
        <div class="fe-time-val">${esc(x.consultation_time||'—')}</div>
        <div class="fe-time-dot"></div>
      </div>
      <div class="fe-patient">
        <div class="fe-avt" style="background:${c}">${ini(pn)}</div>
        <div><div class="fe-pname">${esc(pn)}</div><div class="fe-pid">${esc(x.patient_id||'')}</div></div>
      </div>
      <div class="fe-doctor">
        <div class="fe-dav">${docImg}</div>
        <div><div class="fe-dname">Dr. ${esc(dn)}</div><div class="fe-dspec">${esc(x.doctor_specialization||'General')}</div></div>
      </div>
      <div class="fe-meds">
        ${meds.length
          ?meds.slice(0,4).map(m=>`<span class="fe-med"><i class="fas fa-capsules"></i>${esc(m.name||'')}</span>`).join('')+(meds.length>4?`<span class="fe-med-more">+${meds.length-4} more</span>`:'')
          :`<span class="fe-noplan">No treatment plan</span>`}
      </div>
      <div class="fe-actions">
        <span class="fe-imgbadge ${hi?'has':'no'}">${hi?'<i class="fas fa-image"></i> Image':'No img'}</span>
        <button class="fe-view-btn" onclick="toggleFeedExpand(${i},this)"><i class="fas fa-chevron-down"></i> Details</button>
        <button class="rx-print-btn" onclick="printRx(${i})" style="width:100%;justify-content:center;">
          <i class="fas fa-print"></i> Print
        </button>
      </div>
    </div>
    <div class="rx-fexpand" id="fe-expand-${i}">
      <div class="rx-exp-block">
        <div class="rx-exp-block-hdr"><i class="fas fa-id-card"></i> Patient Details</div>
        <div class="rx-exp-block-body">
          <div class="rx-exp-field"><span class="rx-exp-fl">Patient ID</span><span class="rx-exp-fv" style="font-family:var(--mono);color:var(--p-base)">${esc(x.patient_id||'—')}</span></div>
          ${x.patient_sex?`<div class="rx-exp-field"><span class="rx-exp-fl">Sex</span><span class="rx-exp-fv">${esc(x.patient_sex)}</span></div>`:''}
          ${x.patient_age?`<div class="rx-exp-field"><span class="rx-exp-fl">Age</span><span class="rx-exp-fv">${esc(x.patient_age)} years</span></div>`:''}
          <div class="rx-exp-field"><span class="rx-exp-fl">Specialization</span><span class="rx-exp-fv">${esc(x.doctor_specialization||'General')}</span></div>
        </div>
      </div>
      <div class="rx-exp-block">
        <div class="rx-exp-block-hdr"><i class="fas fa-pills"></i> Medications</div>
        <div class="rx-exp-block-body">
          ${meds.length?meds.map(m=>`<div class="rx-exp-field">
            <span class="rx-exp-fl">${esc(m.name||'—')}</span>
            <span class="rx-exp-fv">${[m.dosage,m.freq,m.duration?m.duration+'d':''].filter(Boolean).join(' · ')}</span>
          </div>`).join('')
          :'<p style="font-size:.78rem;color:var(--t4);padding:.25rem 0">No treatment plan</p>'}
        </div>
      </div>
      <div class="rx-exp-block">
        <div class="rx-exp-block-hdr"><i class="fas fa-image"></i> Prescription Image</div>
        <div class="rx-exp-block-body">
          ${hi?`<img src="${esc(x.prescription_image)}" style="width:100%;border-radius:6px;cursor:zoom-in;border:1px solid var(--border)" onclick="openLbx('${esc(x.prescription_image)}')" alt="Rx" onerror="this.style.display='none'">`
            :`<p style="font-size:.78rem;color:var(--t4);text-align:center;padding:.75rem 0"><i class="fas fa-file-image" style="display:block;font-size:1.4rem;opacity:.2;margin-bottom:.3rem"></i>No image attached</p>`}
        </div>
      </div>
    </div>
  </div>`;
}

function toggleFeedExpand(i,btn){
  const panel=document.getElementById('fe-expand-'+i);
  const open=!panel.classList.contains('open');
  panel.classList.toggle('open',open);
  btn.innerHTML=open?'<i class="fas fa-chevron-up"></i> Close':'<i class="fas fa-chevron-down"></i> Details';
}

/* ── Modal ───────────────────────────────────────────────── */
function closeModal(){ document.getElementById('rxModal').classList.remove('open'); document.body.style.overflow=''; }

/* ── Lightbox ────────────────────────────────────────────── */
function openLbx(src){ document.getElementById('lbx-img').src=src; document.getElementById('lightbox').classList.add('open'); }
function closeLbx(){ document.getElementById('lightbox').classList.remove('open'); }

/* ── Empty / Error ───────────────────────────────────────── */
function emptyHTML(){
  return `<div class="rx-empty"><div class="rx-empty-ic"><i class="fas fa-file-medical-alt"></i></div><h3>No Records Found</h3><p>Try adjusting your search or filters.</p></div>`;
}
function showErr(msg){
  ['sp-list','tbl-body','feed-body'].forEach(id=>{ const el=document.getElementById(id); if(el) el.innerHTML=`<div class="rx-empty"><div class="rx-empty-ic" style="background:#FEE2E2;color:var(--s-rose);border-color:#FECACA"><i class="fas fa-exclamation-triangle"></i></div><h3>Load Failed</h3><p>${esc(msg)}</p></div>`; });
  document.getElementById('cnt').textContent='Error';
  toast(msg,'err');
}
</script>
