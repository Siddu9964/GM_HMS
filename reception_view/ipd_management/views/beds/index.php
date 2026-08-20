<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Notice Board - Floor Overview | GM HMS</title>
    <!-- Global GM Theme -->
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="/GM_HMS/reception_view/assets/css/reception_dashboard.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">
<style>
:root{--em:#0E5C4A;--em-d:#0a4537;--em-m:#1a7a5e;--em-l:#EAF7F1;--em-xl:#f0faf6;--cream:#F7F5EE;--bg:#EEF3F2;--card:#FFFFFF;--border:#E2EBE8;--border-lt:#f0f4f3;--text:#1A2B26;--text-md:#3D5A52;--text-lt:#7A9490;--red:#DC3545;--red-l:#FEE8EA;--amber:#F59E0B;--amber-l:#FEF9EC;--blue:#3B82F6;--blue-l:#EFF6FF;--purple:#7C3AED;--purple-l:#F5F3FF;--sh-sm:0 1px 4px rgba(14,92,74,.06),0 2px 8px rgba(0,0,0,.04);--sh-md:0 4px 16px rgba(14,92,74,.10),0 2px 8px rgba(0,0,0,.06);--sh-lg:0 8px 32px rgba(14,92,74,.14),0 4px 16px rgba(0,0,0,.08);--r-sm:8px;--r-md:14px;--r-lg:18px;--r-xl:24px;--sidebar-w:270px;--header-h:70px}
*,*::before,*::after{box-sizing:border-box}
html,body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);font-size:14px;margin:0;padding:0;overflow-x:hidden}

/* Global Reception Layout Responsive Overrides */
@media (max-width: 992px) {
    .reception-main-content {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        min-width: 0 !important;
    }
}

/* Page-level scroll container */
.beds-page-wrap{
    display:flex;
    flex-direction:column;
    min-height:calc(100vh - 65px);
    flex:1;
    overflow:hidden;
    padding:16px 20px;
}

