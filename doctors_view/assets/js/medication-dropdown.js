/**
 * Enhanced Medication Dropdown System
 * Displays generic names with brand name selection
 */

// Global medication database cache
let medicationsDatabase = [];
let medicationsLoaded = false;

// Load medications on page load
document.addEventListener('DOMContentLoaded', async function () {
    await loadMedicationsDatabase();
});

/**
 * Load medications from API
 */
async function loadMedicationsDatabase() {
    try {
        const response = await fetch('api/get_medications.php');
        const result = await response.json();

        if (result.success) {
            medicationsDatabase = result.data;
            medicationsLoaded = true;
            console.log(`✅ Loaded ${medicationsDatabase.length} medications`);
        } else {
            console.error('Failed to load medications database');
            showToast('Warning: Medication database not loaded', 'warning');
        }
    } catch (error) {
        console.error('Error loading medications:', error);
        showToast('Warning: Could not load medication suggestions', 'warning');
    }
}

/**
 * Enhanced addMedicationRow with smart dropdown
 */
function addMedicationRowEnhanced(medData = null) {
    const tbody = document.getElementById('medication-list-body');
    const row = document.createElement('tr');
    row.className = 'elite-med-row';

    // Generate unique ID for this row
    const rowId = 'med-row-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

    // Parse duration if medData exists
    let durationNum = (medData && medData.duration) ? parseInt(medData.duration) : '';
    let durationUnit = (medData && medData.duration && medData.duration.includes('Week')) ? 'Weeks' :
        (medData && medData.duration && medData.duration.includes('Month')) ? 'Months' : 'Days';

    row.innerHTML = `
        <td style="padding-left: 0;">
            <div class="medication-dropdown-wrapper" style="position: relative;">
                <input 
                    type="text" 
                    class="med-name medication-search-input" 
                    id="${rowId}-search"
                    placeholder="Search Generic/Brand..." 
                    value="${(medData && medData.name) ? medData.name : ''}"
                    autocomplete="off"
                    oninput="handleMedicationSearch(this)"
                    onfocus="handleMedicationSearch(this)"
                    style="border: none; background: transparent; font-weight: 700; color: #0EA5E9; padding: 12px 10px; width: 100%;"
                >
                <input type="hidden" class="med-generic-name" value="${(medData && medData.generic_name) ? medData.generic_name : ''}">
                <input type="hidden" class="med-brand-name" value="${(medData && medData.brand_name) ? medData.brand_name : ''}">
                <div class="medication-dropdown-list" id="${rowId}-dropdown" style="display: none; z-index: 10001;">
                    <!-- Dropdown populated dynamically -->
                </div>
            </div>
        </td>
        <td>
            <input type="text" class="med-dosage" placeholder="500mg" value="${(medData && medData.dosage) ? medData.dosage : ''}" 
                   style="border: 2px solid #F1F5F9; border-radius: 8px; padding: 6px 10px; width: 100%;">
        </td>
        <td>
            <select class="med-timing" style="border: 2px solid #F1F5F9; border-radius: 8px; padding: 6px 4px; width: 100%; cursor: pointer;">
                <option value="After Food" ${(medData && medData.timing === 'After Food') ? 'selected' : ''}>After Food</option>
                <option value="Before Food" ${(medData && medData.timing === 'Before Food') ? 'selected' : ''}>Before Food</option>
                <option value="With Food" ${(medData && medData.timing === 'With Food') ? 'selected' : ''}>With Food</option>
                <option value="Empty Stomach" ${(medData && medData.timing === 'Empty Stomach') ? 'selected' : ''}>Empty Stomach</option>
            </select>
        </td>
        <td>
            <select class="med-frequency" onchange="calculateRowQty(this)" style="border: 2px solid #F1F5F9; border-radius: 8px; padding: 6px 4px; width: 100%; cursor: pointer; font-weight: 700; font-size: 0.8rem; color: #1e293b;">
                <option value="1-0-0" ${(medData && medData.frequency === '1-0-0') ? 'selected' : ''}>1 — 0 — 0 (Morning)</option>
                <option value="0-1-0" ${(medData && medData.frequency === '0-1-0') ? 'selected' : ''}>0 — 1 — 0 (Afternoon)</option>
                <option value="0-0-1" ${(medData && medData.frequency === '0-0-1') ? 'selected' : ''}>0 — 0 — 1 (Night)</option>
                <option value="1-0-1" ${(medData && (medData.frequency === '1-0-1' || medData.frequency === 'BD')) ? 'selected' : ''}>1 — 0 — 1 (Morning & Night)</option>
                <option value="1-1-1" ${(medData && (medData.frequency === '1-1-1' || medData.frequency === 'TDS')) ? 'selected' : ''}>1 — 1 — 1 (Morn, Aft, Night)</option>
                <option value="1-1-1-1" ${(medData && (medData.frequency === '1-1-1-1' || medData.frequency === 'QID')) ? 'selected' : ''}>1—1—1—1 (Every 6 hrs)</option>
                <option value="0.5" ${(medData && (medData.frequency === '0.5' || medData.frequency === 'SOS')) ? 'selected' : ''}>SOS (When Needed)</option>
            </select>
        </td>
        <td>
            <div class="elite-duration-stepper" style="display: flex; align-items: center; background: #F8FAFC; border: 2px solid #F1F5F9; border-radius: 10px; padding: 2px;">
                <button type="button" onclick="updateStepperValue(this, -1)" style="width: 24px; height: 24px; border: none; background: white; border-radius: 6px; cursor: pointer; color: #64748B;"><i class="fas fa-minus" style="font-size: 0.6rem;"></i></button>
                <input type="number" class="med-duration-num" style="width: 35px; border: none; background: transparent; text-align: center; font-weight: 800; color: #1E293B;" placeholder="5" value="${durationNum}" oninput="calculateRowQty(this)">
                <button type="button" onclick="updateStepperValue(this, 1)" style="width: 24px; height: 24px; border: none; background: white; border-radius: 6px; cursor: pointer; color: #64748B;"><i class="fas fa-plus" style="font-size: 0.6rem;"></i></button>
                <select class="med-duration-unit" style="border: none; background: transparent; font-size: 0.7rem; font-weight: 800; color: #0EA5E9; padding: 0 4px; cursor: pointer; min-width: 55px;" onchange="calculateRowQty(this)">
                    <option value="Days" ${durationUnit === 'Days' ? 'selected' : ''}>Days</option>
                    <option value="Weeks" ${durationUnit === 'Weeks' ? 'selected' : ''}>Weeks</option>
                    <option value="Months" ${durationUnit === 'Months' ? 'selected' : ''}>Months</option>
                </select>
            </div>
        </td>
        <td>
            <div class="elite-qty-box" style="display: flex; align-items: center; background: #F0FDF4; border: 2px solid #DCFCE7; border-radius: 10px; padding: 2px 6px;">
                <input type="number" class="med-qty" placeholder="0" value="${(medData && medData.qty) ? medData.qty : ''}" 
                       style="width: 35px; border: none; background: transparent; color: #166534; font-weight: 900; font-size: 0.95rem; text-align: center;">
                <select class="med-qty-unit" style="border: none; background: transparent; font-size: 0.6rem; font-weight: 800; color: #166534; text-transform: uppercase; cursor: pointer; outline: none;">
                    <option value="Tabs">Tabs</option>
                    <option value="ml">ml</option>
                    <option value="Tube">Tube</option>
                    <option value="Bottle">Bottle</option>
                </select>
            </div>
        </td>
        <td style="padding-right: 0; text-align: center;">
            <button type="button" class="elite-remove-btn" onclick="removeMedicationRow(this)" title="Remove Row" 
                    style="width: 32px; height: 32px; border-radius: 50%; border: none; background: #FEE2E2; color: #EF4444; cursor: pointer; transition: 0.2s;">
                <i class="fas fa-trash-can" style="font-size: 0.8rem;"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);

    // Scroll table to bottom if list is long
    const tableWrapper = tbody.closest('.elite-table-wrapper');
    if (tableWrapper) tableWrapper.scrollTop = tableWrapper.scrollHeight;
}

/**
 * Helper for Stepper Buttons
 */
function updateStepperValue(btn, delta) {
    const container = btn.closest('.elite-duration-stepper');
    const input = container.querySelector('.med-duration-num');
    let val = parseInt(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val;
    calculateRowQty(input);
}
window.updateStepperValue = updateStepperValue;

/**
 * Auto-calculate Quantity based on Frequency and Duration
 */
function calculateRowQty(element) {
    const row = element.closest('tr');
    const frequencyInput = row.querySelector('.med-frequency');
    const durationNumInput = row.querySelector('.med-duration-num');
    const durationUnitSelect = row.querySelector('.med-duration-unit');
    const qtyInput = row.querySelector('.med-qty');

    const frequency = frequencyInput.value.trim();
    const durationNum = parseInt(durationNumInput.value) || 0;
    const durationUnit = durationUnitSelect.value;

    if (!frequency || durationNum <= 0) return;

    // Calculate doses per day
    let dosesPerDay = 0;
    if (frequency.includes('-')) {
        // Pattern like 1-0-1
        const parts = frequency.split('-');
        parts.forEach(part => {
            const num = parseFloat(part.trim()) || 0;
            dosesPerDay += num;
        });
    } else {
        // Direct number like 0.5 for SOS
        dosesPerDay = parseFloat(frequency) || 0;
    }

    // Convert duration to days
    let totalDays = durationNum;
    if (durationUnit === 'Weeks') totalDays *= 7;
    else if (durationUnit === 'Months') totalDays *= 30;

    // Calculate total quantity
    const totalQty = Math.ceil(dosesPerDay * totalDays);

    // Update quantity field
    if (totalQty > 0) {
        qtyInput.value = totalQty;

        // Add a subtle animation to show it changed
        const qtyBox = row.querySelector('.elite-qty-box');
        if (qtyBox) {
            qtyBox.style.transform = 'scale(1.05)';
            qtyBox.style.borderColor = '#10B981';
            setTimeout(() => {
                qtyBox.style.transform = 'scale(1)';
                qtyBox.style.borderColor = '#DCFCE7';
            }, 300);
        }
    }
}

/**
 * Handle medication search and dropdown display
 */
function handleMedicationSearch(input) {
    if (!medicationsLoaded) {
        console.warn('Medications database not loaded yet');
        return;
    }

    const searchTerm = input.value.trim().toLowerCase();
    const rowId = input.id.replace('-search', '');
    const dropdown = document.getElementById(rowId + '-dropdown');

    if (!dropdown) return;

    // Clear dropdown if search is empty
    if (searchTerm.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
    }

    // Filter medications by generic name
    const matches = medicationsDatabase.filter(med =>
        med.generic_name.toLowerCase().includes(searchTerm)
    );

    if (matches.length === 0) {
        dropdown.innerHTML = '<div class="dropdown-no-results">No medications found. You can still type manually.</div>';
        dropdown.style.display = 'block';
        return;
    }

    // Build dropdown HTML
    let dropdownHTML = '';
    matches.slice(0, 10).forEach(med => {
        dropdownHTML += `
            <div class="medication-dropdown-group">
                <div class="generic-name-header">
                    <i class="fas fa-capsules"></i>
                    <strong>${med.generic_name}</strong>
                    <span class="category-badge">${med.category}</span>
                </div>
                <div class="brand-list">
                    ${med.brands.map(brand => `
                        <div class="brand-item" onclick="selectMedication('${rowId}', '${med.generic_name}', '${brand}', '${med.common_dosages[0] || ''}')">
                            <i class="fas fa-pills"></i>
                            <span class="brand-name">${brand}</span>
                            ${med.common_dosages.length > 0 ? `<span class="dosage-hint">${med.common_dosages.join(', ')}</span>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    });

    dropdown.innerHTML = dropdownHTML;
    dropdown.style.display = 'block';
}

