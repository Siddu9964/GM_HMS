<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';
$patientId = $_GET['patient_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Vitals Recording - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1f6b4a;
            --primary-light: #319e6e;
            --primary-dark: #144d34;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #1A237E;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
            --glass: rgba(255, 255, 255, 0.9);
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f1f5f9;
            background-image: radial-gradient(at 0% 0%, rgba(31, 107, 74, 0.05) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
        }

        .main-layout {
            display: flex;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: none;
            padding: 0;
            margin: 0;
        }

        /* Page Header Enhancements */
        .page-header {
            margin-bottom: 2rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .page-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            color: var(--slate-900);
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Patient Identity Card - Premium Medical Teal */
        .patient-focus-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(31, 107, 74, 0.3), 0 10px 10px -5px rgba(31, 107, 74, 0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideInLeft 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .patient-focus-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(31, 107, 74, 0.2) 0%, transparent 80%);
            border-radius: 50%;
        }

        .patient-identity {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .patient-avatar {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(4px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'Outfit';
            color: white;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .identity-text h2 {
            font-family: 'Outfit';
            font-size: 1.15rem;
            margin-bottom: 0.15rem;
        }

        .identity-text p {
            color: #cbd5e1;
            font-size: 0.75rem;
            display: flex;
            gap: 1rem;
        }

        .patient-loc-badge {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
            position: relative;
            z-index: 1;
            text-align: right;
        }

        /* Modern Form Styling */
        .vitals-form-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid white;
            animation: fadeInUp 0.8s ease-out;
        }

        .vitals-section-title {
            font-family: 'Outfit';
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .vitals-section-title i {
            color: var(--primary);
            background: var(--slate-100);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-size: 0.85rem;
        }

        .advanced-vitals-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        /* Vital Input Group */
        .vital-input-container {
            position: relative;
            transition: all 0.3s;
        }

        .vital-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .vital-label span {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--slate-700);
        }

        .vital-range-indicator {
            font-size: 0.65rem;
            color: #94a3b8;
        }

        .vital-input-wrapper {
            display: flex;
            align-items: center;
            background: var(--slate-50);
            border: 2px solid var(--slate-200);
            border-radius: 0.75rem;
            padding: 0.25rem 0.25rem 0.25rem 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .vital-input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
            transform: translateY(-2px);
        }

        .vital-input-wrapper i {
            color: #94a3b8;
            margin-right: 0.75rem;
        }

        .vital-control {
            flex: 1;
            border: none;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--slate-900);
            outline: none;
            background: transparent;
        }

        .vital-unit {
            background: var(--slate-100);
            color: var(--slate-700);
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Status Colors & Animations */
        .vital-status-normal .vital-input-wrapper { 
            border-color: var(--success); 
            background: rgba(16, 185, 129, 0.05);
        }
        .vital-status-warning .vital-input-wrapper { 
            border-color: var(--warning); 
            background: rgba(245, 158, 11, 0.05);
        }
        .vital-status-danger .vital-input-wrapper { 
            border-color: var(--danger); 
            background: rgba(239, 68, 68, 0.05);
            animation: pulse-danger 1.5s infinite;
        }

        @keyframes pulse-danger {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Status Badges */
        .status-badge {
            display: none;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .vital-status-normal .badge-normal { display: flex; background: var(--success); color: white; }
        .vital-status-warning .badge-warning { display: flex; background: var(--warning); color: white; }
        .vital-status-danger .badge-danger { display: flex; background: var(--danger); color: white; }

        /* Range Meter */
        .range-meter-container {
            height: 4px;
            background: var(--slate-100);
            border-radius: 2px;
            margin-top: 0.75rem;
            overflow: hidden;
            display: none;
        }

        .vital-status-normal .range-meter-container,
        .vital-status-warning .range-meter-container,
        .vital-status-danger .range-meter-container {
            display: block;
        }

        .range-meter-bar {
            height: 100%;
            width: 0%;
            transition: width 0.5s cubic-bezier(0.16, 1, 0.3, 1), background 0.3s;
        }

        .vital-status-normal .range-meter-bar { background: var(--success); }
        .vital-status-warning .range-meter-bar { background: var(--warning); }
        .vital-status-danger .range-meter-bar { background: var(--danger); }

        /* Tooltip Style */
        .status-tooltip {
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: var(--slate-800);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 10;
        }

        .vital-input-wrapper:hover .status-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* AVPU Scale Styling */
        .avpu-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .avpu-option {
            position: relative;
            cursor: pointer;
        }

        .avpu-option input {
            position: absolute;
            opacity: 0;
        }

        .avpu-box {
            background: var(--slate-50);
            padding: 0.75rem;
            border-radius: 0.75rem;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid var(--slate-200);
        }

        .avpu-box h4 {
            font-family: 'Outfit';
            font-size: 1rem;
            margin-bottom: 0.15rem;
            color: var(--slate-900);
        }

        .avpu-box p {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #64748b;
        }

        .avpu-option input:checked + .avpu-box {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 10px 15px -3px rgba(31, 107, 74, 0.2);
            transform: translateY(-5px);
        }

        .avpu-option input:checked + .avpu-box h4 {
            color: var(--primary);
        }

        /* Large Inputs */
        .remarks-container {
            margin-top: 1rem;
        }

        .remarks-box {
            width: 100%;
            background: var(--slate-50);
            border: 2px solid var(--slate-200);
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.3s;
            min-height: 80px;
            resize: vertical;
        }

        .remarks-box:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
        }

        /* Action Button */
        .actions-bar {
            margin-top: 3rem;
            display: flex;
            gap: 1.5rem;
        }

        .btn-premium {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 0.75rem;
            font-family: 'Outfit';
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.3);
        }

        .btn-premium:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(31, 107, 74, 0.4);
        }

        .btn-cancel {
            padding: 1rem 1.5rem;
            background: white;
            border: 2px solid var(--slate-200);
            border-radius: 0.75rem;
            font-family: 'Outfit';
            font-size: 0.95rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: var(--slate-50);
            border-color: var(--slate-300);
            color: var(--slate-800);
        }

        /* BP Split Input */
        .bp-split {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
        }

        .bp-control {
            width: 50%;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            text-align: center;
        }

        .bp-separator {
            font-size: 1.25rem;
            color: var(--slate-300);
            font-weight: 300;
        }

        /* Scrollable Form Area */
        .form-scroll-area {
            max-height: 58vh;
            overflow-y: auto;
            padding-right: 1rem;
            margin-right: -1rem; /* Offset for internal padding */
            margin-bottom: 1.5rem;
            scrollbar-width: thin;
            scrollbar-color: var(--slate-200) transparent;
        }

        .form-scroll-area::-webkit-scrollbar {
            width: 5px;
        }

        .form-scroll-area::-webkit-scrollbar-track {
            background: transparent;
        }

        .form-scroll-area::-webkit-scrollbar-thumb {
            background: var(--slate-200);
            border-radius: 10px;
        }

        .form-scroll-area::-webkit-scrollbar-thumb:hover {
            background: var(--slate-300);
        }

        /* Vitals History Styling */
        .history-card {
            margin-top: 2rem;
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--slate-100);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--slate-50);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .history-table th {
            text-align: left;
            padding: 0.75rem;
            color: var(--slate-500);
            font-weight: 600;
            border-bottom: 2px solid var(--slate-50);
        }

        .history-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--slate-50);
            color: var(--slate-700);
        }

        .history-date {
            font-weight: 700;
            color: var(--slate-900);
        }

        .history-val-badge {
            padding: 0.25rem 0.5rem;
            background: var(--slate-50);
            border-radius: 0.5rem;
            font-family: 'Inter';
            font-weight: 600;
        }

        .toast-notif {
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-family: 'Outfit';
            font-weight: 600;
            transform: translateX(200%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 5px solid var(--primary);
        }

        .toast-notif.show { transform: translateX(0); }
        .toast-notif.error { border-left-color: var(--danger); }
        .toast-notif.warning { border-left-color: var(--warning); }

        /* Animations */
        /* Form Toggle Animation */
        #newRecordSection {
            display: none;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 2rem;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-toggle-form {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-family: 'Outfit';
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-toggle-form:hover {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(31, 107, 74, 0.2);
        }

        .btn-toggle-form.active {
            background: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        /* Modern Card History */
        .history-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1rem;
            border-radius: 1.25rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-input {
            flex: 1;
            border: 1px solid var(--slate-200);
            padding: 0.6rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            outline: none;
        }

        .filter-input:focus {
            border-color: var(--primary);
        }

        .history-timeline {
            display: grid;
            gap: 1.5rem;
        }

        .history-item-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--slate-100);
            transition: all 0.3s;
        }

        .history-item-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .card-date {
            font-family: 'Outfit';
            font-weight: 700;
            color: var(--slate-900);
            font-size: 1.1rem;
        }

        .card-vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }

        .mini-trend {
            padding: 0.75rem;
            background: var(--slate-50);
            border-radius: 1rem;
        }

        .trend-label {
            font-size: 0.7rem;
            color: var(--slate-500);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .trend-value {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--slate-900);
            margin-bottom: 0.5rem;
        }

        .trend-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .trend-fill {
            height: 100%;
            transition: width 1s ease-out;
        }

        .details-toggle {
            margin-top: 1rem;
            width: 100%;
            border: none;
            background: var(--slate-50);
            padding: 0.5rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--slate-500);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .card-details {
            display: none;
            padding-top: 1rem;
            margin-top: 1rem;
            border-top: 1px dashed var(--slate-200);
            font-size: 0.875rem;
            color: var(--slate-600);
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .advanced-vitals-grid { grid-template-columns: 1fr; }
            .avpu-container { grid-template-columns: repeat(2, 1fr); }
            .patient-focus-card { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .patient-loc-badge { align-self: flex-start; }
            .main-content { padding: 1rem; }
            .vitals-form-card { padding: 1.5rem; }
            .header-actions { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }

        /* History Modal Overlay */
        .history-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .history-modal {
            background: #f8fafc;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            border-radius: 2rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            background: white;
            border-bottom: 1px solid var(--slate-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: var(--slate-100);
            color: var(--slate-600);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalSlide { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Latest Summary Card */
        .latest-summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.1);
            border: 1px solid white;
            position: relative;
        }

        .summary-badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: var(--success);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .history-btn-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .btn-view-all {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.85rem 2rem;
            border-radius: 1rem;
            font-weight: 700;
            font-family: 'Outfit';
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-view-all:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Sidebar -->
        <?php include 'includes/nurse_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="content-wrapper">
            <!-- Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>
            
            <!-- Page Content -->
            <div class="main-content">
                <div class="container">
                    <div class="header-actions">
                        <div class="page-header" style="margin:0">
                            <h1>Clinical Vitals</h1>
                            <div class="breadcrumb">
                                <i class="fas fa-home"></i> Home 
                                <i class="fas fa-chevron-right" style="font-size: 0.6rem;"></i> 
                                Clinical Recording
                            </div>
                        </div>
                        <button class="btn-toggle-form" id="toggleFormBtn" onclick="toggleForm()">
                            <i class="fas fa-plus-circle"></i> New Vital Record
                        </button>
                    </div>

                    <!-- Enhanced Patient Context Banner -->
                    <div class="patient-focus-card">
                        <div class="patient-identity">
                            <div class="patient-avatar" id="patientInitial">?</div>
                            <div class="identity-text">
                                <h2 id="patientFullName">Loading Patient...</h2>
                                <p id="patientSubText">
                                    <span>PID: <strong class="text-white" id="displayPid"><?php echo htmlspecialchars($patientId ?: '---'); ?></strong></span>
                                    <span>Age: ---</span>
                                    <span>Sex: ---</span>
                                </p>
                            </div>
                        </div>
                        <div class="patient-loc-badge" id="patientLocation">
                            <div id="locBed">Bed: ---</div>
                            <div id="locWard" style="font-size: 0.7rem; opacity: 0.8; font-weight: 400;">Ward: ---</div>
                        </div>
                    </div>

                    <div id="newRecordSection">
                        <div class="vitals-form-card">
                            <form id="vitalsForm" onsubmit="return false;">
                                <div class="form-scroll-area">
                                    <!-- Patient Selector (Hidden if patientId is provided) -->
                                    <?php if (!$patientId): ?>
                                    <div class="vitals-section-title">
                                        <i class="fas fa-user-check"></i> Select Active Patient
                                    </div>
                                    <div class="form-group" style="margin-bottom: 2.5rem;">
                                        <div class="vital-input-wrapper">
                                            <i class="fas fa-search"></i>
                                            <select class="vital-control" id="patientSelect" required style="width: 100%;">
                                                <option value="">Choosing Patient from Ward List...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="vitals-section-title">
                                        <i class="fas fa-heartbeat"></i> Primary Assessment
                                    </div>

                                    <div class="advanced-vitals-grid">
                                        <!-- Temperature -->
                                        <div class="vital-input-container" id="container-temp">
                                            <div class="vital-label">
                                                <span>Body Temperature</span>
                                                <div class="status-badges">
                                                    <span class="status-badge badge-normal"><i class="fas fa-check-circle"></i> Normal</span>
                                                    <span class="status-badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Warning</span>
                                                    <span class="status-badge badge-danger"><i class="fas fa-exclamation-circle"></i> Critical</span>
                                                </div>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <div class="status-tooltip" id="tooltip-temp">Enter temperature value</div>
                                                <i class="fas fa-thermometer-half"></i>
                                                <input type="number" step="0.1" class="vital-control" id="temp" placeholder="98.6" oninput="validateVital('temp', 97, 99)">
                                                <div class="vital-unit">°F</div>
                                            </div>
                                            <div class="range-meter-container">
                                                <div class="range-meter-bar" id="meter-temp"></div>
                                            </div>
                                            <span class="vital-range-indicator" style="display:block; margin-top:0.35rem">Recommended: 97.0 - 99.0 °F</span>
                                        </div>

                                        <!-- Blood Pressure -->
                                        <div class="vital-input-container" id="container-bp">
                                            <div class="vital-label">
                                                <span>Blood Pressure</span>
                                                <div class="status-badges">
                                                    <span class="status-badge badge-normal"><i class="fas fa-check-circle"></i> Normal</span>
                                                    <span class="status-badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Pre-HTN</span>
                                                    <span class="status-badge badge-danger"><i class="fas fa-exclamation-circle"></i> High BP</span>
                                                </div>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <div class="status-tooltip" id="tooltip-bp">Enter Systolic / Diastolic</div>
                                                <i class="fas fa-stethoscope"></i>
                                                <div class="bp-split">
                                                    <input type="number" class="bp-control" id="bp_s" placeholder="120" oninput="validateBP()">
                                                    <span class="bp-separator">/</span>
                                                    <input type="number" class="bp-control" id="bp_d" placeholder="80" oninput="validateBP()">
                                                </div>
                                                <div class="vital-unit">mmHg</div>
                                            </div>
                                            <div class="range-meter-container">
                                                <div class="range-meter-bar" id="meter-bp"></div>
                                            </div>
                                            <span class="vital-range-indicator" style="display:block; margin-top:0.35rem">Ideal: 120 / 80 mmHg</span>
                                        </div>

                                        <!-- Pulse Rate -->
                                        <div class="vital-input-container" id="container-pulse">
                                            <div class="vital-label">
                                                <span>Pulse Rate (Heart)</span>
                                                <div class="status-badges">
                                                    <span class="status-badge badge-normal"><i class="fas fa-check-circle"></i> Normal</span>
                                                    <span class="status-badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Arrythmia</span>
                                                    <span class="status-badge badge-danger"><i class="fas fa-exclamation-circle"></i> Critical</span>
                                                </div>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <div class="status-tooltip" id="tooltip-pulse">Enter heart rate (BPM)</div>
                                                <i class="fas fa-heart"></i>
                                                <input type="number" class="vital-control" id="pulse" placeholder="72" oninput="validateVital('pulse', 60, 100)">
                                                <div class="vital-unit">BPM</div>
                                            </div>
                                            <div class="range-meter-container">
                                                <div class="range-meter-bar" id="meter-pulse"></div>
                                            </div>
                                            <span class="vital-range-indicator" style="display:block; margin-top:0.35rem">Normal Range: 60 - 100 BPM</span>
                                        </div>

                                        <!-- Respiratory Rate -->
                                        <div class="vital-input-container" id="container-resp">
                                            <div class="vital-label">
                                                <span>Respiratory Rate</span>
                                                <div class="status-badges">
                                                    <span class="status-badge badge-normal"><i class="fas fa-check-circle"></i> Normal</span>
                                                    <span class="status-badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Tachypnea</span>
                                                    <span class="status-badge badge-danger"><i class="fas fa-exclamation-circle"></i> Distress</span>
                                                </div>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <div class="status-tooltip" id="tooltip-resp">Enter breaths per minute</div>
                                                <i class="fas fa-wind"></i>
                                                <input type="number" class="vital-control" id="resp" placeholder="16" oninput="validateVital('resp', 12, 20)">
                                                <div class="vital-unit">/min</div>
                                            </div>
                                            <div class="range-meter-container">
                                                <div class="range-meter-bar" id="meter-resp"></div>
                                            </div>
                                            <span class="vital-range-indicator" style="display:block; margin-top:0.35rem">Normal: 12 - 20 / min</span>
                                        </div>

                                        <!-- SpO2 -->
                                        <div class="vital-input-container" id="container-spo2">
                                            <div class="vital-label">
                                                <span>Oxygen Saturation</span>
                                                <div class="status-badges">
                                                    <span class="status-badge badge-normal"><i class="fas fa-check-circle"></i> Optimal</span>
                                                    <span class="status-badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Low</span>
                                                    <span class="status-badge badge-danger"><i class="fas fa-exclamation-circle"></i> Hypoxia</span>
                                                </div>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <div class="status-tooltip" id="tooltip-spo2">Enter SpO2 percentage</div>
                                                <i class="fas fa-tint"></i>
                                                <input type="number" class="vital-control" id="spo2" placeholder="98" oninput="validateVital('spo2', 95, 100, true)">
                                                <div class="vital-unit">%</div>
                                            </div>
                                            <div class="range-meter-container">
                                                <div class="range-meter-bar" id="meter-spo2"></div>
                                            </div>
                                            <span class="vital-range-indicator" style="display:block; margin-top:0.35rem">Target: 95 - 100 %</span>
                                        </div>

                                        <!-- Weight -->
                                        <div class="vital-input-container">
                                            <div class="vital-label">
                                                <span>Patient Weight</span>
                                                <span class="vital-range-indicator">Static / Change</span>
                                            </div>
                                            <div class="vital-input-wrapper">
                                                <i class="fas fa-weight"></i>
                                                <input type="number" step="0.1" class="vital-control" id="weight" placeholder="0.0">
                                                <div class="vital-unit">kg</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="vitals-section-title">
                                        <i class="fas fa-brain"></i> Neurological Status (AVPU)
                                    </div>

                                    <div class="avpu-container">
                                        <label class="avpu-option">
                                            <input type="radio" name="avpu" value="Alert" checked>
                                            <div class="avpu-box">
                                                <h4>A</h4>
                                                <p>Alert</p>
                                            </div>
                                        </label>
                                        <label class="avpu-option">
                                            <input type="radio" name="avpu" value="Voice">
                                            <div class="avpu-box">
                                                <h4>V</h4>
                                                <p>Voice</p>
                                            </div>
                                        </label>
                                        <label class="avpu-option">
                                            <input type="radio" name="avpu" value="Pain">
                                            <div class="avpu-box">
                                                <h4>P</h4>
                                                <p>Pain</p>
                                            </div>
                                        </label>
                                        <label class="avpu-option">
                                            <input type="radio" name="avpu" value="Unresponsive">
                                            <div class="avpu-box">
                                                <h4>U</h4>
                                                <p>Unres.</p>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="vitals-section-title">
                                        <i class="fas fa-comment-medical"></i> Nursing Remarks & Observations
                                    </div>
                                    <div class="remarks-container">
                                        <textarea class="remarks-box" id="remarks" placeholder="Include details about patient's general appearance, physical distress, or specific symptoms observed during recording..."></textarea>
                                    </div>
                                </div>

                                <div class="actions-bar">
                                    <button type="button" class="btn-cancel" onclick="toggleForm()">Discard</button>
                                    <button type="button" class="btn-premium" onclick="saveVitals()">
                                        <i class="fas fa-cloud-upload-alt"></i> Finalize & Record Vitals
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Clinical History Dashboard -->
                    <div style="margin-bottom: 2rem;">
                        <h2 style="font-family:'Outfit'; font-size:1.5rem; margin-bottom:1rem">Clinical Trends History</h2>
                        
                        <!-- History Filters -->
                        <div class="history-controls">
                            <input type="text" class="filter-input" id="historySearch" placeholder="Search by remarks or nurse..." oninput="filterHistory()">
                            <input type="date" class="filter-input" id="historyDateFilter" onchange="filterHistory()" style="max-width: 200px;">
                            <button class="btn-wipe" onclick="wipeHistory()" style="background:#fee2e2; color:#ef4444; border:1px solid #fecaca; padding:0.6rem 1rem; border-radius:0.75rem; font-weight:600; cursor:pointer">
                                <i class="fas fa-trash-alt"></i> Wipe History
                            </button>
                        </div>

                        <div id="historyTimeline" class="history-timeline">
                            <!-- Cards will be injected here -->
                            <div style="text-align:center; padding:3rem; background:white; border-radius:1.5rem">
                                <i class="fas fa-folder-open" style="font-size:3rem; color:#e2e8f0; margin-bottom:1rem"></i>
                                <p style="color:#94a3b8">Select a patient to view clinical history</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clinical History Modal -->
    <div id="historyOverlay" class="history-overlay">
        <div class="history-modal">
            <div class="modal-header">
                <div>
                    <h2 style="font-family:'Outfit'; font-size:1.5rem">Clinical History Timeline</h2>
                    <p style="font-size:0.875rem; color:#64748b">Complete chronological assessments for <span id="modalPatientName">Patient</span></p>
                </div>
                <button class="modal-close" onclick="closeHistoryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalHistoryContent">
                <!-- Detailed history cards here -->
            </div>
        </div>
    </div>

    <script>
        // Advanced Validation Engine
        function validateVital(id, min, max, isReverse = false) {
            const input = document.getElementById(id);
            const val = parseFloat(input.value);
            const container = document.getElementById('container-' + id);
            const meter = document.getElementById('meter-' + id);
            const tooltip = document.getElementById('tooltip-' + id);
            
            if (isNaN(val)) {
                container.className = 'vital-input-container';
                if (meter) meter.style.width = '0%';
                if (tooltip) tooltip.textContent = 'Enter value';
                return;
            }

            let status = 'normal';
            let message = 'Clinically Normal';
            let percentage = 0;

            if (isReverse) { // SpO2 Logic
                percentage = Math.min(100, val);
                if (val < 90) { status = 'danger'; message = 'CRITICAL: Hypoxia Detected!'; }
                else if (val < 95) { status = 'warning'; message = 'Warning: Low Saturation'; }
            } else { // Generic Logic
                const range = max - min;
                const offset = val - min;
                percentage = Math.max(0, Math.min(100, (offset / range) * 100));
                
                if (val > max + range*0.5 || val < min - range*0.5) { 
                    status = 'danger'; 
                    message = 'CRITICAL: Immediate Attention Required!'; 
                    if (val > max) percentage = 100; else percentage = 5;
                }
                else if (val > max || val < min) { 
                    status = 'warning'; 
                    message = 'Warning: Outside Normal Range'; 
                }
            }

            container.className = 'vital-input-container vital-status-' + status;
            if (meter) meter.style.width = percentage + '%';
            if (tooltip) tooltip.textContent = message;

            if (status === 'danger') {
                showToast(`Critical ${id.toUpperCase()} detected!`, 'error');
            }
        }

        function validateBP() {
            const s = parseFloat(document.getElementById('bp_s').value);
            const d = parseFloat(document.getElementById('bp_d').value);
            const container = document.getElementById('container-bp');
            const meter = document.getElementById('meter-bp');
            const tooltip = document.getElementById('tooltip-bp');

            if (isNaN(s) || isNaN(d)) {
                container.className = 'vital-input-container';
                if (meter) meter.style.width = '0%';
                return;
            }

            let status = 'normal';
            let message = 'Optimal Blood Pressure';
            
            if (s >= 160 || d >= 100) { status = 'danger'; message = 'Hypertensive Crisis!'; }
            else if (s >= 140 || d >= 90) { status = 'warning'; message = 'Stage 2 Hypertension'; }
            else if (s >= 130 || d >= 85) { status = 'warning'; message = 'Pre-Hypertension'; }
            
            container.className = 'vital-input-container vital-status-' + status;
            if (meter) meter.style.width = ((s/200)*100) + '%';
            if (tooltip) tooltip.textContent = message;
        }

        function showToast(msg, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notif ${type}`;
            toast.innerHTML = `<i class="fas fa-bell"></i> ${msg}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        let fullHistory = [];
        let activePatient = null;

        async function loadData() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                let targetId = urlParams.get('patient_id') || sessionStorage.getItem('selected_patient_id');
                
                if (urlParams.has('patient_id')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const patients = result.data.assigned_patients;
                    const select = document.getElementById('patientSelect');
                    if (select) {
                        select.innerHTML = '<option value="">Searching Ward List...</option>';
                        patients.forEach(p => {
                            const option = document.createElement('option');
                            option.value = p.patient_id;
                            option.textContent = `${p.first_name} ${p.last_name} (${p.bed_number})`;
                            if (p.patient_id === targetId) option.selected = true;
                            select.appendChild(option);
                        });
                        
                        select.addEventListener('change', (e) => {
                            const selected = patients.find(p => p.patient_id === e.target.value);
                            updateBanner(selected);
                        });
                    }

                    if (targetId) {
                        const active = patients.find(p => p.patient_id === targetId);
                        if (active) updateBanner(active);
                    }
                }
            } catch (error) {
                console.error('Error loading clinical data:', error);
            }
        }

        function updateBanner(p) {
            if (!p) return;
            activePatient = p;
            document.getElementById('patientInitial').textContent = (p.first_name || '?').charAt(0);
            document.getElementById('patientFullName').textContent = `${p.first_name || ''} ${p.last_name || ''}`;
            document.getElementById('displayPid').textContent = p.patient_id || '---';
            document.getElementById('patientSubText').innerHTML = `
                <span>ID: <strong style="color:white">${p.patient_id || '---'}</strong></span>
                <span>Age: ${p.age || '---'}</span>
                <span>Sex: ${p.sex || '---'}</span>
            `;
            
            document.getElementById('locBed').textContent = `Room: ${p.room_number || '---'} | Bed: ${p.bed_number || '---'}`;
            document.getElementById('locWard').textContent = `Ward: ${p.room_type || '---'} | Floor: ${p.floor_name || '---'}`;
            
            loadHistory(p.patient_id);
        }

        function toggleForm() {
            const section = document.getElementById('newRecordSection');
            const btn = document.getElementById('toggleFormBtn');
            
            if (section.style.display === 'block') {
                section.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-plus-circle"></i> New Vital Record';
                btn.classList.remove('active');
            } else {
                section.style.display = 'block';
                section.scrollIntoView({ behavior: 'smooth' });
                btn.innerHTML = '<i class="fas fa-times-circle"></i> Cancel Record';
                btn.classList.add('active');
            }
        }

        async function loadHistory(patientId) {
            const timeline = document.getElementById('historyTimeline');
            timeline.innerHTML = '<div style="text-align:center; padding:3rem"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i><p style="margin-top:1rem; color:#64748b">Analyzing clinical history...</p></div>';
            
            try {
                const response = await fetch(`api/vitals_rest.php?patient_id=${patientId}`);
                const result = await response.json();

                if (result.success && result.history) {
                    fullHistory = result.history.reverse();
                    renderLatestSummary(fullHistory);
                } else {
                    timeline.innerHTML = '<div style="text-align:center; padding:3rem; background:white; border-radius:1.5rem"><p style="color:#94a3b8">No clinical assessments found for this patient.</p></div>';
                }
            } catch (error) {
                console.error('History Fetch Error:', error);
            }
        }

        function renderLatestSummary(data) {
            const timeline = document.getElementById('historyTimeline');
            if (data.length === 0) {
                timeline.innerHTML = '<div style="text-align:center; padding:3rem; background:white; border-radius:1.5rem"><p style="color:#94a3b8">No clinical assessments available.</p></div>';
                return;
            }

            const latest = data[0];
            timeline.innerHTML = `
                <div class="latest-summary-card">
                    <div class="summary-badge">Latest Assessment</div>
                    
                    <div style="margin-bottom: 2rem;">
                        <div style="font-family:'Outfit'; font-size:1.25rem; font-weight:700; color:var(--slate-900)">
                            <i class="fas fa-clock" style="color:var(--primary); margin-right:0.5rem"></i>
                            ${new Date(latest.date).toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' })} at ${latest.time || '---'}
                        </div>
                        <p style="font-size:0.875rem; color:#64748b; margin-top:0.25rem">
                            Recorded by: <span style="font-weight:600; color:var(--slate-800)">${latest.recorded_by || 'Hospital Staff'}</span>
                        </p>
                    </div>

                    <div class="card-vitals-grid">
                        ${renderTrend('Temperature', latest.temperature, '°F', 97, 99, '#ef4444')}
                        ${renderTrend('BP Systolic', latest.bp_systolic, 'mmHg', 110, 140, '#1f6b4a')}
                        ${renderTrend('Pulse Rate', latest.pulse_rate, 'BPM', 60, 100, '#10b981')}
                        ${renderTrend('SpO2 Sat.', latest.spo2, '%', 95, 100, '#0ea5e9')}
                    </div>

                    <div class="history-btn-container">
                        <button class="btn-view-all" onclick="openHistoryModal()">
                            <i class="fas fa-history"></i> View Full Clinical History (${data.length} Records)
                        </button>
                    </div>
                </div>
            `;
        }

        function openHistoryModal() {
            document.getElementById('modalPatientName').textContent = activePatient ? `${activePatient.first_name} ${activePatient.last_name}` : 'Patient';
            const content = document.getElementById('modalHistoryContent');
            content.innerHTML = '';
            
            fullHistory.forEach((entry, index) => {
                const card = document.createElement('div');
                card.className = 'history-item-card';
                card.style.marginBottom = '1.5rem';
                card.innerHTML = `
                    <div class="card-top">
                        <div class="card-date">
                            <i class="fas fa-calendar-check" style="color:var(--success); margin-right:0.5rem"></i>
                            ${new Date(entry.date).toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' })} at ${entry.time || '---'}
                        </div>
                        <span style="font-size:0.75rem; color:#94a3b8; font-weight:600">ID: #${activePatient.patient_id.slice(-6).toUpperCase()}</span>
                    </div>
                    
                    <div class="card-vitals-grid" style="grid-template-columns: repeat(4, 1fr);">
                        <div class="mini-trend">
                            <div class="trend-label">Temp</div>
                            <div class="trend-value" style="font-size:1rem">${entry.temperature}°F</div>
                        </div>
                        <div class="mini-trend">
                            <div class="trend-label">BP (S/D)</div>
                            <div class="trend-value" style="font-size:1rem">${entry.bp_systolic}/${entry.bp_diastolic}</div>
                        </div>
                        <div class="mini-trend">
                            <div class="trend-label">Pulse</div>
                            <div class="trend-value" style="font-size:1rem">${entry.pulse_rate}</div>
                        </div>
                        <div class="mini-trend">
                            <div class="trend-label">RR / SpO2</div>
                            <div class="trend-value" style="font-size:1rem">${entry.respiratory_rate} / ${entry.spo2}%</div>
                        </div>
                    </div>

                    <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9; font-size:0.875rem">
                        <p><strong>Clinical Remarks:</strong> <span style="color:#64748b; font-style:italic">"${entry.remarks || 'No remarks recorded for this session.'}"</span></p>
                        <p style="margin-top:0.5rem; color:#94a3b8"><strong>Recorded by:</strong> ${entry.recorded_by || 'Staff'}</p>
                    </div>
                `;
                content.appendChild(card);
            });

            document.getElementById('historyOverlay').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryModal() {
            document.getElementById('historyOverlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function renderTrend(label, val, unit, min, max, color) {
            const percentage = Math.min(100, Math.max(0, ((val - (min-5)) / ((max+5) - (min-5))) * 100));
            return `
                <div class="mini-trend" style="padding:1rem">
                    <div class="trend-label">${label}</div>
                    <div class="trend-value" style="font-size:1.5rem">${val || '---'} <span style="font-size:0.8rem; font-weight:400">${unit}</span></div>
                    <div class="trend-bar">
                        <div class="trend-fill" style="width:${percentage}%; background:${color}"></div>
                    </div>
                </div>
            `;
        }

        function toggleDetails(idx) {
            const el = document.getElementById(`details-${idx}`);
            el.style.display = el.style.display === 'block' ? 'none' : 'block';
        }

        function filterHistory() {
            const search = document.getElementById('historySearch').value.toLowerCase();
            const date = document.getElementById('historyDateFilter').value;
            
            const filtered = fullHistory.filter(entry => {
                const matchesSearch = (entry.remarks || '').toLowerCase().includes(search);
                const matchesDate = !date || entry.date === date;
                return matchesSearch && matchesDate;
            });
            
            renderLatestSummary(filtered); // Changed to renderLatestSummary
        }

        async function saveVitals() {
            if (!activePatient) {
                showToast('Please select a patient first', 'warning');
                return;
            }

            const btn = document.querySelector('.btn-premium');
            const originalContent = btn.innerHTML;
            
            try {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                const data = {
                    patient_id: activePatient.patient_id,
                    visit_id: activePatient.admission_id,
                    visit_type: 'IPD',
                    temperature: document.getElementById('temp').value,
                    bp_systolic: document.getElementById('bp_s').value,
                    bp_diastolic: document.getElementById('bp_d').value,
                    pulse_rate: document.getElementById('pulse').value,
                    respiratory_rate: document.getElementById('resp').value,
                    spo2: document.getElementById('spo2').value,
                    weight: document.getElementById('weight').value,
                    consciousness_level: document.querySelector('input[name="avpu"]:checked').value,
                    remarks: document.getElementById('remarks').value
                };

                const response = await fetch('api/save_vitals.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Vitals Recorded Successfully!', 'success');
                    toggleForm();
                    document.getElementById('vitalsForm').reset();
                    loadHistory(activePatient.patient_id);
                } else {
                    throw new Error(result.message || 'Storage failed');
                }

            } catch (error) {
                console.error('Save Error:', error);
                showToast(error.message || 'Failed to save to database', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function wipeHistory() {
            if (!activePatient) return;
            if (!confirm('CAUTION: This will permanently erase the JSON clinical history for this patient. Action cannot be undone. Proceed?')) return;
            
            try {
                const response = await fetch(`api/vitals_rest.php?patient_id=${activePatient.patient_id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Clinical history wiped successfully', 'success');
                    loadHistory(activePatient.patient_id);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Wipe Error:', error);
                showToast('Failed to clear history', 'error');
            }
        }

        loadData();
    </script>
</body>
</html>