/* Notice Board page-level banner & Header Row */
.nb-header-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.nb-page-banner{
    background:linear-gradient(135deg,var(--em) 0%,#0a4537 100%);
    border-radius:var(--r-md);
    padding:8px 18px;
    display:flex;
    align-items:center;
    gap:12px;
    box-shadow:0 3px 14px rgba(14,92,74,.3);
    flex:1;
    min-width:240px;
}
.nb-page-banner i{font-size:16px;color:rgba(255,255,255,.9);flex-shrink:0}
.nb-page-banner-text{line-height:1.25;min-width:0}
.nb-page-banner-text strong{
    font-size:clamp(12.5px, 1.4vw, 14.5px);
    font-weight:700;
    color:#fff;
    display:block;
    letter-spacing:0.2px;
}
.nb-page-banner-text span{
    font-size:clamp(10px, 1.1vw, 11px);
    color:rgba(255,255,255,.75);
    display:block;
}
.nb-header-controls{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    flex-shrink:0;
}
.nb-hdr-pill{
    background:var(--em-l);
    border:1.5px solid var(--border);
    border-radius:var(--r-md);
    padding:5px 12px;
    display:flex;
    align-items:center;
    gap:6px;
}
.nb-hdr-pill .lbl{font-size:9.5px;color:var(--text-lt);font-weight:700;text-transform:uppercase;letter-spacing:.4px;line-height:1;}
.nb-hdr-pill .val{font-size:12.5px;font-weight:700;color:var(--em);line-height:1;}
.nb-refresh-btn{
    height:36px;
    background:linear-gradient(135deg,var(--em) 0%,var(--em-d) 100%);
    border:none;
    border-radius:var(--r-md);
    color:#fff;
    font-size:12px;
    font-weight:600;
    padding:0 14px;
    display:flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    transition:all .2s;
    font-family:'Inter',sans-serif;
    box-shadow:0 2px 8px rgba(14,92,74,.25);
    white-space:nowrap;
}
.nb-refresh-btn:hover{background:linear-gradient(135deg,var(--em-m) 0%,var(--em) 100%);transform:translateY(-1px);}
.nb-refresh-sub{font-size:9.5px;color:rgba(255,255,255,.72);line-height:1;}

/* KPI Strip Responsive Grid */
.kpi-strip{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:12px;
    margin-bottom:14px;
    flex-shrink:0;
    width:100%;
}
.kpi-card{
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:var(--r-md);
    padding:12px 14px;
    display:flex;
    align-items:center;
    gap:12px;
    transition:all .2s;
    position:relative;
    overflow:hidden;
    box-shadow:var(--sh-sm);
    min-width:0;
}
.kpi-card:hover{box-shadow:var(--sh-md);transform:translateY(-2px);border-color:var(--em-l)}
.kpi-icon{
    width:42px;
    height:42px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    flex-shrink:0;
}
.kpi-info{min-width:0;flex:1;}
.kpi-num{
    font-size:clamp(19px, 2.2vw, 24px);
    font-weight:900;
    line-height:1;
    color:var(--text);
    font-family:'Poppins',sans-serif;
    white-space:nowrap;
}
.kpi-lbl{
    font-size:10px;
    color:var(--text-lt);
    font-weight:700;
    margin-top:2px;
    text-transform:uppercase;
    letter-spacing:.3px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.kpi-sub{
    font-size:9.5px;
    color:var(--text-lt);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.kpi-beds .kpi-icon{background:var(--em-l);color:var(--em)}
.kpi-occ .kpi-icon{background:var(--red-l);color:var(--red)}
.kpi-avail .kpi-icon{background:#e8f8f1;color:#15803d}
.kpi-icu .kpi-icon{background:var(--purple-l);color:var(--purple)}
.kpi-wards .kpi-icon{background:var(--blue-l);color:var(--blue)}

/* Notice Board Body Wrap */
.body-wrap{
    display:flex;
    flex-direction:column;
    flex:1;
    overflow:hidden;
    min-height:0;
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:var(--r-xl);
    box-shadow:var(--sh-sm);
}
.floor-tabs-wrap{
    background:#fff;
    padding:10px 20px;
    border-bottom:1.5px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:flex-start;
    gap:10px;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    scroll-snap-type:x mandatory;
    flex-shrink:0;
    scrollbar-width:thin;
}
.floor-tabs-wrap::-webkit-scrollbar{height:4px}
.floor-tabs-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px}
.floor-tab{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 14px;
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:var(--r-md);
    cursor:pointer;
    transition:all .2s;
    white-space:nowrap;
    min-width:max-content;
    scroll-snap-align:start;
    flex-shrink:0;
}
.floor-tab:hover{border-color:var(--em-l);box-shadow:var(--sh-sm)}
.floor-tab.active{background:var(--em-l);border-color:var(--em)}
.ft-icon{
    width:32px;
    height:32px;
    border-radius:8px;
    background:rgba(14,92,74,.08);
    color:var(--em);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:13px;
    flex-shrink:0;
}
.floor-tab.active .ft-icon{background:var(--em);color:#fff}
.ft-info{display:flex;flex-direction:column}
.ft-name{font-size:12.5px;font-weight:700;color:var(--text)}
.ft-meta{font-size:10px;color:var(--text-lt);font-weight:500;margin-top:1px}

@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.fs-skeleton{height:52px;border-radius:8px;margin-bottom:5px;background:linear-gradient(90deg,#eef7f3 25%,#e0f0ea 50%,#eef7f3 75%);background-size:200% 100%;animation:shimmer 1.4s ease-in-out infinite}

/* Main Area & Breadcrumbs */
.main-area{
    flex:1;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    background:var(--bg);
    min-height:0;
}
.breadcrumb-bar{
    background:#fff;
    border-bottom:1px solid var(--border);
    padding:8px 20px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    font-size:12.5px;
    font-weight:500;
    color:var(--text-lt);
    flex-shrink:0;
    flex-wrap:wrap;
}
.bc-left{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.legend-strip{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}
.legend-strip-title{
    font-size:9.5px;
    font-weight:700;
    color:var(--text-lt);
    text-transform:uppercase;
    letter-spacing:.4px;
    display:flex;
    align-items:center;
    gap:4px;
    margin-right:2px;
}
.legend-strip-title i{color:var(--em);font-size:10px;}
.legend-item{display:flex;align-items:center;gap:5px;font-size:10.5px;color:var(--text-md);white-space:nowrap;}
.og-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.bc-item{display:flex;align-items:center;gap:5px;cursor:pointer;padding:3px 8px;border-radius:6px;transition:all .15s;white-space:nowrap}
.bc-item:hover{background:var(--em-l);color:var(--em)}
.bc-item.active{color:var(--em);font-weight:700;pointer-events:none}
.bc-sep{font-size:8px;color:#cdd8d5}

/* Dynamic Scrollable Area */
.dynamic-scroll{
    flex:1;
    overflow-y:auto;
    padding:clamp(12px, 2vw, 20px);
    -webkit-overflow-scrolling:touch;
}
.dynamic-scroll::-webkit-scrollbar{width:4px}
.dynamic-scroll::-webkit-scrollbar-thumb{background:var(--border);border-radius:10px}

/* Grid & Cards (Room Types & Wards) */
.nb-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
    gap:14px;
    width:100%;
}
.nb-card{
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:var(--r-md);
    display:flex;
    flex-direction:column;
    cursor:pointer;
    transition:all .22s;
    box-shadow:var(--sh-sm);
    position:relative;
    overflow:hidden;
}
.nb-card:hover{border-color:var(--em-m);box-shadow:var(--sh-lg);transform:translateY(-2px)}
.nb-card-top{padding:12px 14px 10px;border-bottom:1px dashed var(--border-lt);flex:1;background:linear-gradient(to bottom,var(--bg) 0%,#fff 100%)}
.nb-card-icon{width:30px;height:30px;border-radius:8px;background:var(--em-l);display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--em);margin-bottom:6px}
.nb-card-name{font-size:12.5px;font-weight:700;color:var(--text);margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.nb-card-sub{font-size:10px;color:var(--text-lt)}
.nb-card-stats{display:grid;grid-template-columns:repeat(3, 1fr);gap:4px;margin-top:8px}
.nb-stat{background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:5px 2px;text-align:center;min-width:0;}
.nb-stat-num{font-size:14px;font-weight:800;line-height:1;font-family:'Poppins',sans-serif}
.nb-stat-num.occ{color:var(--red)}.nb-stat-num.avl{color:#16a34a}.nb-stat-num.tot{color:var(--text)}
.nb-stat-lbl{font-size:8px;font-weight:700;color:var(--text-lt);text-transform:uppercase;letter-spacing:.2px;margin-top:3px}
.nb-card-foot{padding:8px 14px 10px;display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--text-lt);font-weight:600}
.nb-prog{width:100%;height:4px;background:var(--border);border-radius:10px;overflow:hidden;margin-top:6px}
.nb-prog-fill{height:100%;border-radius:10px;transition:width .5s ease}

/* Section Header */
.sec-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:14px;
    gap:10px;
    flex-wrap:wrap;
}
.sec-header h2{
    font-size:clamp(14px, 1.6vw, 17px);
    font-weight:800;
    color:var(--text);
    margin:0;
    display:flex;
    align-items:center;
    gap:8px;
    font-family:'Poppins',sans-serif;
}
.sec-header h2 i{color:var(--em);font-size:14px}
.sec-header-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}

/* Empty State */
.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 16px;text-align:center;gap:10px;grid-column:1/-1;width:100%;}
.empty-icon{width:64px;height:64px;border-radius:50%;background:var(--em-l);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--em);margin-bottom:4px}
.empty-state h3{font-size:16px;font-weight:700;color:var(--text);margin:0}
.empty-state p{font-size:12px;color:var(--text-lt);margin:0;max-width:320px;line-height:1.5;}
.skeleton{background:linear-gradient(90deg,#e8f0ed 25%,#d8eae5 50%,#e8f0ed 75%);background-size:200% 100%;animation:shimmer 1.4s ease-in-out infinite;border-radius:8px}

/* Patient / Bed Modal Panel */
.patient-panel-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:9999;
    display:flex;
    align-items:center;
    justify-content:center;
    backdrop-filter:blur(4px);
    animation:fadeIn .2s ease;
    padding:clamp(8px, 2vw, 20px);
}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.patient-panel{
    background:#fff;
    border:none;
    border-radius:var(--r-xl);
    overflow:hidden;
    box-shadow:0 24px 64px rgba(0,0,0,.3);
    width:100%;
    max-width:940px;
    max-height:min(88vh, 850px);
    display:flex;
    flex-direction:column;
    animation:modalSlideUp .3s cubic-bezier(.175,.885,.32,1.275) both;
}
@keyframes modalSlideUp{from{opacity:0;transform:translateY(24px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.pp-header{
    background:linear-gradient(135deg,var(--em) 0%,var(--em-d) 100%);
    padding:12px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}
.pp-header-left{display:flex;align-items:center;gap:10px;min-width:0;flex:1;}
.pp-header-icon{width:32px;height:32px;background:rgba(255,255,255,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;flex-shrink:0;}
.pp-header-title{font-size:clamp(12.5px, 1.3vw, 14px);font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.pp-header-badge{background:#fff;color:var(--em);border-radius:20px;padding:3px 10px;font-size:10.5px;font-weight:700;white-space:nowrap;flex-shrink:0;}
.pp-hdr-close-btn{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.2);border:none;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;cursor:pointer;transition:all .18s;flex-shrink:0;}
.pp-hdr-close-btn:hover{background:rgba(255,255,255,.35);transform:scale(1.05)}

.pp-toolbar{
    padding:10px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    border-bottom:1px solid var(--border);
    flex-wrap:wrap;
    background:#fafcfb;
}
.pp-search{position:relative;flex:1 1 200px;max-width:320px;min-width:160px;}
.pp-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--text-lt);pointer-events:none}
.pp-search input{width:100%;height:34px;border:1.5px solid var(--border);border-radius:8px;padding:0 10px 0 30px;font-size:12px;font-family:'Inter',sans-serif;outline:none;transition:border-color .18s;background:#fff}
.pp-search input:focus{border-color:var(--em)}
.pp-search input::placeholder{color:var(--text-lt)}
.pp-toolbar-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.pp-view-btn{height:34px;padding:0 12px;border:1.5px solid var(--border);border-radius:8px;background:#fff;font-size:11.5px;font-weight:600;color:var(--text-lt);display:flex;align-items:center;gap:6px;cursor:pointer;transition:all .18s;font-family:'Inter',sans-serif;white-space:nowrap}
.pp-view-btn.active,.pp-view-btn:hover{background:var(--em);color:#fff;border-color:var(--em)}
.pp-export-btn{height:34px;padding:0 12px;border:1.5px solid var(--border);border-radius:8px;background:#fff;font-size:11.5px;font-weight:600;color:var(--text-md);display:flex;align-items:center;gap:6px;cursor:pointer;transition:all .18s;font-family:'Inter',sans-serif;white-space:nowrap}
.pp-export-btn:hover{border-color:var(--em);color:var(--em)}
.pp-body{padding:clamp(12px, 2vw, 18px);overflow-y:auto;-webkit-overflow-scrolling:touch;}

/* Bed Grid inside Modal */
.bed-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
    gap:14px;
    width:100%;
}
.bed-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-sm);transition:all .22s;display:flex;flex-direction:column;min-width:0;}
.bed-card:hover{box-shadow:var(--sh-lg);transform:translateY(-2px)}
.bed-card-stripe{height:4px;width:100%;}
.bed-card.st-available .bed-card-stripe{background:linear-gradient(90deg,#16a34a,#22c55e)}
.bed-card.st-occupied .bed-card-stripe{background:linear-gradient(90deg,var(--red),#f87171)}
.bed-card.st-blocked .bed-card-stripe{background:linear-gradient(90deg,#6b7280,#9ca3af)}
.bed-card.st-maintenance .bed-card-stripe{background:linear-gradient(90deg,var(--amber),#fcd34d)}
.bed-card-top{padding:10px 12px 6px;display:flex;align-items:center;justify-content:space-between;gap:6px;}
.bed-card-num{font-size:13px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:5px;white-space:nowrap;}
.bed-card-num i{font-size:12px;color:var(--em)}
.bed-badge{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;padding:2px 8px;border-radius:20px;white-space:nowrap}
.st-available .bed-badge{background:#dcfce7;color:#15803d}
.st-occupied .bed-badge{background:var(--red-l);color:var(--red)}
.st-blocked .bed-badge{background:#f3f4f6;color:#6b7280}
.st-maintenance .bed-badge{background:var(--amber-l);color:#92400e}
.bed-card-illus{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 12px;gap:6px}
.bed-illus-svg{width:100%;max-width:110px;height:auto;max-height:65px;filter:drop-shadow(0 3px 6px rgba(0,0,0,.06))}
.bed-patient-info{background:#fafcfb;border:1px solid var(--border);border-radius:8px;padding:6px 8px;font-size:11px;width:100%;word-break:break-word;}
.bed-patient-name{font-weight:700;font-size:11.5px;margin-bottom:2px;display:flex;align-items:center;gap:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.bed-patient-name i{color:var(--em);font-size:10px;flex-shrink:0;}
.bed-status-center{display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 0}
.bed-status-center i{font-size:20px}
.bed-status-center span{font-size:11px;font-weight:600}
.st-available .bed-status-center i,.st-available .bed-status-center span{color:#16a34a}
.st-maintenance .bed-status-center i,.st-maintenance .bed-status-center span{color:var(--amber)}
.st-blocked .bed-status-center i,.st-blocked .bed-status-center span{color:#6b7280}
.bed-ready-txt{font-size:10.5px;color:var(--text-lt);font-weight:500;text-align:center;}
.bed-card-action{padding:0 12px 12px}
.btn-admit{width:100%;padding:7px 10px;background:linear-gradient(135deg,var(--em) 0%,var(--em-d) 100%);border:none;border-radius:8px;color:#fff;font-size:11.5px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Inter',sans-serif;box-shadow:0 3px 8px rgba(14,92,74,.2)}
.btn-admit:hover{background:linear-gradient(135deg,var(--em-m) 0%,var(--em) 100%);transform:translateY(-1px)}
.btn-release{width:100%;padding:7px 10px;background:var(--red-l);border:1.5px solid #f5c6c4;border-radius:8px;color:var(--red);font-size:11.5px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Inter',sans-serif}
.btn-release:hover{background:var(--red);color:#fff;border-color:var(--red)}
.btn-change-status{width:100%;padding:7px 10px;background:var(--em-l);border:1.5px solid rgba(14,92,74,.15);border-radius:8px;color:var(--em);font-size:11.5px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Inter',sans-serif}
.btn-change-status:hover{background:var(--em);color:#fff;border-color:var(--em)}

/* Bed List View */
.bed-list{display:flex;flex-direction:column;gap:8px;width:100%;}
.bed-list-row{
    background:#fff;
    border:1.5px solid var(--border);
    border-radius:var(--r-md);
    padding:10px 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    transition:all .18s;
    flex-wrap:wrap;
}
.bed-list-row:hover{border-color:var(--em);background:var(--em-xl)}
.blr-left{display:flex;align-items:center;gap:10px;flex:1;min-width:200px;}
.blr-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.blr-num{font-size:13px;font-weight:800;min-width:70px;display:flex;align-items:center;gap:4px;}
.blr-patient{flex:1;font-size:11.5px;color:var(--text-md);min-width:140px;}
.blr-actions{display:flex;gap:6px;flex-shrink:0;}

/* Confirmation / Prompt Modal */
.hms-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(4px);padding:14px;}
.hms-overlay.open{display:flex}
.hms-modal{background:#fff;border-radius:var(--r-xl);padding:clamp(20px, 3.5vw, 30px) clamp(16px, 3vw, 24px);width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,.22);position:relative;animation:modalIn .22s cubic-bezier(.4,0,.2,1) both;border-top:5px solid var(--em)}
@keyframes modalIn{from{opacity:0;transform:scale(.93) translateY(-12px)}to{opacity:1;transform:scale(1) translateY(0)}}
.hms-modal-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:19px;margin:0 auto 12px}
.hms-modal-icon.danger{background:var(--red-l);color:var(--red)}
.hms-modal-icon.info{background:var(--em-l);color:var(--em)}
.hms-modal-title{font-size:16px;font-weight:700;color:var(--text);text-align:center;margin-bottom:6px}
.hms-modal-body{font-size:12.5px;color:var(--text-lt);text-align:center;margin-bottom:18px;line-height:1.5}
.hms-select-row{display:flex;flex-direction:column;gap:4px;margin-bottom:18px}
.hms-select-row label{font-size:10.5px;font-weight:700;color:var(--text-lt);text-transform:uppercase;letter-spacing:.4px}
.hms-select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--text);background:#fff;outline:none;cursor:pointer;transition:border-color .18s;width:100%;}
.hms-select:focus{border-color:var(--em)}
.hms-modal-btns{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;}
.hms-btn{padding:8px 20px;border-radius:8px;border:1.5px solid transparent;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .18s;font-family:'Inter',sans-serif;flex:1 1 100px;min-width:90px;text-align:center;}
.hms-btn.cancel{background:#f5f5f5;color:var(--text-lt);border-color:var(--border)}
.hms-btn.cancel:hover{background:#e8e8e8}
.hms-btn.green{background:linear-gradient(135deg,var(--em) 0%,var(--em-d) 100%);color:#fff}
.hms-btn.green:hover{background:linear-gradient(135deg,var(--em-m) 0%,var(--em) 100%)}
.hms-btn.red{background:var(--red);color:#fff;border-color:var(--red)}
.hms-btn.red:hover{background:#b91c1c}

/* ============================================================================
   RESPONSIVE MEDIA QUERIES (DESKTOP, LAPTOP, TABLET, MOBILE)
   ============================================================================ */

/* Large Desktop / High-Res Monitors (>= 1440px) */
@media (min-width: 1440px) {
    .nb-grid { grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
    .bed-grid { grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); }
}

/* Laptops & Standard Desktops (1100px - 1439px) */
@media (max-width: 1280px) {
    .kpi-num { font-size: 20px; }
    .kpi-card { padding: 10px 12px; gap: 8px; }
    .kpi-icon { width: 36px; height: 36px; font-size: 14px; }
}

/* Tablet Landscape & Small Laptops (850px - 1099px) */
@media (max-width: 1099px) {
    .kpi-strip {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    .kpi-strip .kpi-card:nth-child(4),
    .kpi-strip .kpi-card:nth-child(5) {
        grid-column: span 1;
    }
    .kpi-strip .kpi-card:nth-child(5) {
        grid-column: span 1;
    }
    .beds-page-wrap { padding: 12px 14px; }
}

/* Tablet Portrait & Mobile Landscape (640px - 849px) */
@media (max-width: 849px) {
    .nb-header-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .nb-header-controls {
        width: 100%;
        justify-content: space-between;
    }
    .nb-hdr-pill {
        flex: 1;
        justify-content: center;
    }
    .kpi-strip {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    .breadcrumb-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 8px 14px;
    }
    .legend-strip {
        width: 100%;
        justify-content: flex-start;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .nb-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }
    .bed-grid {
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 10px;
    }
}

/* Mobile Devices (<= 639px) */
@media (max-width: 639px) {
    .beds-page-wrap { padding: 10px 8px; }
    .nb-page-banner {
        padding: 8px 12px;
        gap: 8px;
    }
    .nb-page-banner-text strong {
        white-space: normal;
        font-size: 13px;
    }
    .nb-page-banner-text span {
        white-space: normal;
        font-size: 10px;
    }
    .nb-header-controls {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 6px;
    }
    .nb-hdr-pill {
        padding: 4px 8px;
        border-radius: 8px;
    }
    .nb-hdr-pill .lbl { font-size: 8.5px; }
    .nb-hdr-pill .val { font-size: 11px; }
    .nb-refresh-btn {
        padding: 0 10px;
        height: 32px;
        font-size: 11px;
    }
    .nb-refresh-sub { display: none; }
    
    /* 2-Column KPI grid on mobile */
    .kpi-strip {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        margin-bottom: 10px;
    }
    .kpi-strip .kpi-card:nth-child(5) {
        grid-column: span 2;
    }
    .kpi-card {
        padding: 8px 10px;
        gap: 8px;
        border-radius: 10px;
    }
    .kpi-icon {
        width: 32px;
        height: 32px;
        font-size: 13px;
        border-radius: 8px;
    }
    .kpi-num { font-size: 17px; }
    .kpi-lbl { font-size: 9px; }
    .kpi-sub { font-size: 8.5px; }

    /* Floor tabs on mobile */
    .floor-tabs-wrap {
        padding: 8px 10px;
        gap: 6px;
    }
    .floor-tab {
        padding: 6px 10px;
        border-radius: 8px;
        gap: 6px;
    }
    .ft-icon {
        width: 26px;
        height: 26px;
        font-size: 11px;
        border-radius: 6px;
    }
    .ft-name { font-size: 11.5px; }
    .ft-meta { font-size: 9px; }

    /* Wards / Room Grid on mobile */
    .nb-grid {
        grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
        gap: 8px;
    }
    .nb-card { border-radius: 10px; }
    .nb-card-top { padding: 10px 10px 8px; }
    .nb-card-name { font-size: 12px; }
    .nb-stat-num { font-size: 13px; }
    .nb-stat-lbl { font-size: 7.5px; }
    .nb-card-foot { padding: 6px 10px 8px; font-size: 9.5px; }

    /* Patient Modal on mobile */
    .patient-panel-overlay { padding: 6px; }
    .patient-panel {
        max-height: 94vh;
        border-radius: 14px;
    }
    .pp-header { padding: 10px 12px; }
    .pp-toolbar {
        padding: 8px 12px;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }
    .pp-search {
        max-width: 100%;
        min-width: 100%;
        flex: 1 1 100%;
    }
    .pp-toolbar-actions {
        width: 100%;
        justify-content: space-between;
    }
    .pp-view-btn, .pp-export-btn {
        flex: 1;
        justify-content: center;
        padding: 0 6px;
        font-size: 10.5px;
        height: 30px;
    }
    .bed-grid {
        grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
        gap: 8px;
    }
    .bed-card { border-radius: 10px; }
    .bed-card-top { padding: 8px 10px 4px; }
    .bed-card-num { font-size: 11.5px; }
    .bed-badge { font-size: 8px; padding: 2px 6px; }
    .bed-illus-svg { max-width: 85px; max-height: 50px; }
    .bed-patient-info { font-size: 10px; padding: 4px 6px; }
    .bed-patient-name { font-size: 10.5px; }
    .bed-card-action { padding: 0 8px 8px; }
    .btn-admit, .btn-release, .btn-change-status {
        font-size: 10.5px;
        padding: 6px 8px;
        border-radius: 6px;
    }

    /* Bed list row on mobile */
    .bed-list-row {
        padding: 8px 10px;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }
    .blr-left { min-width: 100%; }
    .blr-actions { width: 100%; }
    .blr-actions button { width: 100% !important; }
}

/* Extra Small Phones (<= 375px) */
@media (max-width: 375px) {
    .kpi-strip {
        grid-template-columns: 1fr;
    }
    .kpi-strip .kpi-card:nth-child(5) {
        grid-column: span 1;
    }
    .nb-grid {
        grid-template-columns: 1fr;
    }
    .bed-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<div class="reception-layout">
    <!-- Global Sidebar -->
    <?php include '../../../includes/reception_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="reception-main-content">
        <!-- Top Navbar -->
        <?php
        $pageTitle = 'Hospital Notice Board';
        include '../../../includes/reception_navbar.php';
        ?>
        <!-- Page Content Wrapper -->
        <div class="reception-content beds-page-wrap">

        <!-- Notice Board Banner & Info Row -->
        <div class="nb-header-row">
            <div class="nb-page-banner">
                <i class="fas fa-bullhorn"></i>
                <div class="nb-page-banner-text">
                    <strong>Hospital Notice Board &ndash; Floor Overview</strong>
                    <span>Live Bed &amp; Ward Status at a Glance</span>
                </div>
            </div>
            <div class="nb-header-controls">
                <div class="nb-hdr-pill"><i class="fas fa-calendar-alt" style="color:var(--em);font-size:11px;"></i><div><div class="lbl">Date</div><div class="val" id="hdrDate">-</div></div></div>
                <div class="nb-hdr-pill"><i class="fas fa-clock" style="color:var(--em);font-size:11px;"></i><div><div class="lbl">Time</div><div class="val" id="hdrTime">-</div></div></div>
                <button class="nb-refresh-btn" id="refreshBtn" onclick="triggerRefresh()">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i>
                    <div><div>Refresh</div><div class="nb-refresh-sub">Auto: 30s</div></div>
                </button>
            </div>
        </div>

        <!-- KPI Strip -->
        <div class="kpi-strip">
        <div class="kpi-card kpi-beds" id="kpi-total"><div class="kpi-icon"><i class="fas fa-bed"></i></div><div class="kpi-info"><div class="kpi-num skeleton" style="width:48px;height:24px;border-radius:6px;">&nbsp;</div><div class="kpi-lbl">Total Beds</div><div class="kpi-sub">All Floors</div></div></div>
        <div class="kpi-card kpi-occ" id="kpi-occ"><div class="kpi-icon"><i class="fas fa-user-injured"></i></div><div class="kpi-info"><div class="kpi-num skeleton" style="width:48px;height:24px;border-radius:6px;">&nbsp;</div><div class="kpi-lbl">Occupied</div><div class="kpi-sub" id="kpi-occ-pct">-</div></div></div>
        <div class="kpi-card kpi-avail" id="kpi-avail"><div class="kpi-icon"><i class="fas fa-check-circle"></i></div><div class="kpi-info"><div class="kpi-num skeleton" style="width:48px;height:24px;border-radius:6px;">&nbsp;</div><div class="kpi-lbl">Available</div><div class="kpi-sub" id="kpi-avail-pct">-</div></div></div>
        <div class="kpi-card kpi-icu" id="kpi-icu"><div class="kpi-icon"><i class="fas fa-heartbeat"></i></div><div class="kpi-info"><div class="kpi-num skeleton" style="width:48px;height:24px;border-radius:6px;">&nbsp;</div><div class="kpi-lbl">ICU / Emerg.</div><div class="kpi-sub">Critical Units</div></div></div>
        <div class="kpi-card kpi-wards" id="kpi-wards"><div class="kpi-icon"><i class="fas fa-layer-group"></i></div><div class="kpi-info"><div class="kpi-num skeleton" style="width:48px;height:24px;border-radius:6px;">&nbsp;</div><div class="kpi-lbl">Total Wards</div><div class="kpi-sub">Active Wards</div></div></div>
        </div><!-- /kpi-strip -->

        <!-- Notice Board Body -->
        <div class="body-wrap">
            <div class="floor-tabs-wrap" id="floorTabs">
                <div class="skeleton" style="width:120px;height:40px;border-radius:8px;"></div>
                <div class="skeleton" style="width:120px;height:40px;border-radius:8px;"></div>
                <div class="skeleton" style="width:120px;height:40px;border-radius:8px;"></div>
            </div>
            <main class="main-area">
                <div class="breadcrumb-bar" id="breadcrumbBar">
                    <div class="bc-left"><div class="bc-item active"><i class="fas fa-hospital" style="font-size:11px;"></i> Hospital</div></div>
                    <div class="legend-strip">
                        <div class="legend-strip-title"><i class="fas fa-circle-info"></i> Occupancy Guide</div>
                        <div class="legend-item"><div class="og-dot" style="background:#16a34a;"></div> 0-25%</div>
                        <div class="legend-item"><div class="og-dot" style="background:#eab308;"></div> 26-50%</div>
                        <div class="legend-item"><div class="og-dot" style="background:#f97316;"></div> 51-75%</div>
                        <div class="legend-item"><div class="og-dot" style="background:#dc2626;"></div> 76-100%</div>
                    </div>
                </div>
                <div class="dynamic-scroll" id="dynamicView">
                    <div class="empty-state"><div class="empty-icon"><i class="fas fa-bed"></i></div><h3>Select a Floor</h3><p>Choose a floor from the tabs above to view live ward details and manage bed allocation.</p></div>
                </div>
            </main>
        </div><!-- /body-wrap -->
    </div><!-- /reception-main-content -->
</div><!-- /reception-layout -->
<div id="hmsOverlay" class="hms-overlay"><div class="hms-modal" id="hmsModalContent"></div></div><script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="../../public/assets/js/ipd_main.js"></script>
<script>
function updateClock(){var n=new Date();document.getElementById('hdrDate').textContent=n.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'});document.getElementById('hdrTime').textContent=n.toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit'});}
updateClock();setInterval(updateClock,1000);
var hospitalData={floors:{},stats:{totalFloors:0,totalWards:0,totalRoomTypes:0,totalRooms:0,totalBeds:0,occupied:0,available:0,maintenance:0,blocked:0,icu:0}};
var currentView={level:'hospital',floor:null,ward:null,roomType:null,room:null};
var patientPanelWard=null;var viewMode='grid';
$(document).ready(function(){loadBedData();setInterval(function(){loadBedData(true);},30000);});
function triggerRefresh(){var icon=document.getElementById('refreshIcon');var btn=document.getElementById('refreshBtn');icon.classList.add('fa-spin');btn.style.pointerEvents='none';loadBedData(false);setTimeout(function(){icon.classList.remove('fa-spin');btn.style.pointerEvents='';},1200);}
function loadBedData(isRefresh){
    isRefresh=isRefresh||false;
    IPD.ajax('beds','GET').then(function(r){
        var beds=r.data.beds||[];
        buildHierarchy(beds);renderFloorTabs();renderKPIs();
        if(!isRefresh){var fk=Object.keys(hospitalData.floors);if(fk.length>0)setTimeout(function(){resetView();},100);}
        else{updateFloorMeta();refreshCurrentView();}
    }).catch(function(err){IPD.toast(err.message||'Failed to load bed data','error');});
}
function buildHierarchy(beds){
    hospitalData={floors:{},stats:{totalFloors:0,totalWards:0,totalRoomTypes:0,totalRooms:0,totalBeds:0,occupied:0,available:0,maintenance:0,blocked:0,icu:0}};
    var uF=new Set(),uW=new Set(),uT=new Set(),uR=new Set();
    beds.forEach(function(bed){
        var fN=bed.floor_name||'Unassigned',wN=bed.ward_name||'Unassigned Ward',rT=bed.room_type||'General',rNm=bed.room_number||'0';
        var s=(bed.bed_status||'Available').toLowerCase();
        if(s==='occupied'&&!bed.patient_id)s='available';
        var norm='Available';
        if(s==='occupied')norm='Occupied';if(s==='blocked')norm='Blocked';
        if(s==='maintenance'||s==='maintainance')norm='Maintenance';
        if(!hospitalData.floors[fN]){hospitalData.floors[fN]={name:fN,number:bed.floor_number||0,wards:{},stats:{total:0,occ:0,avail:0}};uF.add(fN);}
        var fl=hospitalData.floors[fN];
        if(!fl.wards[wN]){fl.wards[wN]={name:wN,roomTypes:{},stats:{total:0,occ:0,avail:0}};uW.add(fN+'_'+wN);}
        var w=fl.wards[wN];
        if(!w.roomTypes[rT]){w.roomTypes[rT]={name:rT,rooms:{},stats:{total:0,occ:0,avail:0},charges:{bed:bed.amount_per_day||0,nursing:bed.nursig_charge||0,doctor:bed.doctor_charge||0,service:bed.service_charge||0,total:bed.total_bed_amount||0}};uT.add(fN+'_'+wN+'_'+rT);}
        var rt=w.roomTypes[rT];
        if(!rt.rooms[rNm]){rt.rooms[rNm]={number:rNm,name:bed.room_name,type:rT,beds:[],stats:{total:0,occ:0,avail:0}};uR.add(fN+'_'+wN+'_'+rT+'_'+rNm);}
        var room=rt.rooms[rNm];
        var bc=Object.assign({},bed);bc.normalized_status=norm;room.beds.push(bc);
        hospitalData.stats.totalBeds++;fl.stats.total++;w.stats.total++;rt.stats.total++;room.stats.total++;
        if(norm==='Occupied'){hospitalData.stats.occupied++;fl.stats.occ++;w.stats.occ++;rt.stats.occ++;room.stats.occ++;
            if(wN.toLowerCase().indexOf('icu')>=0||wN.toLowerCase().indexOf('emergency')>=0||rT.toLowerCase().indexOf('icu')>=0||rT.toLowerCase().indexOf('emergency')>=0)hospitalData.stats.icu++;}
        else if(norm==='Available'){hospitalData.stats.available++;fl.stats.avail++;w.stats.avail++;rt.stats.avail++;room.stats.avail++;}
        else if(norm==='Blocked')hospitalData.stats.blocked++;
        else if(norm==='Maintenance')hospitalData.stats.maintenance++;
    });
    hospitalData.stats.totalFloors=uF.size;hospitalData.stats.totalWards=uW.size;hospitalData.stats.totalRoomTypes=uT.size;hospitalData.stats.totalRooms=uR.size;
}
function renderKPIs(){
    var s=hospitalData.stats;
    var op=s.totalBeds>0?Math.round((s.occupied/s.totalBeds)*100):0;
    var ap=s.totalBeds>0?Math.round((s.available/s.totalBeds)*100):0;
    $('#kpi-total .kpi-num').removeClass('skeleton').css({width:'',height:''}).text(s.totalBeds);
    $('#kpi-occ .kpi-num').removeClass('skeleton').css({width:'',height:''}).text(s.occupied);
    $('#kpi-avail .kpi-num').removeClass('skeleton').css({width:'',height:''}).text(s.available);
    $('#kpi-icu .kpi-num').removeClass('skeleton').css({width:'',height:''}).text(s.icu);
    $('#kpi-wards .kpi-num').removeClass('skeleton').css({width:'',height:''}).text(s.totalWards);
    $('#kpi-occ-pct').text(op+'% Occupancy');$('#kpi-avail-pct').text(ap+'% Vacant');
}
var FLOOR_ICONS={ground:'fa-home',first:'fa-1',second:'fa-2',third:'fa-3',fourth:'fa-4',fifth:'fa-5',icu:'fa-heartbeat',emergency:'fa-triangle-exclamation'};
function getFloorIcon(name){var nl=(name||'').toLowerCase();var keys=Object.keys(FLOOR_ICONS);for(var i=0;i<keys.length;i++){if(nl.indexOf(keys[i])>=0)return FLOOR_ICONS[keys[i]];}return 'fa-layer-group';}
function renderFloorTabs(){
    var list=$('#floorTabs');list.empty();
    Object.values(hospitalData.floors).forEach(function(floor){
        var wc=Object.keys(floor.wards).length;
        var isAct=currentView.floor===floor.name?'active':'';
        var icon=getFloorIcon(floor.name);
        var isIcu=floor.name.toLowerCase().indexOf('icu')>=0||floor.name.toLowerCase().indexOf('emergency')>=0;
        var iStyle=isIcu?' style="color:var(--red);"':'';
        var item=$('<div class="floor-tab '+isAct+'" data-floor="'+floor.name+'"><div class="ft-icon"'+iStyle+'><i class="fas '+icon+'"></i></div><div class="ft-info"><div class="ft-name">'+floor.name+'</div><div class="ft-meta">'+wc+' Ward'+(wc!==1?'s':'')+' &bull; '+floor.stats.total+' Beds</div></div></div>');
        (function(f){item.on('click',function(){$('.floor-tab').removeClass('active');$(this).addClass('active');navigateTo('floor',f.name);});})(floor);
        list.append(item);
    });
}
function updateFloorMeta(){
    Object.values(hospitalData.floors).forEach(function(floor){
        var el=$('.floor-tab[data-floor="'+floor.name+'"]');
        if(el.length){var wc=Object.keys(floor.wards).length;el.find('.ft-meta').html(wc+' Ward'+(wc!==1?'s':'')+' &bull; '+floor.stats.total+' Beds');}
    });
}
function navigateTo(level,fN,wN,rT,rNm){
    wN=wN||null;rT=rT||null;rNm=rNm||null;
    currentView={level:level,floor:fN,ward:wN,roomType:rT,room:rNm};patientPanelWard=null;renderBreadcrumbs();
    if(level==='floor')renderWards(fN);
    else if(level==='ward')renderRoomTypes(fN,wN);
    else if(level==='roomType')renderRooms(fN,wN,rT);
    else if(level==='room')renderBeds(fN,wN,rT,rNm);
}
function refreshCurrentView(){
    if(!currentView.floor){resetView();return;}
    if(currentView.level==='floor')renderWards(currentView.floor);
    else if(currentView.level==='ward')renderRoomTypes(currentView.floor,currentView.ward);
    else if(currentView.level==='roomType')renderRooms(currentView.floor,currentView.ward,currentView.roomType);
    else if(currentView.level==='room')renderBeds(currentView.floor,currentView.ward,currentView.roomType,currentView.room);
}
function renderBreadcrumbs(){
    var h='<div class="bc-left"><div class="bc-item" onclick="resetView()"><i class="fas fa-hospital" style="font-size:11px;"></i> Hospital</div>';
    if(currentView.floor){h+='<i class="fas fa-chevron-right bc-sep"></i>';h+='<div class="bc-item '+(currentView.level==='floor'?'active':'')+'" onclick="navigateTo(\'floor\',\''+currentView.floor+'\')">'+currentView.floor+'</div>';}
    if(currentView.ward){h+='<i class="fas fa-chevron-right bc-sep"></i>';h+='<div class="bc-item '+(currentView.level==='ward'?'active':'')+'" onclick="navigateTo(\'ward\',\''+currentView.floor+'\',\''+currentView.ward+'\')">'+currentView.ward+'</div>';}
    if(currentView.roomType){h+='<i class="fas fa-chevron-right bc-sep"></i>';h+='<div class="bc-item '+(currentView.level==='roomType'?'active':'')+'" onclick="navigateTo(\'roomType\',\''+currentView.floor+'\',\''+currentView.ward+'\',\''+currentView.roomType+'\')">'+currentView.roomType+'</div>';}
    if(currentView.room){h+='<i class="fas fa-chevron-right bc-sep"></i><div class="bc-item active">Room '+currentView.room+'</div>';}
    h+='</div><div class="legend-strip"><div class="legend-strip-title"><i class="fas fa-circle-info"></i> Occupancy Guide</div><div class="legend-item"><div class="og-dot" style="background:#16a34a;"></div> 0-25%</div><div class="legend-item"><div class="og-dot" style="background:#eab308;"></div> 26-50%</div><div class="legend-item"><div class="og-dot" style="background:#f97316;"></div> 51-75%</div><div class="legend-item"><div class="og-dot" style="background:#dc2626;"></div> 76-100%</div></div>';
    $('#breadcrumbBar').html(h);
}
function resetView(){
    currentView={level:'hospital',floor:null,ward:null,roomType:null,room:null};
    patientPanelWard=null;
    $('.floor-tab').removeClass('active');
    renderBreadcrumbs();
    var h='<div class="empty-state"><div class="empty-icon"><i class="fas fa-hospital"></i></div><h3>Select a Floor</h3><p>Click on any of the floor tabs above to view live ward details and manage bed allocation.</p></div>';
    $('#dynamicView').html(h);
}
function occColor(p){if(p<=25)return'#16a34a';if(p<=50)return'#eab308';if(p<=75)return'#f97316';return'#dc2626';}
function occChip(p){if(p===0)return'<span class="status-chip chip-green">Available</span>';if(p<=50)return'<span class="status-chip chip-yellow">Moderate</span>';if(p<100)return'<span class="status-chip chip-orange">High</span>';return'<span class="status-chip chip-red">Full</span>';}

function renderWards(floorName){
    var floor=hospitalData.floors[floorName];if(!floor)return;
    var h='<div class="sec-header"><h2><i class="fas fa-th-large"></i> Room Types &ndash; '+floorName+'</h2></div><div class="nb-grid">';
    Object.values(floor.wards).forEach(function(ward){
        Object.values(ward.roomTypes).forEach(function(rt){
            var p=rt.stats.total>0?Math.round((rt.stats.occ/rt.stats.total)*100):0;
            var rc=Object.keys(rt.rooms).length;var fc=occColor(p);
            var fn=floorName.replace(/'/g,"\\'");var wn=ward.name.replace(/'/g,"\\'");var rtn=rt.name.replace(/'/g,"\\'");
            h+='<div class="nb-card searchable-card" data-search="'+rt.name.toLowerCase()+'" onclick="openPatientPanel(\''+wn+'\',\''+rtn+'\',null,\''+fn+'\')">'
             +'<div class="nb-card-top"><div class="nb-card-icon"><i class="fas fa-bed"></i></div>'
             +'<div class="nb-card-name" title="'+rt.name+'">'+rt.name+'</div><div class="nb-card-sub">'+ward.name+' &bull; '+rc+' Room'+(rc!==1?'s':'')+'</div>'
             +'<div class="nb-card-stats"><div class="nb-stat"><div class="nb-stat-num occ">'+rt.stats.occ+'</div><div class="nb-stat-lbl">Occupied</div></div><div class="nb-stat"><div class="nb-stat-num avl">'+rt.stats.avail+'</div><div class="nb-stat-lbl">Available</div></div><div class="nb-stat"><div class="nb-stat-num tot">'+rt.stats.total+'</div><div class="nb-stat-lbl">Total</div></div></div>'
             +(rt.charges.total>0?'<div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border);font-size:10.5px;"><div style="display:flex;justify-content:space-between;align-items:center;"><span style="color:var(--text-lt);">Rate/Day:</span><strong style="color:var(--em);">&#8377;'+rt.charges.total+'</strong></div></div>':'')
             +'</div><div class="nb-card-foot"><span>'+p+'% Occupied</span><span>'+rt.stats.total+' Beds</span></div>'
             +'<div style="padding:0 14px 12px;"><div class="nb-prog"><div class="nb-prog-fill" style="width:'+p+'%;background:'+fc+';"></div></div></div></div>';
        });
    });
    h+='</div><div id="patientPanelContainer"></div>';
    $('#dynamicView').html(h);
}
function openPatientPanel(wardName,roomType,roomNum,floorName){
    var floor=hospitalData.floors[floorName];if(!floor||!floor.wards[wardName])return;
    var rt=floor.wards[wardName].roomTypes[roomType];if(!rt)return;
    
    var bedsToDisplay = [];
    var totalPts = 0;
    var titleStr = '';
    
    if (roomNum) {
        var room = rt.rooms[roomNum]; if(!room)return;
        bedsToDisplay = room.beds;
        totalPts = room.stats.occ;
        titleStr = 'Patient Details - ' + wardName + ' (' + roomNum + ')';
    } else {
        Object.values(rt.rooms).forEach(function(r){
            bedsToDisplay = bedsToDisplay.concat(r.beds);
        });
        totalPts = rt.stats.occ;
        titleStr = 'Beds - ' + roomType + ' (' + wardName + ')';
    }
    
    patientPanelWard={wardName:wardName,roomType:roomType,roomNum:roomNum,floorName:floorName};
    
    $('.ward-table tbody tr').removeClass('active-row');
    if (roomNum) {
        var rowId='row-'+wardName.replace(/\s+/g,'-')+'-'+roomType.replace(/\s+/g,'-')+'-'+roomNum;
        $('#'+rowId).addClass('active-row');
    }
    
    $('#patientPanelContainer').html('<div class="patient-panel-overlay" id="ppOverlay" onclick="if(event.target===this)closePatientPanel()"><div class="patient-panel" id="patientPanel">'
     +'<div class="pp-header"><div class="pp-header-left"><div class="pp-header-icon"><i class="fas fa-hospital-alt"></i></div>'
     +'<div class="pp-header-title" title="'+titleStr+'">'+titleStr+'</div>'
     +'<span class="pp-header-badge">Patients: '+totalPts+'</span></div>'
     +'<button class="pp-hdr-close-btn" onclick="closePatientPanel()" title="Close"><i class="fas fa-times"></i></button></div>'
     +'<div class="pp-toolbar"><div class="pp-search"><i class="fas fa-search"></i><input type="text" placeholder="Search Patient or Bed..." id="ppSearchInput" onkeyup="filterBedCards()"/></div>'
     +'<div class="pp-toolbar-actions">'
     +'<button class="pp-view-btn '+(viewMode==='grid'?'active':'')+'" id="btnGridView" onclick="switchView(\'grid\')"><i class="fas fa-th-large"></i> Grid View</button>'
     +'<button class="pp-view-btn '+(viewMode==='list'?'active':'')+'" id="btnListView" onclick="switchView(\'list\')"><i class="fas fa-list-ul"></i> List View</button>'
     +'<button class="pp-export-btn" onclick="IPD.toast(\'Export coming soon!\',\'info\')"><i class="fas fa-download"></i> Export</button>'
     +'</div></div>'
     +'<div class="pp-body" id="ppBody">'+renderBedCardsHTML(bedsToDisplay)+'</div></div></div>');
}
function closePatientPanel(){
    $('#patientPanel').css({opacity:0,transform:'translateY(20px) scale(0.95)',transition:'all .22s'});
    $('#ppOverlay').css({opacity:0,transition:'all .22s'});
    setTimeout(function(){$('#patientPanelContainer').empty();},230);
    $('.ward-table tbody tr').removeClass('active-row');patientPanelWard=null;
}
function switchView(mode){
    viewMode=mode;
    if(mode==='grid'){$('#btnGridView').addClass('active');$('#btnListView').removeClass('active');}
    else{$('#btnListView').addClass('active');$('#btnGridView').removeClass('active');}
    if(!patientPanelWard)return;
    var d=patientPanelWard;
    var bedsToDisplay = [];
    if (d.roomNum) {
        var room=hospitalData.floors[d.floorName].wards[d.wardName].roomTypes[d.roomType].rooms[d.roomNum];
        if(room) bedsToDisplay = room.beds;
    } else {
        var rt=hospitalData.floors[d.floorName].wards[d.wardName].roomTypes[d.roomType];
        if(rt) {
            Object.values(rt.rooms).forEach(function(r){
                bedsToDisplay = bedsToDisplay.concat(r.beds);
            });
        }
    }
    $('#ppBody').html(renderBedCardsHTML(bedsToDisplay));
}
function bedSVG(){return '<svg class="bed-illus-svg" viewBox="0 0 130 80" xmlns="http://www.w3.org/2000/svg"><rect x="10" y="38" width="110" height="22" rx="5" fill="#e0f0e8" stroke="#16a34a" stroke-width="1.5"/><rect x="14" y="32" width="96" height="12" rx="4" fill="#c6e8d5" stroke="#16a34a" stroke-width="1"/><rect x="18" y="33" width="22" height="10" rx="3" fill="#fff" stroke="#b0d8c0" stroke-width="1"/><rect x="14" y="60" width="8" height="10" rx="2" fill="#a8d5bd"/><rect x="108" y="60" width="8" height="10" rx="2" fill="#a8d5bd"/><rect x="10" y="28" width="12" height="32" rx="4" fill="#a8d5bd" stroke="#16a34a" stroke-width="1"/><rect x="100" y="18" width="3" height="24" fill="#9ca3af"/><rect x="94" y="14" width="16" height="10" rx="2" fill="#1a2b26" stroke="#374151" stroke-width=".5"/><rect x="96" y="16" width="12" height="6" rx="1" fill="#10b981" opacity=".6"/><polyline points="97,19 99,19 100,17 101,21 102,17 103,19 105,19" fill="none" stroke="#22c55e" stroke-width=".8"/><rect x="24" y="10" width="2" height="28" fill="#d1d5db"/><circle cx="25" cy="11" r="3" fill="#bfdbfe" stroke="#93c5fd" stroke-width=".8"/></svg>';}
function renderBedCardsHTML(beds){
    if(viewMode==='list')return renderBedListHTML(beds);
    var h='<div class="bed-grid" id="bedCardGrid">';
    beds.forEach(function(bed){
        var st=bed.normalized_status.toLowerCase();var cls='st-'+st;var body='',action='';
        if(st==='occupied'){
            body='<div class="bed-patient-info"><div class="bed-patient-name" title="'+(bed.patient_name||'Unknown')+'"><i class="fas fa-user-circle"></i>'+(bed.patient_name||'Unknown Patient')+'</div><div style="color:var(--text-lt);font-size:10px;line-height:1.3;"><b>PID:</b> '+(bed.patient_id||'-')+' &bull; <b>Adm:</b> '+IPD.formatDate(bed.admission_date)+'</div></div>';
            action='<button class="btn-release" onclick="event.stopPropagation();handleAction(\''+bed.bed_id+'\',\'release\')"><i class="fas fa-sign-out-alt"></i> Release Bed</button>';
        } else if(st==='available'){
            body=bedSVG()+'<div class="bed-ready-txt">Ready for admission</div>';
            action='<button class="btn-admit" onclick="event.stopPropagation();handleAction(\''+bed.bed_id+'\',\'admit\')"><i class="fas fa-plus-circle"></i> Admit Patient</button>';
        } else {
            var icons={maintenance:'fa-tools',blocked:'fa-ban',reserved:'fa-bookmark'};
            body='<div class="bed-status-center"><i class="fas '+(icons[st]||'fa-ban')+'"></i><span>'+bed.normalized_status+'</span></div>';
            action='<button class="btn-change-status" onclick="event.stopPropagation();handleAction(\''+bed.bed_id+'\',\'manage\')"><i class="fas fa-sliders-h"></i> Change Status</button>';
        }
        h+='<div class="bed-card '+cls+' searchable-bed-card" data-search="'+bed.bed_number.toLowerCase()+' '+(bed.patient_name?bed.patient_name.toLowerCase():'')+'"><div class="bed-card-stripe"></div><div class="bed-card-top"><div class="bed-card-num"><i class="fas fa-bed"></i> '+bed.bed_number+'</div><span class="bed-badge">'+bed.normalized_status+'</span></div><div class="bed-card-illus">'+body+'</div><div class="bed-card-action">'+action+'</div></div>';
    });
    return h+'</div>';
}
function renderBedListHTML(beds){
    var h='<div class="bed-list" id="bedCardGrid">';
    beds.forEach(function(bed){
        var st=bed.normalized_status.toLowerCase();
        var dot=st==='available'?'#16a34a':st==='occupied'?'var(--red)':st==='maintenance'?'var(--amber)':'#9ca3af';
        var act='';
        if(st==='occupied')act='<button class="btn-release" style="width:auto;padding:6px 14px;" onclick="handleAction(\''+bed.bed_id+'\',\'release\')"><i class="fas fa-sign-out-alt"></i> Release</button>';
        else if(st==='available')act='<button class="btn-admit" style="width:auto;padding:6px 14px;" onclick="handleAction(\''+bed.bed_id+'\',\'admit\')"><i class="fas fa-plus-circle"></i> Admit</button>';
        else act='<button class="btn-change-status" style="width:auto;padding:6px 14px;" onclick="handleAction(\''+bed.bed_id+'\',\'manage\')"><i class="fas fa-sliders-h"></i> Change</button>';
        h+='<div class="bed-list-row searchable-bed-card" data-search="'+bed.bed_number.toLowerCase()+' '+(bed.patient_name?bed.patient_name.toLowerCase():'')+'">'
          +'<div class="blr-left">'
          +'<div class="blr-dot" style="background:'+dot+';"></div>'
          +'<div class="blr-num"><i class="fas fa-bed" style="color:var(--em);font-size:11px;"></i>'+bed.bed_number+'</div>'
          +'<span class="bed-badge '+(st==='available'?'st-available':st==='occupied'?'st-occupied':'st-blocked')+'">'+bed.normalized_status+'</span>'
          +'<div class="blr-patient">'+(st==='occupied'?'<strong>'+(bed.patient_name||'Unknown')+'</strong> (PID: '+(bed.patient_id||'-')+')':'<span style="color:var(--text-lt);">Ready for admission</span>')+'</div>'
          +'</div>'
          +'<div class="blr-actions">'+act+'</div></div>';
    });
    return h+'</div>';
}
function filterBedCards(){var q=($('#ppSearchInput').val()||'').toLowerCase().trim();$('.searchable-bed-card').each(function(){$(this).toggle(!q||($(this).data('search')||'').indexOf(q)>=0);});}
function toggleFloorCard(){var b=$('#floorCardBody'),btn=$('#collapseBtn');if(b.is(':visible')){b.slideUp(220);btn.addClass('collapsed');}else{b.slideDown(220);btn.removeClass('collapsed');}}
function renderRoomTypes(floorName,wardName){
    var ward=hospitalData.floors[floorName]&&hospitalData.floors[floorName].wards[wardName];if(!ward)return;
    var h='<div class="sec-header"><h2><i class="fas fa-th-large"></i> Room Types &ndash; '+wardName+'</h2></div><div class="nb-grid">';
    Object.values(ward.roomTypes).forEach(function(rt){
        var p=rt.stats.total>0?Math.round((rt.stats.occ/rt.stats.total)*100):0;var rc=Object.keys(rt.rooms).length;var fc=occColor(p);
        var fn=floorName.replace(/'/g,"\\'");var wn=wardName.replace(/'/g,"\\'");var rtn=rt.name.replace(/'/g,"\\'");
        h+='<div class="nb-card searchable-card" data-search="'+rt.name.toLowerCase()+'" onclick="navigateTo(\'roomType\',\''+fn+'\',\''+wn+'\',\''+rtn+'\')">'
         +'<div class="nb-card-top"><div class="nb-card-icon"><i class="fas fa-bed"></i></div>'
         +'<div class="nb-card-name" title="'+rt.name+'">'+rt.name+'</div><div class="nb-card-sub">'+rc+' Room'+(rc!==1?'s':'')+'</div>'
         +'<div class="nb-card-stats"><div class="nb-stat"><div class="nb-stat-num occ">'+rt.stats.occ+'</div><div class="nb-stat-lbl">Occupied</div></div><div class="nb-stat"><div class="nb-stat-num avl">'+rt.stats.avail+'</div><div class="nb-stat-lbl">Available</div></div><div class="nb-stat"><div class="nb-stat-num tot">'+rt.stats.total+'</div><div class="nb-stat-lbl">Total</div></div></div>'
         +(rt.charges.total>0?'<div style="margin-top:10px;padding-top:8px;border-top:1px dashed var(--border);font-size:10.5px;"><div style="display:flex;justify-content:space-between;align-items:center;"><span style="color:var(--text-lt);">Rate/Day:</span><strong style="color:var(--em);">&#8377;'+rt.charges.total+'</strong></div></div>':'')
         +'</div><div class="nb-card-foot"><span>'+p+'% Occupied</span><span>'+rt.stats.total+' Beds</span></div>'
         +'<div style="padding:0 14px 12px;"><div class="nb-prog"><div class="nb-prog-fill" style="width:'+p+'%;background:'+fc+';"></div></div></div></div>';
    });
    $('#dynamicView').html(h+'</div>');
}
function renderRooms(floorName,wardName,roomTypeName){
    var rt=hospitalData.floors[floorName]&&hospitalData.floors[floorName].wards[wardName]&&hospitalData.floors[floorName].wards[wardName].roomTypes[roomTypeName];if(!rt)return;
    
    var allBeds = [];
    Object.values(rt.rooms).forEach(function(room){
        allBeds = allBeds.concat(room.beds);
    });
    
    var fn=floorName.replace(/'/g,"\\'");var wn=wardName.replace(/'/g,"\\'");var rtn=roomTypeName.replace(/'/g,"\\'");
    var h='<div class="sec-header"><h2><i class="fas fa-bed"></i> Beds &ndash; '+roomTypeName+' ('+wardName+')</h2>'
     +'<div class="sec-header-actions">'
     +'<button class="pp-view-btn '+(viewMode==='grid'?'active':'')+'" id="btnGR" onclick="viewMode=\'grid\';$(\'#btnGR\').addClass(\'active\');$(\'#btnLR\').removeClass(\'active\');renderRooms(\''+fn+'\',\''+wn+'\',\''+rtn+'\')"><i class="fas fa-th-large"></i> Grid</button>'
     +'<button class="pp-view-btn '+(viewMode==='list'?'active':'')+'" id="btnLR" onclick="viewMode=\'list\';$(\'#btnLR\').addClass(\'active\');$(\'#btnGR\').removeClass(\'active\');renderRooms(\''+fn+'\',\''+wn+'\',\''+rtn+'\')"><i class="fas fa-list-ul"></i> List</button>'
     +'</div></div>';
    $('#dynamicView').html(h+renderBedCardsHTML(allBeds));
}
function renderBeds(floorName,wardName,roomTypeName,roomNum){
    var r=hospitalData.floors[floorName]&&hospitalData.floors[floorName].wards[wardName]&&hospitalData.floors[floorName].wards[wardName].roomTypes[roomTypeName]&&hospitalData.floors[floorName].wards[wardName].roomTypes[roomTypeName].rooms[roomNum];if(!r)return;
    var fn=floorName.replace(/'/g,"\\'");var wn=wardName.replace(/'/g,"\\'");var rtn=roomTypeName.replace(/'/g,"\\'");
    var h='<div class="sec-header"><h2><i class="fas fa-bed"></i> Beds in Room '+roomNum+'</h2>'
     +'<div class="sec-header-actions">'
     +'<button class="pp-view-btn '+(viewMode==='grid'?'active':'')+'" id="btnGR" onclick="viewMode=\'grid\';$(\'#btnGR\').addClass(\'active\');$(\'#btnLR\').removeClass(\'active\');renderBeds(\''+fn+'\',\''+wn+'\',\''+rtn+'\',\''+roomNum+'\')"><i class="fas fa-th-large"></i> Grid</button>'
     +'<button class="pp-view-btn '+(viewMode==='list'?'active':'')+'" id="btnLR" onclick="viewMode=\'list\';$(\'#btnLR\').addClass(\'active\');$(\'#btnGR\').removeClass(\'active\');renderBeds(\''+fn+'\',\''+wn+'\',\''+rtn+'\',\''+roomNum+'\')"><i class="fas fa-list-ul"></i> List</button>'
     +'</div></div>';
    $('#dynamicView').html(h+renderBedCardsHTML(r.beds));
}
var Modal={
    overlay:null,init:function(){this.overlay=document.getElementById('hmsOverlay');},
    confirm:function(o){
        var ic=o.type==='danger'?'danger':'info';var is=o.type==='danger'?'fa-sign-out-alt':'fa-check-circle';var bc=o.type==='danger'?'red':'green';
        document.getElementById('hmsModalContent').innerHTML='<div class="hms-modal-icon '+ic+'"><i class="fas '+is+'"></i></div><div class="hms-modal-title">'+o.title+'</div><div class="hms-modal-body">'+o.body+'</div><div class="hms-modal-btns"><button class="hms-btn cancel" id="hmsCnl">'+(o.cancelText||'Cancel')+'</button><button class="hms-btn '+bc+'" id="hmsConf">'+(o.confirmText||'Confirm')+'</button></div>';
        this.overlay.classList.add('open');var self=this;
        document.getElementById('hmsCnl').onclick=function(){self.close();};
        document.getElementById('hmsConf').onclick=function(){self.close();o.onConfirm();};
    },
    prompt:function(o){
        var opts=o.options.map(function(x){return'<option value="'+x+'"'+(x===o.defaultVal?' selected':'')+'>'+x+'</option>';}).join('');
        document.getElementById('hmsModalContent').innerHTML='<div class="hms-modal-icon info"><i class="fas fa-sliders-h"></i></div><div class="hms-modal-title">'+o.title+'</div><div class="hms-modal-body">'+o.body+'</div><div class="hms-select-row"><label>Select New Status</label><select class="hms-select" id="hmsSel">'+opts+'</select></div><div class="hms-modal-btns"><button class="hms-btn cancel" id="hmsCnl">'+(o.cancelText||'Cancel')+'</button><button class="hms-btn green" id="hmsConf">'+(o.confirmText||'Update')+'</button></div>';
        this.overlay.classList.add('open');var self=this;
        document.getElementById('hmsCnl').onclick=function(){self.close();};
        document.getElementById('hmsConf').onclick=function(){var v=document.getElementById('hmsSel').value;self.close();o.onConfirm(v);};
    },
    close:function(){this.overlay.classList.remove('open');}
};
document.addEventListener('DOMContentLoaded',function(){
    Modal.init();
    document.getElementById('hmsOverlay').addEventListener('click',function(e){if(e.target===this)Modal.close();});
});
function handleAction(bedId,action){
    if(action==='release'){
        Modal.confirm({title:'Release Bed',body:'Are you sure you want to release this bed?<br>The patient will be marked as discharged.',confirmText:'<i class="fas fa-sign-out-alt"></i> Release',cancelText:'Cancel',type:'danger',onConfirm:function(){
            IPD.ajax('beds?action=release','POST',{bed_id:bedId}).then(function(){IPD.toast('Bed released successfully','success');loadBedData(true);}).catch(function(err){IPD.toast(err.message,'error');});
        }});
    } else if(action==='manage'){
        Modal.prompt({title:'Change Bed Status',body:'Select the new status for this bed.',options:['Available','Blocked','Maintenance'],defaultVal:'Available',confirmText:'<i class="fas fa-check"></i> Update Status',onConfirm:function(v){
            IPD.ajax('beds?id='+bedId,'PUT',{status:v}).then(function(){IPD.toast('Bed status updated to '+v,'success');loadBedData(true);}).catch(function(err){IPD.toast(err.message,'error');});
        }});
    } else if(action==='admit'){
        Modal.confirm({title:'Admit Patient',body:'Redirect to IPD admission form for this bed?',confirmText:'<i class="fas fa-plus-circle"></i> Go to Admission',cancelText:'Cancel',type:'info',onConfirm:function(){window.location.href='/GM_HMS/reception_view/ipd_management/public/index.php';}});
    }
}
</script>
        </div><!-- /reception-content -->
</body>
</html>