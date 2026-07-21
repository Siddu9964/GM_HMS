<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        :root {
            --primary: #4A90E2; --primary-dark: #357ABD;
            --success: #28A745; --warning: #FFC107;
            --danger: #DC3545; --info: #17A2B8;
            --light: #F8F9FA; --dark: #343A40;
        }
        body { background: #F5F7FA; min-height: 100vh; display: flex; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; }
        .main-content { flex: 1; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }

        .page-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 25px;
        }
        .page-header h1 { font-size: 24px; color: var(--dark); font-weight: 700; }

        /* Shift Banner */
        .shift-banner {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            color: white; border-radius: 16px; padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 0 8px 24px rgba(74,144,226,0.3);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
        }
        .shift-banner-left h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .shift-banner-left p { opacity: 0.85; font-size: 14px; }
        .shift-type-badge {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            color: white; padding: 10px 22px;
            border-radius: 50px; font-weight: 700;
            font-size: 16px; letter-spacing: 0.5px;
        }

        /* Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }
        .info-card {
            background: white; border-radius: 12px;
            padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-left: 4px solid var(--primary);
            transition: transform 0.2s;
        }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.1); }
        .info-card.green  { border-left-color: var(--success); }
        .info-card.orange { border-left-color: var(--warning); }
        .info-card.red    { border-left-color: var(--danger); }
        .info-card.teal   { border-left-color: var(--info); }
        .info-card.purple { border-left-color: #7C3AED; }

        .info-card-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white; margin-bottom: 12px;
            background: var(--primary);
        }
        .info-card.green  .info-card-icon { background: var(--success); }
        .info-card.orange .info-card-icon { background: var(--warning); }
        .info-card.red    .info-card-icon { background: var(--danger); }
        .info-card.teal   .info-card-icon { background: var(--info); }
        .info-card.purple .info-card-icon { background: #7C3AED; }

        .info-card-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 6px;
        }
        .info-card-value { font-size: 17px; font-weight: 700; color: var(--dark); word-break: break-word; }
        .info-card-sub   { font-size: 12px; color: #64748b; margin-top: 4px; }

        /* Beds */
        .beds-card {
            background: white; border-radius: 12px;
            padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 24px;
        }
        .beds-card h3 {
            font-size: 16px; font-weight: 700; color: var(--dark);
            margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
        }
        .bed-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .bed-chip {
            background: #EFF6FF; color: #1D4ED8;
            border: 1px solid #BFDBFE; border-radius: 8px;
            padding: 6px 14px; font-size: 13px; font-weight: 600;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 700; text-transform: uppercase;
        }
        .status-active    { background: #D1FAE5; color: #065F46; }
        .status-scheduled { background: #FEF3C7; color: #92400E; }
        .status-completed { background: #E5E7EB; color: #374151; }

        /* Upcoming Shifts */
        .section-card {
            background: white; border-radius: 12px;
            padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            margin-bottom: 24px;
        }
        .section-card h3 {
            font-size: 16px; font-weight: 700; color: var(--dark);
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .shift-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #F1F5F9;
            flex-wrap: wrap; gap: 8px;
        }
        .shift-row:last-child { border-bottom: none; }
        .shift-row-left { display: flex; flex-direction: column; gap: 2px; }
        .shift-row-date { font-weight: 700; color: var(--dark); font-size: 14px; }
        .shift-row-detail { font-size: 12px; color: #64748b; }

        .loading, .empty-state { text-align: center; padding: 50px; color: #6C757D; }
        .empty-state i { font-size: 56px; margin-bottom: 16px; color: #DEE2E6; display: block; }
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
                        <h1><i class="fas fa-clock" style="color:var(--primary);margin-right:10px;"></i>My Shift</h1>
                        <div id="shiftStatusBadge"></div>
                    </div>

                    <!-- Current Shift -->
                    <div id="shiftData">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p style="margin-top:12px;">Loading shift information...</p>
                        </div>
                    </div>

                    <!-- Upcoming Shifts -->
                    <div class="section-card">
                        <h3><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Upcoming Shifts</h3>
                        <div id="upcomingShifts">
                            <p style="color:#94a3b8;font-size:14px;">Loading schedule...</p>
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

                    // Status badge in header
                    document.getElementById('shiftStatusBadge').innerHTML = `
                        <span class="status-badge ${statusClass(shift.status)}">
                            <i class="fas fa-circle" style="font-size:8px;"></i> ${shift.status || 'Scheduled'}
                        </span>`;

                    // Beds chips
                    let bedsHtml = '<span style="color:#94a3b8;font-size:13px;">No beds assigned</span>';
                    if (shift.assigned_beds) {
                        const beds = shift.assigned_beds.split(',').map(b => b.trim()).filter(Boolean);
                        bedsHtml = beds.map(b =>
                            `<span class="bed-chip"><i class="fas fa-bed" style="margin-right:5px;"></i>${b}</span>`
                        ).join('');
                    }

                    shiftContainer.innerHTML = `
                        <!-- Banner -->
                        <div class="shift-banner">
                            <div class="shift-banner-left">
                                <h2><i class="fas fa-user-nurse" style="margin-right:8px;"></i>Current Assignment</h2>
                                <p>
                                    ${formatDate(shift.shift_date_from)} &nbsp;→&nbsp; ${formatDate(shift.shift_date_to)}
                                    &nbsp;·&nbsp; ${days} day${days !== 1 ? 's' : ''}
                                </p>
                            </div>
                            <div class="shift-type-badge">
                                <i class="fas fa-sun" style="margin-right:6px;"></i>${shift.shift_type || '—'}
                            </div>
                        </div>

                        <!-- Info Cards -->
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="info-card-label">Start Date</div>
                                <div class="info-card-value">${formatDate(shift.shift_date_from)}</div>
                                <div class="info-card-sub">Assignment begins</div>
                            </div>
                            <div class="info-card green">
                                <div class="info-card-icon"><i class="fas fa-calendar-times"></i></div>
                                <div class="info-card-label">End Date</div>
                                <div class="info-card-value">${formatDate(shift.shift_date_to)}</div>
                                <div class="info-card-sub">Assignment ends</div>
                            </div>
                            <div class="info-card orange">
                                <div class="info-card-icon"><i class="fas fa-clock"></i></div>
                                <div class="info-card-label">Shift Type</div>
                                <div class="info-card-value">${shift.shift_type || '—'}</div>
                                <div class="info-card-sub">${shiftTime(shift.shift_type)}</div>
                            </div>
                            <div class="info-card teal">
                                <div class="info-card-icon"><i class="fas fa-stethoscope"></i></div>
                                <div class="info-card-label">Work Area</div>
                                <div class="info-card-value">${shift.work_area || 'Not Assigned'}</div>
                                <div class="info-card-sub">Specialty area</div>
                            </div>
                            <div class="info-card purple">
                                <div class="info-card-icon"><i class="fas fa-hospital-alt"></i></div>
                                <div class="info-card-label">Ward</div>
                                <div class="info-card-value">${shift.ward_name || '—'}</div>
                                <div class="info-card-sub">${shift.floor_name ? 'Floor: ' + shift.floor_name : 'Floor not set'}</div>
                            </div>
                            <div class="info-card red">
                                <div class="info-card-icon"><i class="fas fa-layer-group"></i></div>
                                <div class="info-card-label">Floor / Category</div>
                                <div class="info-card-value">${shift.floor_name || '—'}</div>
                                <div class="info-card-sub">Ward category</div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon"><i class="fas fa-calendar-day"></i></div>
                                <div class="info-card-label">Duration</div>
                                <div class="info-card-value">${days} Day${days !== 1 ? 's' : ''}</div>
                                <div class="info-card-sub">Total assignment span</div>
                            </div>
                            <div class="info-card green">
                                <div class="info-card-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="info-card-label">Status</div>
                                <div class="info-card-value">${shift.status || 'Scheduled'}</div>
                                <div class="info-card-sub">Assignment status</div>
                            </div>
                        </div>

                        <!-- Assigned Beds -->
                        <div class="beds-card">
                            <h3><i class="fas fa-bed" style="color:var(--primary);"></i> Assigned Beds</h3>
                            <div class="bed-chips">${bedsHtml}</div>
                        </div>
                    `;
                } else {
                    document.getElementById('shiftStatusBadge').innerHTML = '';
                    shiftContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Active Shift Today</h3>
                            <p>You don't have a shift assignment covering today's date.</p>
                        </div>`;
                }

                renderUpcomingShifts(result.data.upcoming_shifts);

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('shiftData').innerHTML =
                    '<p style="color:var(--danger);text-align:center;padding:30px;">Error loading shift data.</p>';
            }
        }

        function renderUpcomingShifts(shifts) {
            const container = document.getElementById('upcomingShifts');
            if (!shifts || shifts.length === 0) {
                container.innerHTML = '<p style="color:#94a3b8;font-size:14px;">No upcoming shifts scheduled.</p>';
                return;
            }
            container.innerHTML = shifts.map(s => `
                <div class="shift-row">
                    <div class="shift-row-left">
                        <div class="shift-row-date">
                            ${formatDate(s.shift_date_from)} &nbsp;→&nbsp; ${formatDate(s.shift_date_to)}
                        </div>
                        <div class="shift-row-detail">
                            <i class="fas fa-clock" style="margin-right:4px;color:var(--primary);"></i>${s.shift_type || '—'}
                            &nbsp;·&nbsp;
                            <i class="fas fa-hospital-alt" style="margin-right:4px;color:var(--info);"></i>${s.ward_name || '—'}
                            ${s.work_area ? '&nbsp;·&nbsp;<i class="fas fa-stethoscope" style="margin-right:4px;"></i>' + s.work_area : ''}
                            ${s.floor_name ? '&nbsp;·&nbsp;<i class="fas fa-layer-group" style="margin-right:4px;"></i>' + s.floor_name : ''}
                        </div>
                    </div>
                    <span class="status-badge ${statusClass(s.status)}">${s.status || 'Scheduled'}</span>
                </div>
            `).join('');
        }

        loadShiftData();
    </script>
</body>
</html>