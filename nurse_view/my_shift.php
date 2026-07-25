<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Nurse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shift - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1f6b4a;
            --primary-light: #2a8f63;
            --primary-dark: #154a33;
            --bg-color: #f3efe6;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(31, 107, 74, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-color); min-height: 100vh; display: flex; color: var(--text-main); }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .container { max-width: 1100px; margin: 0 auto; animation: fadeIn 0.5s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 15px;
        }
        .page-header h1 { 
            font-size: 28px; font-weight: 800; color: var(--primary); 
            display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px;
        }
        .page-header h1 i { background: var(--primary); color: var(--bg-color); padding: 10px; border-radius: 12px; font-size: 20px; }

        /* Premium Banner */
        .shift-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--bg-color); border-radius: 20px; padding: 35px 40px;
            margin-bottom: 30px; box-shadow: 0 15px 35px rgba(31, 107, 74, 0.2);
            display: flex; justify-content: space-between; align-items: center;
            position: relative; overflow: hidden;
        }
        .shift-banner::after {
            content: ''; position: absolute; right: -50px; top: -50px;
            width: 200px; height: 200px; background: rgba(243, 239, 230, 0.05);
            border-radius: 50%;
        }
        .shift-banner-left { position: relative; z-index: 1; }
        .shift-banner-left h2 { font-size: 26px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; }
        .shift-banner-left p { opacity: 0.9; font-size: 15px; font-weight: 400; display: flex; align-items: center; gap: 8px; }
        
        .shift-type-badge {
            position: relative; z-index: 1;
            background: rgba(243, 239, 230, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(243, 239, 230, 0.3);
            color: var(--bg-color); padding: 12px 28px;
            border-radius: 50px; font-weight: 700;
            font-size: 18px; letter-spacing: 0.5px;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        /* Modern Grid System */
        .dashboard-grid {
            display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;
        }
        @media(max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }

        /* Detail Cards */
        .card {
            background: var(--card-bg); border-radius: 20px; padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(31, 107, 74, 0.08);
        }
        .card-header {
            display: flex; align-items: center; gap: 12px; margin-bottom: 25px;
        }
        .card-header h3 { font-size: 18px; font-weight: 700; color: var(--primary-dark); }
        .card-header-icon {
            width: 40px; height: 40px; background: rgba(31, 107, 74, 0.1);
            color: var(--primary); border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }

        /* Timeline / Details Grid */
        .details-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
        .detail-item {
            background: var(--bg-color); padding: 16px; border-radius: 14px;
            border-left: 4px solid var(--primary); transition: all 0.2s ease;
        }
        .detail-item:hover { background: #ebe5d5; }
        .detail-label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; letter-spacing: 0.5px; }
        .detail-value { font-size: 16px; font-weight: 700; color: var(--primary-dark); }

        /* Assigned Beds (Pills Design) */
        .bed-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .bed-chip {
            background: var(--card-bg); color: var(--primary);
            border: 2px solid var(--primary-light); border-radius: 10px;
            padding: 8px 18px; font-size: 14px; font-weight: 700;
            display: flex; align-items: center; gap: 8px;
            transition: all 0.2s ease; box-shadow: 0 4px 10px rgba(31,107,74,0.05);
        }
        .bed-chip:hover {
            background: var(--primary); color: var(--bg-color);
            transform: scale(1.05);
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 18px; border-radius: 50px;
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .status-active    { background: var(--primary); color: var(--bg-color); }
        .status-scheduled { background: #e2d9c1; color: var(--primary-dark); }
        .status-completed { background: #cbd5e1; color: #334155; }

        /* Upcoming Shifts */
        .shift-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 20px; background: var(--bg-color); border-radius: 14px;
            margin-bottom: 12px; transition: all 0.2s; border: 1px solid transparent;
        }
        .shift-row:hover {
            background: #ebe5d5; border-color: var(--primary-light);
            transform: translateX(5px);
        }
        .shift-row-left { display: flex; flex-direction: column; gap: 5px; }
        .shift-row-date { font-weight: 700; color: var(--primary-dark); font-size: 15px; }
        .shift-row-detail { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 10px; }
        .shift-row-detail i { color: var(--primary); }

        .loading, .empty-state { text-align: center; padding: 60px; color: var(--primary); }
        .empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.2; display: block; }
        .empty-state h3 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/nurse_sidebar.php'; ?>
        <div class="content-wrapper">
            <?php include 'includes/nurse_navbar.php'; ?>
            <div class="main-content">
                <div class="container">
                    <div class="page-header">
                        <h1><i class="fas fa-clock"></i> My Shift</h1>
                        <div id="shiftStatusBadge"></div>
                    </div>

                    <div id="shiftData">
                        <div class="loading">
                            <i class="fas fa-circle-notch fa-spin fa-3x"></i>
                            <p style="margin-top:15px; font-weight:600; font-size:16px;">Synchronizing shift data...</p>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 30px;">
                        <div class="card-header">
                            <div class="card-header-icon"><i class="fas fa-calendar-alt"></i></div>
                            <h3>Upcoming Schedule</h3>
                        </div>
                        <div id="upcomingShifts">
                            <p style="color:var(--text-muted);font-size:14px;text-align:center;padding:20px;">Loading schedule...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function daysBetween(from, to) {
            if (!from || !to) return 0;
            const d1 = new Date(from), d2 = new Date(to);
            return Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
        }

        function shiftTime(type) {
            const times = {
                'Morning': '6:00 AM – 2:00 PM',
                'Evening': '2:00 PM – 10:00 PM',
                'Night':   '10:00 PM – 6:00 AM'
            };
            return times[type] || type || '—';
        }

        function statusClass(status) {
            if (!status) return 'status-scheduled';
            const s = status.toLowerCase();
            if (s === 'active')    return 'status-active';
            if (s === 'scheduled') return 'status-scheduled';
            return 'status-completed';
        }

        async function loadShiftData() {
            try {
                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (!result.success) throw new Error('API error');

                const shift = result.data.current_shift;
                const shiftContainer = document.getElementById('shiftData');

                if (shift) {
                    const days = daysBetween(shift.shift_date_from, shift.shift_date_to);

                    document.getElementById('shiftStatusBadge').innerHTML = `
                        <span class="status-badge ${statusClass(shift.status)}">
                            <i class="fas fa-circle" style="font-size:8px;"></i> ${shift.status || 'Scheduled'}
                        </span>`;

                    let bedsHtml = '<span style="color:var(--text-muted);font-size:14px;font-weight:500;">No beds assigned for this shift.</span>';
                    if (shift.assigned_beds) {
                        const beds = shift.assigned_beds.split(',').map(b => b.trim()).filter(Boolean);
                        bedsHtml = beds.map(b =>
                            `<span class="bed-chip"><i class="fas fa-bed"></i>${b}</span>`
                        ).join('');
                    }

                    shiftContainer.innerHTML = `
                        <!-- Banner -->
                        <div class="shift-banner">
                            <div class="shift-banner-left">
                                <h2>Current Assignment</h2>
                                <p>
                                    <i class="fas fa-calendar-check"></i> ${formatDate(shift.shift_date_from)} &nbsp;→&nbsp; ${formatDate(shift.shift_date_to)}
                                    &nbsp; | &nbsp; <i class="fas fa-hourglass-half"></i> ${days} day${days !== 1 ? 's' : ''} duration
                                </p>
                            </div>
                            <div class="shift-type-badge">
                                <i class="fas fa-sun"></i>${shift.shift_type || '—'}
                            </div>
                        </div>

                        <div class="dashboard-grid">
                            <!-- Left Column: Details -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-header-icon"><i class="fas fa-info-circle"></i></div>
                                    <h3>Shift Details</h3>
                                </div>
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <div class="detail-label">Start Date</div>
                                        <div class="detail-value">${formatDate(shift.shift_date_from)}</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">End Date</div>
                                        <div class="detail-value">${formatDate(shift.shift_date_to)}</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Shift Timing</div>
                                        <div class="detail-value">${shiftTime(shift.shift_type)}</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">Duration</div>
                                        <div class="detail-value">${days} Day${days !== 1 ? 's' : ''}</div>
                                    </div>
                                    <div class="detail-item" style="grid-column: 1 / -1;">
                                        <div class="detail-label">Location</div>
                                        <div class="detail-value">
                                            ${shift.ward_name || '—'} 
                                            <span style="color:var(--text-muted);font-size:14px;font-weight:500;">
                                                (${shift.floor_name || 'Floor not set'}${shift.work_area ? ' - ' + shift.work_area : ''})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Beds -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-header-icon"><i class="fas fa-bed"></i></div>
                                    <h3>Assigned Beds</h3>
                                </div>
                                <div class="bed-chips">
                                    ${bedsHtml}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('shiftStatusBadge').innerHTML = '';
                    shiftContainer.innerHTML = `
                        <div class="card empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Active Shift Today</h3>
                            <p style="color:var(--text-muted);">You don't have a shift assignment covering today's date.</p>
                        </div>`;
                }

                renderUpcomingShifts(result.data.upcoming_shifts);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('shiftData').innerHTML =
                    '<div class="card"><p style="color:red;text-align:center;">Error loading shift data.</p></div>';
            }
        }

        function renderUpcomingShifts(shifts) {
            const container = document.getElementById('upcomingShifts');
            if (!shifts || shifts.length === 0) {
                container.innerHTML = '<p style="color:var(--text-muted);font-size:15px;text-align:center;padding:20px;">No upcoming shifts scheduled in the system.</p>';
                return;
            }
            container.innerHTML = shifts.map(s => `
                <div class="shift-row">
                    <div class="shift-row-left">
                        <div class="shift-row-date">
                            ${formatDate(s.shift_date_from)} &nbsp;→&nbsp; ${formatDate(s.shift_date_to)}
                        </div>
                        <div class="shift-row-detail">
                            <i class="fas fa-clock"></i> <span>${s.shift_type || '—'}</span>
                            <span style="color:var(--border-color);">|</span>
                            <i class="fas fa-hospital-alt"></i> <span>${s.ward_name || '—'}</span>
                            ${s.floor_name ? '<span style="color:var(--border-color);">|</span><i class="fas fa-layer-group"></i> <span>' + s.floor_name + '</span>' : ''}
                        </div>
                    </div>
                    <span class="status-badge ${statusClass(s.status)}">
                        <i class="fas fa-circle" style="font-size:8px;"></i> ${s.status || 'Scheduled'}
                    </span>
                </div>
            `).join('');
        }

        loadShiftData();
    </script>
</body>
</html>