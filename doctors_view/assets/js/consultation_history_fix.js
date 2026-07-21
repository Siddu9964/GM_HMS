/**
 * MEDICAL HISTORY VIEWER - COMPACT MINI HEADER VERSION
 * Focuses on high-end horizontal header and visible navigation
 */

(function () {
    'use strict';

    console.log('Loading medical history compact header fix...');

    /**
     * View complete patient medical history - FIXED
     */
    window.viewPatientHistory = async function () {
        const patientId = document.getElementById('patient-select').value;
        if (!patientId) {
            showToast('Please select a patient first', 'warning');
            return;
        }

        try {
            showLoading('Loading medical file...');

            const [consultationsRes, prescriptionsRes] = await Promise.all([
                API.get(`consultations?patient_id=${patientId}`).catch(() => ({ success: false, data: [] })),
                API.get(`prescriptions/patient/${patientId}`).catch(() => ({ success: false, data: { history: [] } }))
            ]);

            hideLoading();

            let prescriptions = [];
            if (prescriptionsRes.data) {
                if (Array.isArray(prescriptionsRes.data)) prescriptions = prescriptionsRes.data;
                else if (prescriptionsRes.data.history) prescriptions = prescriptionsRes.data.history;
            }

            // Show customized compact modal
            showMedicalHistoryModal({
                patient: currentPatient,
                prescriptions: prescriptions
            });

        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            showToast('Failed to load history', 'error');
        }
    };

    /**
     * Display medical history in a FULL-WIDTH modal with COMPACT HEADER
     */
    window.showMedicalHistoryModal = function (data) {
        const { patient, prescriptions } = data;

        const modal = document.createElement('div');
        modal.className = 'medical-history-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1rem;
            animation: fadeIn 0.3s ease;
        `;

        const content = document.createElement('div');
        content.style.cssText = `
            background: #F1F5F9;
            border-radius: 20px;
            width: 100%;
            max-width: 1400px;
            max-height: 95vh;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        `;

        // PREMIUM MEDICAL IDENTITY STRIP (60/40 SPLIT DESIGN)
        content.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #F0FDFA 0%, #FFFFFF 100%);
                padding: 1.5rem 2.5rem;
                display: flex;
                flex-direction: column;
                gap: 2rem;
                border-bottom: 1px solid rgba(203, 213, 225, 0.4);
                z-index: 1000;
                position: relative;
                box-shadow: inset 0 -2px 10px rgba(0, 0, 0, 0.02);
            ">
                <!-- TOP ZONE: 60/40 IDENTITY & ACTIONS -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    
                    <!-- LEFT SIDE (60%): IDENTITY -->
                    <div style="flex: 0 0 60%; display: flex; align-items: center; gap: 24px;">
                        <!-- Large Avatar with Glow -->
                        <div style="
                            width: 64px; height: 64px; 
                            background: white; 
                            border-radius: 18px; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            color: #0D9488; 
                            font-size: 1.8rem;
                            box-shadow: 0 10px 25px rgba(13, 148, 136, 0.15), 0 0 0 4px rgba(13, 148, 136, 0.05);
                            border: 1px solid rgba(13, 148, 136, 0.1);
                        ">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
                                <h1 style="margin: 0; font-size: 2rem; font-weight: 800; color: #0F172A; letter-spacing: -0.8px;">
                                    ${patient.first_name || ''} ${patient.last_name || ''}
                                </h1>
                                <span style="
                                    background: rgba(100, 116, 139, 0.1); 
                                    color: #475569; 
                                    padding: 4px 12px; 
                                    border-radius: 99px; 
                                    font-size: 0.75rem; 
                                    font-weight: 700; 
                                    letter-spacing: 0.5px;
                                    font-family: 'JetBrains Mono', monospace;
                                ">
                                    ${patient.patient_id}
                                </span>
                            </div>
                            <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #64748B; opacity: 0.8; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-file-medical-alt" style="color: #0D9488; font-size: 0.8rem;"></i>
                                Patient Medical File
                            </p>
                        </div>
                    </div>

                    <!-- RIGHT SIDE (40%): ACTIONS -->
                    <div style="flex: 0 0 35%; display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                        <button onclick="window.print()" style="
                            background: white;
                            border: 1px solid rgba(0,0,0,0.05);
                            color: #64748B;
                            padding: 10px 20px;
                            border-radius: 12px;
                            font-weight: 700;
                            font-size: 0.85rem;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            transition: all 0.2s ease;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
                        " onmouseover="this.style.background='#F8FAFC'; this.style.color='#1E293B'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='white'; this.style.color='#64748B'; this.style.transform='none'">
                            <i class="fas fa-print"></i>
                            Download PDF
                        </button>

                        <button onclick="this.closest('.medical-history-modal').remove()" style="
                            background: #F1F5F9; 
                            border: none; 
                            color: #64748B;
                            width: 44px; height: 44px; 
                            border-radius: 12px; 
                            cursor: pointer;
                            display: flex; align-items: center; justify-content: center;
                            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                        " onmouseover="this.style.background='#EF4444'; this.style.color='white'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='#F1F5F9'; this.style.color='#64748B'; this.style.transform='scale(1)'">
                            <i class="fas fa-times" style="font-size: 1.1rem;"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div style="flex: 1; overflow-y: auto; padding: 2rem 0; background: #F8FAFC;" id="modal-body-container">
                <!-- CONTENT CONTAINER -->
                <div style="width: 100%; max-width: 1200px; margin: 0 auto;">
                    ${buildHistoryContent([], prescriptions)}
                </div>
            </div>
        `;

        modal.appendChild(content);
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        // Initialize and Update Nav state
        setTimeout(() => {
            if (window.PrescriptionNotebook) {
                // Remove the redundant controls below the notebook
                const oldNav = document.querySelector('.notebook-nav');
                if (oldNav) oldNav.style.display = 'none';
            }
        }, 100);
    };

    /**
     * Compact Content Builder
     */
    window.buildHistoryContent = function (consultations, prescriptions) {
        let html = '';

        // DIRECT NOTEBOOK (Stats are now in the Identity Strip above)
        if (typeof buildPrescriptionNotebook === 'function') {
            html += buildPrescriptionNotebook(prescriptions);
        }

        return html;
    };

})();
