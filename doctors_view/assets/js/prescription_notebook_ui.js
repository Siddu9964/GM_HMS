/**
 * SUPREME MEDICAL NOTEBOOK UI - HYPER-REALISTIC PHYSICAL ENGINE
 * Features: Paper Texture, Metallic Spiral, Depth Stacking, Light Scattering
 */

(function () {
    'use strict';

    // State management
    let prescriptionHistory = [];
    let currentIndex = 0;
    let isFlipping = false;

    /**
     * Inject Hyper-Realistic Notebook Styles
     */
    function injectStyles() {
        if (document.getElementById('notebook-styles')) return;

        const style = document.createElement('style');
        style.id = 'notebook-styles';
        style.textContent = `
            .notebook-wrapper {
                width: 100%;
                perspective: 3000px;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 60px 0;
                background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.05) 0%, transparent 80%);
            }

            .notebook-outer {
                position: relative;
                transform: rotateX(5deg) rotateY(-2deg); /* Subtle 3D desk tilt */
                filter: drop-shadow(0 50px 100px rgba(0,0,0,0.25));
            }

            /* Stack of pages effect */
            .notebook-outer::before {
                content: '';
                position: absolute;
                top: 5px; left: 5px; right: 5px; bottom: -10px;
                background: #e5e5e5;
                border-radius: 8px;
                box-shadow: 0 2px 2px rgba(0,0,0,0.1);
                z-index: -1;
            }

            .notebook {
                width: 1100px;
                height: 700px;
                position: relative;
                transform-style: preserve-3d;
                display: flex;
                background: #FBFAF7; /* Supreme Ivory */
                border-radius: 8px;
                overflow: visible;
            }

            /* Paper Texture & Lighting */
            .page {
                width: 550px;
                height: 100%;
                background-color: #FBFAF7;
                background-image: 
                    url("https://www.transparenttextures.com/patterns/creampaper.png"),
                    linear-gradient(to right, rgba(0,0,0,0.05) 0%, transparent 5%, transparent 95%, rgba(0,0,0,0.05) 100%);
                position: relative;
                padding: 60px;
                overflow-y: auto;
                overflow-x: hidden;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                scrollbar-width: none;
            }

            .page::-webkit-scrollbar { display: none; }

            .static-left { 
                border-radius: 8px 0 0 8px;
                border-right: 1px solid rgba(0,0,0,0.1);
                /* Page curve near spiral */
                box-shadow: inset -30px 0 50px rgba(0,0,0,0.02);
            }

            .static-right { 
                border-radius: 0 8px 8px 0;
                /* Page curve near spiral */
                box-shadow: inset 30px 0 50px rgba(0,0,0,0.02);
            }

            /* Realistic Spiral Binding */
            .spiral-container {
                position: absolute;
                left: 50%;
                top: 0;
                width: 40px;
                height: 100%;
                transform: translateX(-50%);
                z-index: 1000;
                display: flex;
                flex-direction: column;
                justify-content: space-around;
                align-items: center;
                padding: 15px 0;
                pointer-events: none;
            }

            .spiral-ring {
                width: 34px;
                height: 12px;
                background: linear-gradient(180deg, #E0E0E0 0%, #C0C0C0 30%, #8E8E8E 70%, #606060 100%);
                border-radius: 6px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.3), inset 0 1px 2px rgba(255,255,255,0.8);
                position: relative;
                margin: 4px 0;
            }

            /* Shadow under ring on the paper */
            .spiral-ring::before {
                content: '';
                position: absolute;
                bottom: -4px;
                left: 2px;
                width: 90%;
                height: 3px;
                background: rgba(0,0,0,0.15);
                filter: blur(2px);
                border-radius: 50%;
            }

            /* PREMIUM SIDE-TAB NAVIGATION (BOOKMARK STYLE) */
            .notebook-side-tab {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 100px;
                height: 48px;
                background: #FFFFFF;
                border: 1px solid #E2E8F0;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 2000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                font-family: 'Inter', sans-serif;
                font-size: 0.8rem;
                font-weight: 800;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                color: #1F4E79; /* Deep Medical Blue */
                pointer-events: auto; /* Ensure clickable */
            }

            .tab-prev {
                left: -60px; /* Sticks out significantly */
                border-radius: 99px 0 0 99px;
                padding-left: 15px;
                padding-right: 5px;
            }

            .tab-next {
                right: -60px; /* Sticks out significantly */
                border-radius: 0 99px 99px 0;
                padding-right: 15px;
                padding-left: 5px;
            }

            /* Hover Interaction: Slide Out */
            .notebook-side-tab:hover:not(:disabled) {
                background: #FFFFFF;
                color: #2FA4A9; /* Teal Accent */
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            }

            .tab-prev:hover { transform: translateY(-50%) translateX(-15px); }
            .tab-next:hover { transform: translateY(-50%) translateX(15px); }

            .notebook-side-tab i {
                font-size: 1rem;
            }

            .tab-prev i { margin-right: 8px; }
            .tab-next i { margin-left: 8px; }

            .notebook-side-tab:disabled {
                opacity: 0.3;
                cursor: not-allowed;
                filter: grayscale(1);
            }

            /* Flipping Page with Curl Simulation */
            .flipping-page {
                position: absolute;
                top: 0;
                right: 0;
                width: 550px;
                height: 100%;
                transform-origin: left center;
                transform-style: preserve-3d;
                z-index: 500;
                pointer-events: none;
                display: none;
            }

            .flip-side {
                position: absolute;
                top: 0; left: 0; width: 100%; height: 100%;
                backface-visibility: hidden;
                padding: 60px;
                box-sizing: border-box;
                background-color: #FBFAF7;
                background-image: url("https://www.transparenttextures.com/patterns/creampaper.png");
                display: flex;
                flex-direction: column;
                box-shadow: inset 0 0 100px rgba(0,0,0,0.05);
            }

            .flip-front { border-radius: 0 8px 8px 0; z-index: 2; }
            .flip-back { 
                transform: rotateY(180deg); 
                border-radius: 8px 0 0 8px;
                background-image: 
                    url("https://www.transparenttextures.com/patterns/creampaper.png"),
                    linear-gradient(to right, rgba(0,0,0,0.1) 0%, transparent 10%);
            }

            /* Content Typography */
            .nb-label {
                font-family: 'Inter', sans-serif;
                font-size: 0.75rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: #B0B0B0;
                margin-bottom: 0.8rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .nb-label::after {
                content: '';
                flex: 1;
                height: 1px;
                background: #E2E8F0;
            }

            .nb-main-date {
                font-family: 'Georgia', serif;
                font-size: 2rem;
                font-weight: 700;
                color: #2C2C2C;
                margin-bottom: 0.5rem;
            }

            .nb-doc-highlight {
                color: #1F4E79;
                font-weight: 700;
                font-size: 1.15rem;
                margin: 10px 0;
            }

            .nb-diagnosis-box {
                background: rgba(255,255,255,0.7);
                border: 1px solid #E2E8F0;
                border-left: 5px solid #2FA4A9;
                padding: 20px;
                border-radius: 4px;
                margin: 20px 0;
                color: #2C2C2C;
                font-size: 1rem;
                line-height: 1.6;
                box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            }

            .nb-med-row {
                display: flex;
                align-items: flex-start;
                gap: 15px;
                padding: 15px 0;
                border-bottom: 1px solid rgba(0,0,0,0.04);
            }

            .nb-med-bullet {
                width: 10px;
                height: 10px;
                background: #2FA4A9;
                border-radius: 50%;
                margin-top: 6px;
                box-shadow: 0 2px 4px rgba(47,164,169,0.3);
            }

            .nb-page-num-v2 {
                position: absolute;
                bottom: 25px;
                font-weight: 800;
                font-size: 0.75rem;
                color: #D1D1D1;
                letter-spacing: 2px;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Get HTML for Left Page
     */
    function getLeftPageHTML(rx, pageIdx) {
        if (!rx) return '';
        return `
            <div class="nb-label">Medical Journal Entry</div>
            <div class="nb-main-date">${rx.prescription_date || 'N/A'}</div>
            
            <div style="margin-top: 30px;">
                <div class="nb-label">Attending Physician</div>
                <div class="nb-doc-highlight">
                    <i class="fas fa-stethoscope"></i> Dr. ${rx.doctor_name || 'MD'}
                </div>
                <div style="font-size: 0.9rem; color: #64748B; padding-left: 28px;">
                    ${rx.specialization || rx.designation || 'Specialist'}
                </div>
            </div>

            <div style="margin-top: 30px;">
                <div class="nb-label">Clinical Data</div>
                <div class="nb-diagnosis-box">
                    ${rx.diagnosis || 'Standard assessment.'}
                </div>
            </div>

            ${rx.general_instructions ? `
                <div style="margin-top: auto;">
                    <div class="nb-label">Physician Notes</div>
                    <div style="font-size: 0.95rem; color: #475569; line-height: 1.7; font-style: italic; padding: 15px; background: rgba(0,0,0,0.01); border-radius: 8px;">
                        "${rx.general_instructions}"
                    </div>
                </div>
            ` : ''}

            <div class="nb-page-num-v2" style="left: 60px;">PG. ${currentIndex * 2 + 1}</div>
        `;
    }

    /**
     * Get HTML for Right Page
     */
    function getRightPageHTML(rx, pageIdx) {
        if (!rx) return '';
        let medsHtml = '';
        try {
            const meds = typeof rx.medicines === 'string' ? JSON.parse(rx.medicines || '[]') : (rx.medicines || []);
            medsHtml = meds.map(m => `
                <div class="nb-med-row">
                    <div class="nb-med-bullet"></div>
                    <div>
                        <div style="font-weight: 700; color: #2C2C2C;">${m.name || m.tablet_name}</div>
                        <div style="font-size: 0.85rem; color: #64748B; margin-top: 3px;">
                            ${m.dosage || ''} | ${m.timing || ''} | ${m.duration || ''}
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (e) { medsHtml = `<div class="nb-med-row">${rx.medicines}</div>`; }

        return `
            <div class="nb-label">Prescription Plan</div>
            <div style="flex: 1;">${medsHtml || '<div style="color:#B0B0B0; padding:20px;">No medications recorded.</div>'}</div>
            
            ${rx.dietary_advice ? `
                <div style="margin-top: 20px;">
                    <div class="nb-label">Dietary Advice</div>
                    <div style="font-size: 0.9rem; color: #475569; padding: 12px; background: rgba(16,185,129,0.03); border: 1px solid rgba(16,185,129,0.1); border-radius: 8px;">
                        <i class="fas fa-apple-alt" style="color: #10B981; margin-right: 8px;"></i> ${rx.dietary_advice}
                    </div>
                </div>
            ` : ''
            }
            
            ${rx.follow_up_date ? `
                <div style="margin-top: 30px; padding: 25px; background: rgba(31,78,121,0.03); border-radius: 12px; border: 1px solid rgba(31,78,121,0.08);">
                    <div class="nb-label" style="color: #1F4E79;">Follow-Up Appointment</div>
                    <div style="font-weight: 800; color: #1F4E79; font-size: 1.3rem; margin-bottom: 8px;">
                        <i class="fas fa-calendar-alt"></i> ${rx.follow_up_date}
                    </div>
                    ${rx.follow_up_instructions ? `
                        <div style="font-size: 0.9rem; color: #475569; padding-top: 8px; border-top: 1px dashed rgba(31,78,121,0.2);">
                            <i class="fas fa-info-circle" style="color: #1F4E79; opacity: 0.5;"></i> ${rx.follow_up_instructions}
                        </div>
                    ` : ''}
                </div>
            ` : ''
            }

        <div class="nb-page-num-v2" style="right: 60px;">PG. ${currentIndex * 2 + 2}</div>
        `;
    }

    /**
     * Animate Flip with Curl Effect Logic
     */
    function flipPage(direction) {
        if (isFlipping) return;

        const nextIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;
        if (nextIndex < 0 || nextIndex >= prescriptionHistory.length) return;

        isFlipping = true;

        const flippingPage = document.getElementById('page-flip-element');
        const flipFront = document.getElementById('flip-front-content');
        const flipBack = document.getElementById('flip-back-content');
        const staticLeft = document.getElementById('nb-page-left');
        const staticRight = document.getElementById('nb-page-right');

        flippingPage.style.display = 'block';

        if (direction === 'next') {
            flippingPage.style.right = '0';
            flippingPage.style.left = 'auto';
            flippingPage.style.transformOrigin = 'left center';

            flipFront.innerHTML = getRightPageHTML(prescriptionHistory[currentIndex]);
            flipBack.innerHTML = getLeftPageHTML(prescriptionHistory[nextIndex]);
            staticRight.innerHTML = getRightPageHTML(prescriptionHistory[nextIndex]);

            const tl = gsap.timeline({
                onComplete: () => {
                    currentIndex = nextIndex;
                    staticLeft.innerHTML = getLeftPageHTML(prescriptionHistory[currentIndex]);
                    flippingPage.style.display = 'none';
                    updateNav();
                    isFlipping = false;
                }
            });

            // Page curl simulation with natural 160-degree curve
            tl.fromTo(flippingPage,
                { rotateY: 0, z: 0, skewY: 0 },
                { duration: 1.2, rotateY: -160, z: 80, skewY: 2, ease: "power2.inOut" }
            );
            // Moving shadow during flip
            tl.fromTo(flipFront, { boxShadow: "inset 0 0 0px rgba(0,0,0,0)" }, { duration: 0.6, boxShadow: "inset 100px 0 100px rgba(0,0,0,0.1)", yoyo: true, repeat: 1 }, 0);

        } else {
            flippingPage.style.left = '0';
            flippingPage.style.right = 'auto';
            flippingPage.style.transformOrigin = 'right center';

            flipFront.innerHTML = getLeftPageHTML(prescriptionHistory[currentIndex]);
            flipBack.innerHTML = getRightPageHTML(prescriptionHistory[nextIndex]);
            staticLeft.innerHTML = getLeftPageHTML(prescriptionHistory[nextIndex]);

            const tl = gsap.timeline({
                onComplete: () => {
                    currentIndex = nextIndex;
                    staticRight.innerHTML = getRightPageHTML(prescriptionHistory[currentIndex]);
                    flippingPage.style.display = 'none';
                    updateNav();
                    isFlipping = false;
                }
            });

            tl.fromTo(flippingPage,
                { rotateY: 0, z: 0, skewY: 0 },
                { duration: 1.2, rotateY: 160, z: 80, skewY: -2, ease: "power2.inOut" }
            );
        }
    }

    function updateNav() {
        const tabPrev = document.getElementById('tab-prev');
        const tabNext = document.getElementById('tab-next');
        const pageNum = document.getElementById('nb-page-num');
        const headerPrev = document.getElementById('header-prev');
        const headerNext = document.getElementById('header-next');

        if (tabPrev) tabPrev.disabled = currentIndex === 0;
        if (tabNext) tabNext.disabled = currentIndex === prescriptionHistory.length - 1;

        if (headerPrev) headerPrev.disabled = currentIndex === 0;
        if (headerNext) headerNext.disabled = currentIndex === prescriptionHistory.length - 1;

        if (pageNum) pageNum.innerText = `Prescription ${currentIndex + 1} / ${prescriptionHistory.length}`;
    }

    window.buildPrescriptionNotebook = function (prescriptions) {
        if (!prescriptions || prescriptions.length === 0) return '';

        // Smart Paging (same as before)
        const processed = [];
        prescriptions.forEach(rx => {
            const meds = typeof rx.medicines === 'string' ? JSON.parse(rx.medicines || '[]') : (rx.medicines || []);
            const notes = rx.general_instructions || '';
            const LIMIT_MEDS = 6;
            const LIMIT_NOTES = 500;

            if (meds.length > LIMIT_MEDS || notes.length > LIMIT_NOTES) {
                const count = Math.max(Math.ceil(meds.length / LIMIT_MEDS), Math.ceil(notes.length / LIMIT_NOTES));
                for (let i = 0; i < count; i++) {
                    processed.push({
                        ...rx,
                        prescription_date: i === 0 ? rx.prescription_date : `${rx.prescription_date} (Cont.)`,
                        diagnosis: i === 0 ? rx.diagnosis : `Physician Notes - Part ${i + 1}`,
                        medicines: meds.slice(i * LIMIT_MEDS, (i + 1) * LIMIT_MEDS),
                        general_instructions: notes.substring(i * LIMIT_NOTES, (i + 1) * LIMIT_NOTES),
                        is_continuation: i > 0
                    });
                }
            } else processed.push(rx);
        });

        prescriptionHistory = processed;
        currentIndex = 0;
        injectStyles();

        let spiralRings = '';
        for (let i = 0; i < 18; i++) spiralRings += '<div class="spiral-ring"></div>';

        setTimeout(() => {
            const left = document.getElementById('nb-page-left');
            const right = document.getElementById('nb-page-right');
            if (left && right) {
                left.innerHTML = getLeftPageHTML(prescriptionHistory[0]);
                right.innerHTML = getRightPageHTML(prescriptionHistory[0]);
                updateNav();
            }
        }, 100);

        return `
            <div class="notebook-wrapper">
                <div class="notebook-outer">
                    <div class="notebook" id="prescription-notebook">
                        <!-- Semi-Visible Page Tabs (Bookmark Style) -->
                        <button id="tab-prev" class="notebook-side-tab tab-prev" onclick="window.PrescriptionNotebook.flip('prev')">
                            <i class="fas fa-chevron-left"></i> <span>Prev</span>
                        </button>
                        <button id="tab-next" class="notebook-side-tab tab-next" onclick="window.PrescriptionNotebook.flip('next')">
                            <span>Next</span> <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Spiral Rendering -->
                        <div class="spiral-container">${spiralRings}</div>

                        <!-- Pages -->
                        <div class="page static-left" id="nb-page-left"></div>
                        <div class="page static-right" id="nb-page-right"></div>
                        
                        <!-- 3D Flipping Layer -->
                        <div class="flipping-page" id="page-flip-element">
                            <div class="flip-side flip-front" id="flip-front-content"></div>
                            <div class="flip-side flip-back" id="flip-back-content"></div>
                        </div>
                    </div>
                </div>
                <div id="nb-page-num" style="margin-top: 20px; font-weight: 800; color: #B0B0B0; font-size: 0.8rem; letter-spacing: 1px;"></div>
            </div>
        `;
    };

    window.PrescriptionNotebook = { flip: (p) => flipPage(p) };

})();
