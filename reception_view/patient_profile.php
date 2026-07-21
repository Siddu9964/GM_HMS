<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: /GM_HMS/login.php");
    exit();
}
$patientId = $_GET['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile — GM HMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">

    <style>
        /* ═══════════════════════════════════════════════════
           GP = Global Patient — unique prefix, no conflicts
        ═══════════════════════════════════════════════════ */

        /* ── BASE ─────────────────────────────────────────── */
        .reception-content {
            background: #f3efe6 !important;
            padding: 0 !important;
            min-height: 100vh;
        }
        .gp-wrap {
            padding: 28px 36px 48px;
            background: #f3efe6;
            min-height: 100vh;
        }

        /* ── PAGE HEADER ──────────────────────────────────── */
        .gp-hdr {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 30px;
        }
        .gp-hdr-left { display: flex; align-items: center; gap: 14px; }
        .gp-back {
            width: 44px; height: 44px; border-radius: 14px;
            background: #fff; border: 1.5px solid #e5ddd0;
            color: #1f6b4a; display: flex; align-items: center;
            justify-content: center; text-decoration: none; font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(31,107,74,0.1); transition: all 0.2s;
        }
        .gp-back:hover { background: #f0fdf4; border-color: #1f6b4a; transform: translateX(-2px); }
        .gp-breadcrumb { display: flex; align-items: center; gap: 8px; }
        .gp-bc-item {
            font-size: 0.82rem; color: #9a8f82; font-weight: 500;
            cursor: pointer; transition: color 0.2s;
        }
        .gp-bc-item:hover { color: #1f6b4a; }
        .gp-bc-sep { color: #c5bdb2; font-size: 0.75rem; }
        .gp-bc-active { color: #1f6b4a; font-weight: 700; }
        .gp-page-label {
            font-size: 1.6rem; font-weight: 900; color: #1a1210;
            letter-spacing: -0.5px; line-height: 1;
        }
        .gp-page-sub { font-size: 0.82rem; color: #9a8f82; margin-top: 3px; font-weight: 500; }

        #bookApptBtn {
            background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%) !important;
            color: #fff !important; border: none !important; border-radius: 14px;
            padding: 13px 28px; font-weight: 700; font-size: 0.9rem;
            font-family: 'Inter', sans-serif; cursor: pointer;
            display: inline-flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 32px rgba(31,107,74,0.35), 0 2px 8px rgba(31,107,74,0.15);
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            position: relative; overflow: hidden;
        }
        #bookApptBtn::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0; transition: opacity 0.3s;
        }
        #bookApptBtn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(31,107,74,0.45), 0 4px 12px rgba(31,107,74,0.2);
        }
        #bookApptBtn:hover:not(:disabled)::before { opacity: 1; }
        #bookApptBtn:disabled {
            background: #d8d0c4 !important; color: #a09488 !important;
            box-shadow: none; cursor: not-allowed; transform: none;
        }

        /* ── HERO PROFILE CARD ────────────────────────────── */
        .gp-hero {
            background: #143e2f; /* Solid dark green from image */
            border-radius: 28px;
            border: 2px solid #e8e0cc;
            box-shadow: 0 10px 40px rgba(20,62,47,0.3);
            margin-bottom: 24px;
            overflow: hidden;
            position: relative;
        }

        .gp-hero-inner {
            display: flex; align-items: stretch; gap: 0;
        }

        /* LEFT — dark green panel */
        .gp-hero-left {
            width: 320px; min-width: 320px;
            background: transparent;
            padding: 24px 32px;
            display: flex; flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.15);
            position: relative; overflow: hidden;
        }

        /* Avatar */
        .gp-avatar-wrap {
            position: relative; width: 76px; height: 76px;
            flex-shrink: 0;
        }
        .gp-avatar {
            width: 76px; height: 76px; border-radius: 50%;
            background: #143e2f;
            border: 2px solid #e8e0cc;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 800; color: #fff;
        }

        .gp-pname {
            font-size: 1.5rem; font-weight: 800; color: #ffffff !important;
            letter-spacing: -0.3px; line-height: 1.2; margin: 0 0 4px 0;
        }
        .gp-pid {
            color: #bfae96; font-size: 0.9rem;
            font-weight: 600; font-family: monospace; letter-spacing: 0.5px;
        }

        .gp-status-chip {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);
            color: #d8ceb8; font-size: 0.8rem; font-weight: 700;
            padding: 8px 16px; border-radius: 20px; margin-bottom: 12px; margin-top: 24px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .gp-status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #e8e0cc;
        }

        /* Stats row */
        .gp-stats {
            display: flex; gap: 1px; width: 100%;
            background: rgba(255,255,255,0.08);
            border-radius: 16px; overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            position: relative; z-index: 1;
        }
        .gp-stat {
            flex: 1; padding: 14px 8px; text-align: center;
            background: rgba(255,255,255,0.05);
            border-right: 1px solid rgba(255,255,255,0.08);
            transition: background 0.2s;
        }
        .gp-stat:last-child { border-right: none; }
        .gp-stat:hover { background: rgba(255,255,255,0.1); }
        .gp-stat-num {
            font-size: 1.6rem; font-weight: 900; color: #fff;
            letter-spacing: -0.5px; line-height: 1;
            display: flex; align-items: center; justify-content: center;
        }
        .gp-stat-lbl { font-size: 0.62rem; color: rgba(255,255,255,0.55); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

        /* RIGHT — info grid */
        .gp-hero-right {
            flex: 1; padding: 24px 32px;
            background: transparent;
            display: flex; flex-direction: column; justify-content: center;
        }

        /* ── MINI STATS STRIP (inside right panel) ──────── */
        .gp-mini-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .gp-mini-stat {
            background: rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 8px;
            text-align: center;
        }
        .gp-mini-stat-ico {
            color: #d8ceb8; font-size: 1rem; margin-bottom: 4px;
        }
        .gp-mini-stat-val {
            font-size: 1rem; font-weight: 800; color: #fff; line-height: 1.2;
        }
        .gp-mini-stat-lbl {
            font-size: 0.6rem; color: #bfae96; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;
        }

        /* ── INFO TILES GRID ──────────────────────────────── */
        .gp-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .gp-info-tile {
            background: rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px; padding: 8px 12px;
            display: flex; align-items: center; gap: 10px;
        }
        .gp-info-tile.gp-full { grid-column: 1 / -1; }

        .gp-tile-ico {
            color: #d8ceb8; font-size: 1.1rem; flex-shrink: 0;
            width: 24px; text-align: center;
        }
        .gp-tile-lbl {
            font-size: 0.6rem; color: #bfae96; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;
        }
        .gp-tile-val {
            font-size: 0.8rem; color: #fff; font-weight: 600; line-height: 1.2;
        }

        /* ── KPI CARDS ─────────────────────────────────────── */
        .gp-kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .gp-kpi {
            background: #fff;
            border: 1.5px solid #e5ddd0;
            border-radius: 22px; padding: 22px 24px;
            display: flex; align-items: center; gap: 18px;
            box-shadow: 0 4px 16px rgba(31,107,74,0.05);
            transition: all 0.25s ease;
            position: relative; overflow: hidden; cursor: default;
        }
        .gp-kpi::after {
            content: ''; position: absolute;
            bottom: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #1f6b4a, #3aaa78);
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.3s ease;
        }
        .gp-kpi:hover { transform: translateY(-3px); box-shadow: 0 12px 36px rgba(31,107,74,0.12); }
        .gp-kpi:hover::after { transform: scaleX(1); }
        .gp-kpi-ico {
            width: 54px; height: 54px; border-radius: 16px; flex-shrink: 0;
            background: rgba(31,107,74,0.08); color: #1f6b4a;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .gp-kpi-lbl { font-size: 0.7rem; color: #9a8f82; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px; }
        .gp-kpi-val { font-size: 1.7rem; font-weight: 900; color: #1a1210; letter-spacing: -0.5px; line-height: 1; }
        .gp-kpi-tag { font-size: 0.72rem; color: #1f6b4a; font-weight: 700; margin-top: 4px; }
        .gp-kpi-progress { width: 100%; height: 6px; background: #e5ddd0; border-radius: 3px; margin-top: 8px; overflow: hidden; }
        .gp-kpi-bar { height: 100%; background: #1f6b4a; border-radius: 3px; }

        /* ── TABS ──────────────────────────────────────────── */
        .gp-tabs-card {
            background: #fff;
            border: 1.5px solid #e5ddd0;
            border-radius: 26px;
            box-shadow: 0 4px 20px rgba(31,107,74,0.06);
            overflow: hidden;
        }
        .gp-tabs-nav {
            display: flex; align-items: center; gap: 6px;
            padding: 20px 28px 0;
            border-bottom: 2px solid #f0ebe3;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
        }
        .gp-tabs-nav::-webkit-scrollbar { display: none; } /* Chrome */
        .gp-tab {
            padding: 12px 20px 16px; font-weight: 700; font-size: 0.88rem;
            color: #9a8f82; cursor: pointer;
            display: flex; align-items: center; gap: 9px;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
            transition: all 0.25s ease; border-radius: 0;
            white-space: nowrap;
        }
        .gp-tab:hover { color: #4a7a66; }
        .gp-tab.active { color: #1f6b4a; border-bottom-color: #1f6b4a; }
        .gp-cnt {
            padding: 2px 9px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;
            background: #f0ebe3; color: #9a8f82; transition: all 0.25s;
        }
        .gp-tab.active .gp-cnt { background: rgba(31,107,74,0.12); color: #1f6b4a; }

        .gp-panel { 
            display: none; 
            overflow-y: auto;
            max-height: 500px; /* Enable scrolling */
        }
        .gp-panel::-webkit-scrollbar { width: 8px; }
        .gp-panel::-webkit-scrollbar-track { background: transparent; }
        .gp-panel::-webkit-scrollbar-thumb { background: rgba(31,107,74,0.2); border-radius: 4px; }
        .gp-panel::-webkit-scrollbar-thumb:hover { background: rgba(31,107,74,0.4); }

        .gp-panel.active { display: block; animation: gpFade 0.35s ease; }
        @keyframes gpFade { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* ── OVERVIEW LAYOUT ──────────────────────────────── */
        .gp-layout-2col { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; padding: 24px; }
        @media(max-width:1100px) { .gp-layout-2col { grid-template-columns: 1fr; } }
        
        /* ── MEDICAL SUMMARY ───────────────────────────────── */
        .gp-med-summary {
            background: #fff; border: 1.5px solid #e5ddd0; border-radius: 24px;
            padding: 24px; box-shadow: 0 10px 40px rgba(31,107,74,0.04);
            margin-bottom: 24px;
        }
        .gp-ms-title { font-size: 1.1rem; font-weight: 900; color: #1f6b4a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .gp-ms-item {
            display: flex; align-items: flex-start; gap: 16px;
            padding: 14px 0; border-bottom: 1px dashed #ede8dd;
            transition: all 0.2s;
        }
        .gp-ms-item:last-child { border-bottom: none; padding-bottom: 0; }
        .gp-ms-item:hover { transform: translateX(4px); }
        .gp-ms-ico {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            background: #f5f9f6; color: #1f6b4a; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; border: 1px solid rgba(31,107,74,0.1);
        }
        .gp-ms-lbl { font-size: 0.7rem; color: #9a8f82; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .gp-ms-val { font-size: 0.95rem; font-weight: 700; color: #1a1210; line-height: 1.4; }
        .gp-ms-tag { display: inline-block; background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }

        /* ── QUICK ACTIONS (Redesigned) ────────────────────── */
        .gp-qa-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .gp-qa-btn2 {
            display: flex; align-items: center; gap: 12px; padding: 16px;
            background: #faf8f4; border: 1.5px solid #ede8dd; border-radius: 16px;
            cursor: pointer; transition: all 0.25s ease; text-decoration: none;
        }
        .gp-qa-btn2 i { font-size: 1.2rem; color: #1f6b4a; transition: transform 0.2s; }
        .gp-qa-btn2 div { text-align: left; }
        .gp-qa-title { font-size: 0.9rem; font-weight: 800; color: #1a1210; margin-bottom: 2px; }
        .gp-qa-sub { font-size: 0.7rem; color: #9a8f82; font-weight: 600; }
        .gp-qa-btn2:hover { background: #f0fdf4; border-color: rgba(31,107,74,0.3); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(31,107,74,0.08); }
        .gp-qa-btn2:hover i { transform: scale(1.15); }
        .gp-qa-btn2.primary { background: linear-gradient(135deg, #1f6b4a, #144d34); border: none; }
        .gp-qa-btn2.primary .gp-qa-title { color: #fff; }
        .gp-qa-btn2.primary .gp-qa-sub { color: rgba(255,255,255,0.7); }
        .gp-qa-btn2.primary i { color: #b7f5d4; }

        /* ── TIMELINE ──────────────────────────────────────── */
        .gp-timeline-wrap { padding: 24px; position: relative; }
        .gp-timeline-wrap::before {
            content: ''; position: absolute; top: 30px; bottom: 30px; left: 45px;
            width: 2px; background: #ede8dd; z-index: 1;
        }
        .gp-tl-item { display: flex; gap: 24px; margin-bottom: 30px; position: relative; z-index: 2; }
        .gp-tl-icon {
            width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
            background: #fff; border: 2px solid #1f6b4a; color: #1f6b4a;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
            box-shadow: 0 0 0 4px #f3efe6;
        }
        .gp-tl-content {
            background: #fff; border: 1.5px solid #ede8dd; border-radius: 16px;
            padding: 20px; flex: 1; box-shadow: 0 4px 16px rgba(31,107,74,0.04);
            transition: all 0.2s;
        }
        .gp-tl-content:hover { transform: translateY(-2px); border-color: rgba(31,107,74,0.3); box-shadow: 0 8px 24px rgba(31,107,74,0.08); }
        .gp-tl-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
        .gp-tl-title { font-size: 1.05rem; font-weight: 800; color: #1a1210; }
        .gp-tl-time { font-size: 0.75rem; color: #9a8f82; font-weight: 700; background: #faf8f4; padding: 4px 10px; border-radius: 12px; }
        .gp-tl-desc { font-size: 0.9rem; color: #5a5047; line-height: 1.5; margin-bottom: 12px; }
        .gp-tl-meta { display: flex; gap: 16px; font-size: 0.8rem; font-weight: 600; color: #9a8f82; }
        .gp-tl-meta i { color: #1f6b4a; margin-right: 4px; }

        /* ── TABLE ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 14px 28px;
            font-size: 0.68rem; font-weight: 800; color: #9a8f82;
            text-transform: uppercase; letter-spacing: 0.9px;
            background: #faf8f4; border-bottom: 1.5px solid #ede8dd;
        }
        td {
            padding: 18px 28px; border-bottom: 1px solid #f5f1eb;
            font-size: 0.88rem; color: #5a5047; font-weight: 500;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover td { background: #faf8f4; }

        .gp-td-d1 { font-weight: 700; color: #1a1210; font-size: 0.9rem; }
        .gp-td-d2 { font-size: 0.75rem; color: #9a8f82; margin-top: 3px; }
        .gp-doc-chip {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #1f6b4a, #2d9266);
            color: #fff; font-size: 0.82rem; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: 10px;
        }
        .gp-doc-row { display: flex; align-items: center; }
        .gp-doc-name { font-weight: 700; color: #1a1210; }
        .gp-bid {
            font-weight: 800; color: #1f6b4a; font-family: monospace;
            font-size: 0.82rem; letter-spacing: 0.5px;
            background: rgba(31,107,74,0.07); padding: 3px 10px;
            border-radius: 8px; border: 1px solid rgba(31,107,74,0.1);
        }
        .gp-amt { font-weight: 900; color: #1f6b4a; font-size: 1.05rem; }

        /* ── STATUS PILLS ──────────────────────────────────── */
        .gp-pill {
            padding: 5px 13px; border-radius: 20px; font-size: 0.74rem;
            font-weight: 700; display: inline-flex; align-items: center;
            gap: 6px; letter-spacing: 0.2px;
        }
        .gp-pill::before { content:''; width:6px; height:6px; border-radius:50%; }
        .gp-sched   { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
        .gp-sched::before   { background:#4caf50; }
        .gp-done    { background:#e8f5e9; color:#1f6b4a; border:1px solid #a5d6a7; }
        .gp-done::before    { background:#1f6b4a; }
        .gp-cancel  { background:#fef3e2; color:#e65100; border:1px solid #ffcc80; }
        .gp-cancel::before  { background:#ff9800; }
        .gp-paid    { background:#e8f5e9; color:#1f6b4a; border:1px solid #a5d6a7; }
        .gp-paid::before    { background:#1f6b4a; }
        .gp-pending { background:#fff8e1; color:#f57f17; border:1px solid #ffe082; }
        .gp-pending::before { background:#ffc107; }

        /* ── BADGE ─────────────────────────────────────────── */
        .gp-badge {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 15px; border-radius: 20px; font-size: 0.74rem;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .gp-badge-old { background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.2); }
        .gp-badge-new { background: rgba(74,222,128,0.2); color: #b7f5d4; border: 1px solid rgba(74,222,128,0.3); }

        /* ── EMPTY STATE ───────────────────────────────────── */
        .gp-empty {
            padding: 60px 28px; text-align: center;
        }
        .gp-empty-ico {
            width: 64px; height: 64px; border-radius: 20px;
            background: rgba(31,107,74,0.07); color: #1f6b4a;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; margin: 0 auto 16px;
        }
        .gp-empty p { color: #9a8f82; font-weight: 600; font-size: 0.95rem; margin:0; }

        /* ── ANIMATIONS ────────────────────────────────────── */
        .gp-hero, .gp-kpi, .gp-tabs-card {
            animation: gpLoad 0.5s ease both;
        }
        .gp-kpi:nth-child(1) { animation-delay: 0.05s; }
        .gp-kpi:nth-child(2) { animation-delay: 0.1s; }
        .gp-kpi:nth-child(3) { animation-delay: 0.15s; }
        .gp-kpi:nth-child(4) { animation-delay: 0.2s; }
        @keyframes gpLoad { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
    </style>
</head>
<body>
<div class="reception-layout">
    <?php include 'includes/reception_sidebar.php'; ?>
    <div class="reception-main-content">
        <?php $pageTitle = 'Patient Profile'; include 'includes/reception_navbar.php'; ?>
        <main class="reception-content">
        <div class="gp-wrap">

            <!-- ── PAGE HEADER ── -->
            <div class="gp-hdr">
                <div class="gp-hdr-left">
                    <a href="javascript:history.back()" class="gp-back">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <div class="gp-breadcrumb">
                            <span class="gp-bc-item" onclick="history.back()">Dashboard</span>
                            <span class="gp-bc-sep"><i class="fas fa-chevron-right"></i></span>
                            <span class="gp-bc-active">Patient Profile</span>
                        </div>
                        <div class="gp-page-label">Patient Profile</div>
                        <div class="gp-page-sub">Medical records &amp; complete history</div>
                    </div>
                </div>
                <button onclick="bookAppointment()" id="bookApptBtn" disabled>
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </button>
            </div>

            <!-- ── HERO CARD — populated by JS ── -->
            <div id="profileCard">
                <div class="gp-hero">
                    <div class="gp-hero-bar"></div>
                    <div class="gp-hero-inner">
                        <div class="gp-hero-left" style="align-items:center;justify-content:center;">
                            <div style="text-align:center; z-index:1; position:relative;">
                                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:rgba(255,255,255,0.4);"></i>
                                <p style="color:rgba(255,255,255,0.4); margin-top:12px; font-weight:600; font-size:0.9rem;">Loading…</p>
                            </div>
                        </div>
                        <div class="gp-hero-right" style="display:flex;align-items:center;justify-content:center;">
                            <p style="color:#9a8f82; font-weight:600;">Fetching patient data…</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI section removed as requested -->

            <!-- ── TABS ── -->
            <div class="gp-tabs-card hidden" id="historyContainer">

                <!-- Tab Navigation -->
                <div class="gp-tabs-nav">
                    <div class="gp-tab active" onclick="switchTab('overview')" id="tab-overview">
                        <i class="fas fa-columns"></i> Overview
                    </div>
                    <div class="gp-tab" onclick="switchTab('bills')" id="tab-bills">
                        <i class="fas fa-file-invoice-dollar"></i> Bills <span class="gp-cnt" id="billCount">0</span>
                    </div>
                    <div class="gp-tab" onclick="switchTab('timeline')" id="tab-timeline"><i class="fas fa-stream"></i> Timeline</div>
                </div>

                <!-- OVERVIEW TAB -->
                <div id="overview-content" class="gp-panel active">
                    <div style="padding: 24px;">
                        <div class="gp-ms-title"><i class="fas fa-history"></i> Recent Visits</div>
                        <div style="border:1.5px solid #ede8dd; border-radius:16px; overflow:hidden;">
                            <table id="appointmentsTable">
                                <thead><tr>
                                    <th>Date &amp; Time</th>
                                    <th>Doctor</th>
                                    <th>Department</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr></thead>
                                <tbody>
                                    <tr><td colspan="5" style="text-align:center;padding:40px;color:#9a8f82;">Loading appointments…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- APPOINTMENTS TAB (For generic tab support if needed, mostly handled in Overview now) -->
                <div id="appointments-content" class="gp-panel">
                    <div style="padding:40px; text-align:center; color:#9a8f82; font-weight:600;">
                        Appointments are now shown in the Overview tab.
                    </div>
                </div>

                <!-- BILLS TAB -->
                <div id="bills-content" class="gp-panel">
                    <div style="overflow-x:auto;">
                        <table id="billsTable">
                            <thead><tr>
                                <th>Bill ID</th>
                                <th>Date</th>
                                <th>Purpose</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr></thead>
                            <tbody>
                                <tr><td colspan="5" style="text-align:center;padding:40px;color:#9a8f82;">Loading bills…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TIMELINE TAB -->
                <div id="timeline-content" class="gp-panel">
                    <div class="gp-layout-2col">
                        <!-- Left: Timeline -->
                        <div class="gp-timeline-wrap" id="patientTimelineContainer">
                            <div style="padding:40px; text-align:center; color:#9a8f82;">Loading timeline…</div>
                        </div>
                        
                        <!-- Right: Health Overview Panel -->
                        <div>
                            <div class="gp-med-summary" style="position:sticky; top:24px;">
                                <div class="gp-ms-title"><i class="fas fa-chart-pie"></i> Health Overview</div>
                                
                                <div style="display:flex; justify-content:center; margin:30px 0;">
                                    <div id="healthScoreChart" style="position:relative; width:140px; height:140px; border-radius:50%; background:conic-gradient(#1f6b4a 85%, #ede8dd 0); display:flex; align-items:center; justify-content:center;">
                                        <div style="position:absolute; width:110px; height:110px; background:#fff; border-radius:50%; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow:inset 0 4px 10px rgba(0,0,0,0.05);">
                                            <span id="healthScoreVal" style="font-size:2rem; font-weight:900; color:#1a1210; line-height:1;">85</span>
                                            <span style="font-size:0.7rem; font-weight:700; color:#9a8f82; text-transform:uppercase;">Health Score</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="gp-ms-item">
                                    <div class="gp-ms-ico"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div class="gp-ms-lbl">Risk Level</div>
                                        <div class="gp-ms-val" id="riskLevelVal" style="color:#2e7d32; font-weight:800;">Low Risk</div>
                                    </div>
                                </div>
                                <div class="gp-ms-item">
                                    <div class="gp-ms-ico"><i class="fas fa-user-md"></i></div>
                                    <div>
                                        <div class="gp-ms-lbl">Primary Physician</div>
                                        <div class="gp-ms-val" id="primaryPhysicianVal">Not Assigned</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /gp-wrap -->
        </main>
    </div>
</div>

<script>
    let patientId = "<?= htmlspecialchars($patientId) ?>";
    if (!patientId) {
        patientId = sessionStorage.getItem('currentPatientId') || '';
    } else {
        sessionStorage.setItem('currentPatientId', patientId);
    }

    function switchTab(tabName) {
        document.querySelectorAll('.gp-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.gp-panel').forEach(c => c.classList.remove('active'));
        document.getElementById(`tab-${tabName}`).classList.add('active');
        document.getElementById(`${tabName}-content`).classList.add('active');
    }

    function bookAppointment() {
        if (patientId) {
            window.location.href = `appointment_management.php?patient_id=${patientId}&action=new`;
        }
    }
</script>
<script src="assets/js/patient_profile.js?v=<?= time() ?>"></script>
</body>
</html>