/**
 * Select medication from dropdown
 */
function selectMedication(rowId, genericName, brandName, suggestedDosage) {
    const searchInput = document.getElementById(rowId + '-search');
    const dropdown = document.getElementById(rowId + '-dropdown');
    const row = searchInput.closest('tr');

    // Set the display value (brand name)
    searchInput.value = `${brandName} (${genericName})`;

    // Store generic and brand names in hidden fields
    row.querySelector('.med-generic-name').value = genericName;
    row.querySelector('.med-brand-name').value = brandName;

    // Auto-fill dosage if available and field is empty
    const dosageInput = row.querySelector('.med-dosage');
    if (suggestedDosage && !dosageInput.value) {
        dosageInput.value = suggestedDosage;
    }

    // Auto-detect unit based on medication name (e.g., Syrup -> ml/Bottle)
    const qtyUnitSelect = row.querySelector('.med-qty-unit');
    if (qtyUnitSelect) {
        const fullText = (brandName + ' ' + genericName).toLowerCase();
        if (fullText.includes('syr') || fullText.includes('liq') || fullText.includes('susp')) {
            qtyUnitSelect.value = 'ml';
        } else if (fullText.includes('cream') || fullText.includes('oint') || fullText.includes('gel')) {
            qtyUnitSelect.value = 'Tube';
        } else {
            qtyUnitSelect.value = 'Tabs';
        }
    }

    // Hide dropdown
    dropdown.style.display = 'none';

    // Focus on next field (dosage)
    dosageInput.focus();
}

/**
 * Close dropdown when clicking outside
 */
document.addEventListener('click', function (event) {
    if (!event.target.closest('.medication-dropdown-wrapper')) {
        document.querySelectorAll('.medication-dropdown-list').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});

// Export for use in main consultation.js
window.addMedicationRowEnhanced = addMedicationRowEnhanced;
window.handleMedicationSearch = handleMedicationSearch;
window.selectMedication = selectMedication;
