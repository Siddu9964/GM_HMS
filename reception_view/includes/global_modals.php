<!-- GLOBAL MODALS (Auto-extracted) -->
    <!-- Patient Form Modal -->
    <div id="patientModal" class="ref-modal-overlay hidden" onclick="closeModalOnBackdrop(event)">
        <div class="ref-modal-card" onclick="event.stopPropagation()">
            <div class="ref-modal-header">
                <h2 id="modalTitle">Add New Patient</h2>
                <button onclick="closePatientModal()" class="ref-modal-close" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="ref-modal-body">
                <form id="patientForm">
                    <input type="hidden" id="editPatientId" name="patient_id">

                    <div class="ref-form-grid">
                        <!-- Section 1: Basic Information -->
                        <div class="ref-section-title">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Patient ID</label>
                            <input type="text" id="displayPatientId" class="readonly-mint" value="PID-AUTO" readonly>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Title</label>
                            <select name="title">
                                <option value="">Select</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr">Dr</option>
                                <option value="Master">Mast</option>
                                <option value="B/O">B/O</option>
                                <option value="Baby Boy">Baby Boy</option>
                                <option value="Baby Girl">Baby Girl</option>
                                <option value="NA">N/A</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" placeholder="First Name" required>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Last Name">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Phone <span class="req">*</span></label>
                            <input type="tel" id="patientPhone" name="phone" required placeholder="Phone Number" maxlength="10">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Email Address">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Aadhar Number</label>
                            <input type="text" id="patientAadhar" name="aadhar" placeholder="XXXX XXXX XXXX" maxlength="14">
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Gender</label>
                            <div class="radio-group" style="display:flex; gap:10px; margin-top:2px;">
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Male" id="male">
                                    <label for="male" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-mars"></i> Male</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Female" id="female">
                                    <label for="female" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-venus"></i> Female</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" name="sex" value="Other" id="other">
                                    <label for="other" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem;"><i class="fas fa-genderless"></i> Other</label>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Medical Details -->
                        <div class="ref-section-title">
                            <i class="fas fa-heartbeat"></i> Medical Details
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Birth Date</label>
                            <input type="date" name="birth_date" id="birthDate">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Age</label>
                            <input type="number" name="age" id="age" min="0" max="150" placeholder="Years">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Blood Group</label>
                            <select name="blood_group">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>



                        <!-- Section 3: Location & Address -->
                        <div class="ref-section-title">
                            <i class="fas fa-map-marked-alt"></i> Location & Address
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Pincode</label>
                            <div style="position:relative;">
                                <input type="text" name="pincode" id="patientPincode" placeholder="6-digit pincode" maxlength="6">
                                <span id="pincodeStatus" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:14px; pointer-events:none;"></span>
                            </div>
                            <span id="pincodeMessage" style="font-size:11px; margin-top:2px; display:block; color:#64748b;"></span>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Country</label>
                            <input type="text" name="country" id="patientCountry" placeholder="Country">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>State</label>
                            <input type="text" name="state" id="patientState" placeholder="State">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>District</label>
                            <input type="text" name="district" id="patientDistrict" list="districtDatalist" placeholder="District" autocomplete="off">
                            <datalist id="districtDatalist"></datalist>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Division</label>
                            <input type="text" name="division" id="patientDivision" placeholder="Division">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Region</label>
                            <input type="text" name="region" id="patientRegion" placeholder="Region">
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>City / Taluk</label>
                            <div style="position: relative;">
                                <input type="text" name="city" id="patientCity" list="cityDatalist" placeholder="City" autocomplete="off" style="padding-right: 24px !important;">
                                <i class="fas fa-chevron-down" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #144d34; font-size: 0.75rem;"></i>
                            </div>
                            <datalist id="cityDatalist"></datalist>
                        </div>

                        <div class="ref-field ref-col-1" style="position:relative;">
                            <label>Area / Post Office</label>
                            <input type="hidden" name="area" id="patientAreaValue">
                            <div style="position:relative;">
                                <input type="text" id="patientAreaSearch" placeholder="Search Area" autocomplete="off" style="padding-right: 28px !important;">
                                <i class="fas fa-chevron-down" id="areaDropdownArrow" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #144d34; font-size: 0.75rem;"></i>
                                <span id="patientAreaClear" onclick="window._clearAreaSearch()" title="Clear" style="display:none; position:absolute; right:24px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:12px; color:#9ca3af;">✕</span>
                            </div>
                            <div id="patientAreaDropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1.5px solid #144D34; border-radius:0 0 8px 8px; max-height:160px; overflow-y:auto; z-index:999; box-shadow:0 8px 20px rgba(0,0,0,0.15);"></div>
                        </div>

                        <div class="ref-field ref-col-4">
                            <label>Full Address</label>
                            <textarea name="address" rows="2" placeholder="Full residential address..."></textarea>
                        </div>

                        <!-- Section 4: Referral Information -->
                        <div class="ref-section-title">
                            <i class="fas fa-handshake"></i> Referral Information
                        </div>

                        <!-- Referred By Dropdown -->
                        <div class="ref-field ref-col-2">
                            <label>Referred By <span class="req">*</span></label>
                            <select name="referral_type" id="referred_by_select" onchange="toggleReferralSource()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #d1d5db; background-color: #f9fafb;" required>
                                <option value="Doctor" selected>Doctor</option>
                                <option value="Self">walk-in</option>
                                <option value="Staff">Staff</option>
                                <option value="Online">Online</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>

                        <!-- Referral Name -->
                        <div class="ref-field ref-col-2" id="doctorSearchDiv">
                            <label>Referral Name</label>
                            
                            <!-- For Doctor (Advanced Search) -->
                            <div id="doctorSelectWrapper">
                                <select id="existingDoctorSelect" style="width: 100%;">
                                    <option value="">Search existing or enter new...</option>
                                </select>
                            </div>
                            
                            <!-- For Staff/Online/Others (Plain Text) -->
                            <div id="plainReferralWrapper" style="display: none;">
                                <input type="text" id="plainReferralName" placeholder="Enter referral name" oninput="document.getElementById('referral_name_input').value = this.value">
                            </div>

                            <input type="hidden" name="referral_name" id="referral_name_input" value="">
                            <input type="hidden" name="is_new_doctor" id="is_new_doctor" value="0">
                        </div>

                        <!-- Doctor Extra Details -->
                        <div id="doctorExtraDetailsDiv" class="ref-col-4" style="display: none; padding-top: 12px; margin-bottom: 8px;">
                            <h4 style="margin-top: 0; margin-bottom: 16px; color: #1e293b; font-size: 14px;"><i class="fas fa-user-md" style="color: #1f6b4a;"></i> Doctor Details</h4>
                            <div class="ref-form-grid">
                                <div class="ref-field ref-col-2" style="margin: 0;">
                                    <label>Phone Number</label>
                                    <input type="tel" name="ref_doctor_phone" id="ref_doctor_phone" placeholder="Phone Number">
                                </div>
                                <div class="ref-field ref-col-2" style="margin: 0;">
                                    <label>Email Address</label>
                                    <input type="email" name="ref_doctor_email" id="ref_doctor_email" placeholder="Email Address">
                                </div>
                                <div class="ref-field ref-col-4" style="margin: 0;">
                                    <label>Address</label>
                                    <input type="text" name="ref_doctor_address" id="ref_doctor_address" placeholder="Clinic/Hospital Address">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ref-modal-footer">
                        <button type="button" onclick="closePatientModal()" class="ref-btn-cancel">Cancel</button>
                        <button type="submit" class="ref-btn-submit"><i class="fas fa-save"></i> Complete Registration </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Duplicate Alert Modal -->
    <div id="duplicateModal" class="modal-overlay hidden">
        <div class="modal-content alert-modal">
            <div class="modal-body" style="padding: 40px 30px;">
                <div class="alert-icon-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
                <h2 class="alert-title">Patient Already Exists</h2>
                <div id="duplicateInfo" class="alert-message">
                    Patient details already exist. Please proceed to appointment booking.
                </div>
                <div class="alert-footer">
                    <button id="proceedToBookingBtn" class="btn btn-primary btn-full">
                        <i class="fas fa-calendar-check"></i> Proceed to Booking
                    </button>
                    <button onclick="document.getElementById('duplicateModal').classList.add('hidden')"
                        class="btn btn-secondary btn-full">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleReferralSource() {
            const referredBy = document.getElementById('referred_by_select').value;
            const searchDiv = document.getElementById('doctorSearchDiv');
            const doctorExtraDetails = document.getElementById('doctorExtraDetailsDiv');
            const docWrapper = document.getElementById('doctorSelectWrapper');
            const plainWrapper = document.getElementById('plainReferralWrapper');
            
            if (referredBy === 'Self') {
                searchDiv.style.display = 'none';
                doctorExtraDetails.style.display = 'none';
                document.getElementById('referral_name_input').value = '';
                document.getElementById('is_new_doctor').value = '0';
            } else if (referredBy === 'Doctor') {
                searchDiv.style.display = 'block';
                docWrapper.style.display = 'block';
                plainWrapper.style.display = 'none';
                doctorExtraDetails.style.display = 'block';
                // Value is handled by Select2 change event
            } else {
                // Staff, Online, Others
                searchDiv.style.display = 'block';
                docWrapper.style.display = 'none';
                plainWrapper.style.display = 'block';
                doctorExtraDetails.style.display = 'none';
                document.getElementById('is_new_doctor').value = '0';
                document.getElementById('referral_name_input').value = document.getElementById('plainReferralName').value;
            }
        }
    </script>
    <!-- Appointment Modal -->
    <div id="appointmentModal" class="ref-modal-overlay hidden">
        <div class="ref-modal-card" onclick="event.stopPropagation()" style="max-width: 950px; width: 95%;">
            <div class="ref-modal-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="background: #E8F4EC; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #144D34;">
                        <i class="fas fa-calendar-check" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h2 id="modalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #144D34;">New Appointment</h2>
                        <span style="font-size: 0.78rem; color: #64748b;">Schedule one or multiple doctor consultations for the patient</span>
                    </div>
                </div>
                <button onclick="appointmentManager.closeModal()" class="ref-modal-close" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="ref-modal-body" style="padding: 1.25rem 1.5rem; max-height: 82vh; overflow-y: auto;">
                <form id="appointmentForm">
                    <input type="hidden" id="editAppointmentId" name="appointment_id">
                    <input type="hidden" id="patientPhone" name="phone">

                    <div class="ref-form-grid">
                        <!-- Section 1: Patient Information & Date -->
                        <div class="ref-section-title" style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-user-circle"></i> Patient & Date Information</span>
                            <span id="appointmentCountBadge" style="font-size: 0.72rem; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-weight: 700;">1 Doctor Selected</span>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Reference ID</label>
                            <input type="text" id="displayAppointmentId" class="readonly-mint" value="APT-AUTO" readonly>
                        </div>

                        <div class="ref-field ref-col-2">
                            <label>Select Patient <span class="req">*</span></label>
                            <select id="patientSelect" name="patient_id" style="width: 100%;" tabindex="1">
                                <option value="">Search by Patient ID, Name or Phone...</option>
                            </select>
                        </div>

                        <div class="ref-field ref-col-1">
                            <label>Appointment Date <span class="req">*</span></label>
                            <input type="date" id="appointmentDateMain" name="appointment_date" required tabindex="2" onchange="appointmentManager.onMainDateChange(this.value)">
                        </div>

                        <div class="ref-field ref-col-4">
                            <label>Reason / Chief Complaint</label>
                            <input type="text" id="appointmentReasonMain" name="reason" placeholder="Main complaint or purpose of visit..." tabindex="3">
                        </div>

                        <!-- Section 2: Doctor & Department Consultations -->
                        <div class="ref-section-title" style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.75rem;">
                            <span><i class="fas fa-user-md"></i> Doctor Consultations & Time Slots</span>
                            <button type="button" id="btnAddDoctorRow" onclick="appointmentManager.addDoctorRow()" class="btn btn-sm" style="background: #144D34; color: #fff; border-radius: 6px; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;">
                                <i class="fas fa-plus"></i> Add Another Doctor
                            </button>
                        </div>

                        <!-- Container for dynamic doctor rows -->
                        <div class="ref-col-4" id="doctorRowsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Dynamically populated via JS -->
                        </div>

                        <!-- Section 3: Notes -->
                        <div class="ref-section-title" style="margin-top: 0.5rem;">
                            <i class="fas fa-comment-dots"></i> Additional Notes
                        </div>

                        <div class="ref-field ref-col-4">
                            <textarea name="notes" id="appointmentNotes" rows="2" placeholder="Additional instructions, special requirements, or clinical notes..." tabindex="10"></textarea>
                        </div>
                    </div>

                    <div class="ref-modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 1rem; border-top: 1.5px solid #e2e8f0; margin-top: 1rem;">
                        <button type="button" onclick="appointmentManager.closeModal()" class="ref-btn-cancel" style="padding: 0.6rem 1.5rem; font-weight: 600;">Cancel</button>
                        <button type="submit" id="btnSaveOnly" class="ref-btn-submit" style="background: #144D34; color: #fff; padding: 0.6rem 1.8rem; font-weight: 700; border-radius: 8px; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <i class="fas fa-calendar-check"></i> <span id="btnSaveText">Commit Changes</span>
                        </button>
                    </div>
                </form>

                <script>
                    // Keyboard navigation and Focus Trap
                    document.addEventListener('DOMContentLoaded', function() {
                        const modal = document.getElementById('appointmentModal');
                        if (modal) {
                            modal.addEventListener('keydown', function(e) {
                                const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
                                const elements = Array.from(modal.querySelectorAll(focusableElements)).filter(el => !el.disabled && el.offsetParent !== null);
                                
                                // Tab key focus trap
                                if (e.key === 'Tab') {
                                    if(elements.length > 0) {
                                        const firstElement = elements[0];
                                        const lastElement = elements[elements.length - 1];
                                        
                                        if (e.shiftKey) { // Shift + Tab
                                            if (document.activeElement === firstElement) {
                                                lastElement.focus();
                                                e.preventDefault();
                                            }
                                        } else { // Tab
                                            if (document.activeElement === lastElement) {
                                                firstElement.focus();
                                                e.preventDefault();
                                            }
                                        }
                                    }
                                }
                                
                                // Enter key acts like Tab (skip textareas and buttons)
                                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
                                    e.preventDefault();
                                    const index = elements.indexOf(document.activeElement);
                                    if (index > -1 && index < elements.length - 1) {
                                        elements[index + 1].focus();
                                    }
                                }
                            });
                        }
                    });
                </script>
            </div>
        </div>
    </div>
