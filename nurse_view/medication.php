<?php
session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Administration - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tom Select for advanced dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #1f6b4a;
            --primary-dark: #144d34;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #e11d48;
            --info: #0ea5e9;
            --light: #F8F9FA;
            --dark: #1e293b;
        }

        body {
            background: #F5F7FA;
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
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 24px;
            color: var(--dark);
            font-weight: 700;
        }

        .tab-navigation {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 1px solid #DEE2E6;
        }

        .tab-btn {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #6C757D;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .med-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: 20px;
        }

        .med-time {
            background: #E3F2FD;
            color: var(--primary);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            width: 80px;
        }

        .med-time .time {
            display: block;
            font-weight: 700;
            font-size: 16px;
        }

        .med-time .date {
            font-size: 11px;
            opacity: 0.8;
        }

        .med-info h4 {
            font-size: 17px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .med-info p {
            font-size: 13px;
            color: #6C757D;
        }

        .patient-tag {
            background: #F1F3F5;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #495057;
        }

        .btn-give {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-give:hover {
            background: #218838;
            transform: scale(1.05);
        }

        .med-overdue {
            border-left: 5px solid var(--danger);
        }

        .overdue-tag {
            color: var(--danger);
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .loading, .empty-state {
            text-align: center;
            padding: 80px;
            color: #6C757D;
            background: white;
            border-radius: 12px;
        }

        .return-form-container {
            background: #ffffff;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
            display: none;
            border: 1px solid #f1f5f9;
        }
        .return-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        .return-header i {
            font-size: 24px;
            color: var(--primary);
            background: rgba(31, 107, 74, 0.1);
            padding: 12px;
            border-radius: 12px;
        }
        .return-header h3 {
            color: #1e293b;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-group {
            margin-bottom: 5px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            background-color: #f8fafc;
            transition: all 0.2s;
            color: #334155;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 6px -1px rgba(31, 107, 74, 0.3), 0 2px 4px -1px rgba(31, 107, 74, 0.15);
            margin-top: 15px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(31, 107, 74, 0.4), 0 4px 6px -2px rgba(31, 107, 74, 0.2);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        /* Override Tom Select CSS to match our modern theme */
        .ts-control {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.95rem;
            background-color: #f8fafc;
            color: #334155;
            transition: all 0.2s;
            box-shadow: none;
        }
        .ts-control.focus {
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(31, 107, 74, 0.1);
        }
        .ts-dropdown {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            font-size: 0.95rem;
            color: #334155;
        }
        .ts-dropdown .active {
            background-color: rgba(31, 107, 74, 0.1) !important;
            color: var(--primary-dark) !important;
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
                    <div class="page-header">
                        <h1>Medication Administration</h1>
                        <button class="btn-give" style="background: var(--primary);">
                            <i class="fas fa-plus"></i> New Admin
                        </button>
                    </div>

                    <div class="tab-navigation">
                        <div class="tab-btn active" data-target="medicationList" onclick="switchTab('medicationList', this)">Scheduled Today</div>
                        <div class="tab-btn" data-target="medicationList" onclick="switchTab('medicationList', this)">Overdue</div>
                        <div class="tab-btn" data-target="medicineReturnContainer" onclick="switchTab('medicineReturnContainer', this)">Medicine Return</div>
                    </div>

                    <div id="medicationList" class="tab-content-area">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p>Loading medications schedule...</p>
                        </div>
                    </div>

                    <div id="medicineReturnContainer" class="return-form-container tab-content-area">
                        <div class="return-header">
                            <i class="fas fa-undo-alt"></i>
                            <div>
                                <h3>Return Extra Medicine</h3>
                                <p style="color: #64748b; font-size: 0.85rem; margin-top: 4px;">Log unused IPD medications to automatically return them to the pharmacy inventory.</p>
                            </div>
                        </div>

                        <form id="medicineReturnForm" onsubmit="submitReturn(event)">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user-injured" style="color: #94a3b8; margin-right: 5px;"></i> Select Patient</label>
                                    <select class="form-control" id="return_patient_id" required>
                                        <option value="">-- Loading Patients --</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-pills" style="color: #94a3b8; margin-right: 5px;"></i> Select Medicine</label>
                                    <select class="form-control" id="return_product_id" required>
                                        <option value="">-- Loading Medicines --</option>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label"><i class="fas fa-sort-numeric-up-alt" style="color: #94a3b8; margin-right: 5px;"></i> Return Quantity</label>
                                    <input type="number" class="form-control" id="return_qty" min="1" required placeholder="Enter exact quantity to return (e.g. 5)">
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label"><i class="fas fa-comment-medical" style="color: #94a3b8; margin-right: 5px;"></i> Reason for Return</label>
                                    <textarea class="form-control" id="return_reason" rows="3" required placeholder="e.g. Medicine stopped by doctor, patient discharged, etc."></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <button type="submit" class="btn-submit" id="submitReturnBtn">
                                        <i class="fas fa-paper-plane"></i> Submit Return to Pharmacy
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadMedications() {
            try {
                // Get patient from URL or SessionStorage
                const urlParams = new URLSearchParams(window.location.search);
                let targetId = urlParams.get('patient_id') || sessionStorage.getItem('selected_patient_id');
                
                // Clear URL param if it exists to keep URL clean
                if (urlParams.has('patient_id')) {
                    sessionStorage.setItem('selected_patient_id', urlParams.get('patient_id'));
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                const response = await fetch('api/dashboard.php');
                const result = await response.json();

                if (result.success) {
                    const overdue = result.data.overdue_medications;
                    const patients = result.data.assigned_patients;
                    const container = document.getElementById('medicationList');
                    
                    // Populate Patient Dropdown for Return Form
                    const patientSelect = document.getElementById('return_patient_id');
                    if (patients && patients.length > 0) {
                        patientSelect.innerHTML = '<option value="">-- Select Patient --</option>' + 
                            patients.map(p => `<option value="${p.patient_id}">${p.first_name} ${p.last_name} (Bed: ${p.room_number}/${p.bed_number})</option>`).join('');
                    } else {
                        patientSelect.innerHTML = '<option value="">No patients assigned</option>';
                    }
                    
                    // Initialize Tom Select for Patient Dropdown
                    new TomSelect("#return_patient_id", {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Search for a patient..."
                    });
                    
                    if (overdue && overdue.length > 0) {
                        container.innerHTML = overdue.map(m => `
                            <div class="med-card med-overdue">
                                <div class="med-time">
                                    <span class="time">${new Date(m.scheduled_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                    <span class="date">OVERDUE</span>
                                </div>
                                <div class="med-info">
                                    <h4>${m.medicine_name}</h4>
                                    <p>${m.dosage} | ${m.route} | ${m.frequency}</p>
                                    <div class="overdue-tag">
                                        <i class="fas fa-exclamation-circle"></i> Overdue by ${m.minutes_overdue} mins
                                    </div>
                                </div>
                                <div>
                                    <span class="patient-tag">
                                        <i class="fas fa-user"></i> ${m.patient_name}
                                    </span>
                                </div>
                                <button class="btn-give">GIVE NOW</button>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 64px; margin-bottom: 20px;"></i>
                                <h3>All Caught Up!</h3>
                                <p>No medications are currently pending for administration.</p>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function loadPharmacyProducts() {
            try {
                const response = await fetch('api/medicine_return.php');
                const result = await response.json();
                
                if (result.success) {
                    const productSelect = document.getElementById('return_product_id');
                    if (result.data && result.data.length > 0) {
                        productSelect.innerHTML = '<option value="">-- Select Medicine --</option>' + 
                            result.data.map(p => `<option value="${p.product_id}">${p.product_name} (Batch: ${p.batch_number || 'N/A'})</option>`).join('');
                    } else {
                        productSelect.innerHTML = '<option value="">No medicines found in inventory</option>';
                    }
                    
                    // Initialize Tom Select for Medicine Dropdown
                    new TomSelect("#return_product_id", {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Search for a medicine..."
                    });
                }
            } catch (error) {
                console.error('Failed to load products:', error);
            }
        }

        async function submitReturn(event) {
            event.preventDefault();
            const btn = document.getElementById('submitReturnBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            btn.disabled = true;

            const payload = {
                patient_id: document.getElementById('return_patient_id').value,
                product_id: document.getElementById('return_product_id').value,
                qty: document.getElementById('return_qty').value,
                reason: document.getElementById('return_reason').value
            };

            try {
                const response = await fetch('api/medicine_return.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success) {
                    alert('Success: ' + result.message + '\nReturn No: ' + result.data.return_no);
                    document.getElementById('medicineReturnForm').reset();
                    // Optionally switch back to main tab
                    document.querySelector('.tab-navigation .tab-btn').click();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Submit Error:', error);
                alert('An error occurred while submitting the return.');
            } finally {
                btn.innerHTML = '<i class="fas fa-undo"></i> Submit Return to Pharmacy';
                btn.disabled = false;
            }
        }

        function switchTab(targetId, btnElement) {
            // Update active button
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            // Hide all tab content
            document.querySelectorAll('.tab-content-area').forEach(div => div.style.display = 'none');

            // Show target
            document.getElementById(targetId).style.display = targetId === 'medicationList' ? 'block' : 'block';
        }

        loadMedications();
        loadPharmacyProducts();
    </script>
</body>
</html>
