
        // Data Structure
        let hospitalData = {
            floors: {},
            stats: {
                totalFloors: 0,
                totalWards: 0,
                totalRooms: 0,
                totalBeds: 0,
                occupied: 0,
                available: 0,
                maintenance: 0,
                blocked: 0
            }
        };

        // Navigation State
        let currentView = {
            level: 'hospital', // 'hospital', 'floor', 'ward', 'room'
            floor: null,
            ward: null,
            room: null
        };

        // Initialize
        $(document).ready(function() {
            loadBedData();
            // Auto refresh
            setInterval(() => {
                // Only refresh if not deeply interacting, or refresh transparently
                loadBedData(true); 
            }, 30000);
        });

        function loadBedData(isRefresh = false) {
            IPD.ajax('beds', 'GET')
                .then(response => {
                    const beds = response.data.beds || [];
                    buildHierarchy(beds);
                    
                    if(!isRefresh) {
                        renderFloorSidebar();
                        renderTopStats();
                        
                        // Optionally auto-select first floor
                        const floorKeys = Object.keys(hospitalData.floors);
                        if(floorKeys.length > 0) {
                            setTimeout(() => {
                                $('.floor-item').first().click();
                            }, 100);
                        }
                    } else {
                        // Soft refresh current view
                        updateStatsOnly();
                        refreshCurrentView();
                    }
                })
                .catch(error => {
                    IPD.toast(error.message || 'Failed to load bed data', 'error');
                });
        }

        // 1. Data Processing
        function buildHierarchy(beds) {
            // Reset
            hospitalData = {
                floors: {},
                stats: { totalFloors: 0, totalWards: 0, totalRooms: 0, totalBeds: 0, occupied: 0, available: 0, maintenance: 0, blocked: 0 }
            };

            let uniqueFloors = new Set();
            let uniqueWards = new Set();
            let uniqueRooms = new Set();

            beds.forEach(bed => {
                const fName = bed.floor_name || 'Unassigned Floor';
                const wName = bed.ward_name || 'Unassigned Ward';
                const rNum = bed.room_number || '0';

                // Status Normalization (Handle Stale Occupied)
                let status = (bed.bed_status || 'Available').toLowerCase();
                if (status === 'occupied' && !bed.patient_id) status = 'available';
                
                let normStatus = 'Available';
                if(status === 'occupied') normStatus = 'Occupied';
                if(status === 'blocked') normStatus = 'Blocked';
                if(status === 'maintenance' || status === 'maintainance') normStatus = 'Maintenance';

                // Ensure Floor exists
                if (!hospitalData.floors[fName]) {
                    hospitalData.floors[fName] = { name: fName, wards: {}, stats: { total:0, occ:0, avail:0 } };
                    uniqueFloors.add(fName);
                }
                
                // Ensure Ward exists
                if (!hospitalData.floors[fName].wards[wName]) {
                    hospitalData.floors[fName].wards[wName] = { 
                        name: wName, 
                        type: bed.ward_type,
                        rooms: {}, 
                        stats: { total:0, occ:0, avail:0 } 
                    };
                    uniqueWards.add(fName + '_' + wName);
                }

                // Ensure Room exists
                if (!hospitalData.floors[fName].wards[wName].rooms[rNum]) {
                    hospitalData.floors[fName].wards[wName].rooms[rNum] = {
                        number: rNum,
                        name: bed.room_name,
                        type: bed.room_category || bed.room_type,
                        beds: [],
                        stats: { total:0, occ:0, avail:0 }
                    };
                    uniqueRooms.add(fName + '_' + wName + '_' + rNum);
                }

                // Append Bed
                const bedObj = { ...bed, normalized_status: normStatus };
                hospitalData.floors[fName].wards[wName].rooms[rNum].beds.push(bedObj);

                // Aggregate Stats
                hospitalData.stats.totalBeds++;
                hospitalData.floors[fName].stats.total++;
                hospitalData.floors[fName].wards[wName].stats.total++;
                hospitalData.floors[fName].wards[wName].rooms[rNum].stats.total++;

                if (normStatus === 'Occupied') {
                    hospitalData.stats.occupied++;
                    hospitalData.floors[fName].stats.occ++;
                    hospitalData.floors[fName].wards[wName].stats.occ++;
                    hospitalData.floors[fName].wards[wName].rooms[rNum].stats.occ++;
                } else if (normStatus === 'Available') {
                    hospitalData.stats.available++;
                    hospitalData.floors[fName].stats.avail++;
                    hospitalData.floors[fName].wards[wName].stats.avail++;
                    hospitalData.floors[fName].wards[wName].rooms[rNum].stats.avail++;
                } else if (normStatus === 'Blocked') {
                    hospitalData.stats.blocked++;
                } else if (normStatus === 'Maintenance') {
                    hospitalData.stats.maintenance++;
                }
            });

            hospitalData.stats.totalFloors = uniqueFloors.size;
            hospitalData.stats.totalWards = uniqueWards.size;
            hospitalData.stats.totalRooms = uniqueRooms.size;
        }

        // 2. Sidebar & Global Stats
        function renderTopStats() {
            const s = hospitalData.stats;
            const html = `
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bed"></i></div>
                    <div class="stat-info">
                        <h4>${s.totalBeds}</h4>
                        <p>Total Beds</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-occ);"><i class="fas fa-user-injured"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-occ);">${s.occupied}</h4>
                        <p>Occupied</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-avail);"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-avail);">${s.available}</h4>
                        <p>Available</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color:var(--status-maint);"><i class="fas fa-tools"></i></div>
                    <div class="stat-info">
                        <h4 style="color:var(--status-maint);">${s.maintenance}</h4>
                        <p>Maintenance</p>
                    </div>
                </div>
            `;
            $('#topStats').html(html);
        }

        function updateStatsOnly() {
            renderTopStats();
            // Just update inner counts on sidebar, keep active state
            Object.values(hospitalData.floors).forEach(floor => {
                const wardsCount = Object.keys(floor.wards).length;
                const el = $(\`.floor-item[data-floor="\${floor.name}"]\`);
                if(el.length) {
                    el.find('.floor-item-meta').text(\`\${wardsCount} Wards • \${floor.stats.total} Beds\`);
                }
            });
        }

        function renderFloorSidebar() {
            const list = $('#floorList');
            list.empty();

            Object.values(hospitalData.floors).forEach(floor => {
                const isActive = (currentView.floor === floor.name) ? 'active' : '';
                const wardsCount = Object.keys(floor.wards).length;
                
                const item = $(\`
                    <div class="floor-item \${isActive}" data-floor="\${floor.name}">
                        <div class="icon"><i class="fas fa-layer-group"></i></div>
                        <div class="floor-item-content">
                            <div class="floor-item-title">\${floor.name}</div>
                            <div class="floor-item-meta">\${wardsCount} Wards • \${floor.stats.total} Beds</div>
                        </div>
                    </div>
                \`);

                item.click(function() {
                    $('.floor-item').removeClass('active');
                    $(this).addClass('active');
                    navigateTo('floor', floor.name);
                });

                list.append(item);
            });
        }

        // 3. Navigation Engine
        function navigateTo(level, fName, wName = null, rNum = null) {
            currentView = { level, floor: fName, ward: wName, room: rNum };
            
            // Render Breadcrumbs
            renderBreadcrumbs();

            // Clear search filter when navigating hierarchically
            $('#globalSearch').val('');
            $('#statusFilter').val('');

            // Render content based on level
            if (level === 'floor') {
                renderWards(fName);
            } else if (level === 'ward') {
                renderRooms(fName, wName);
            } else if (level === 'room') {
                renderBeds(fName, wName, rNum);
            }
        }

        function refreshCurrentView() {
            if (currentView.level === 'floor') renderWards(currentView.floor);
            else if (currentView.level === 'ward') renderRooms(currentView.floor, currentView.ward);
            else if (currentView.level === 'room') renderBeds(currentView.floor, currentView.ward, currentView.room);
        }

        function renderBreadcrumbs() {
            let html = \`<div class="breadcrumb-item" onclick="resetHospital()"><i class="fas fa-hospital"></i> Hospital</div>\`;
            
            if (currentView.floor) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'floor' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}" onclick="navigateTo('floor', '\${currentView.floor}')">\${currentView.floor}</div>\`;
            }
            if (currentView.ward) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'ward' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}" onclick="navigateTo('ward', '\${currentView.floor}', '\${currentView.ward}')">\${currentView.ward}</div>\`;
            }
            if (currentView.room) {
                html += \`<i class="fas fa-chevron-right breadcrumb-separator"></i>\`;
                const act = currentView.level === 'room' ? 'active' : '';
                html += \`<div class="breadcrumb-item \${act}">Room \${currentView.room}</div>\`;
            }
            $('#appBreadcrumbs').html(html);
        }

        function resetHospital() {
            currentView = { level: 'hospital', floor: null, ward: null, room: null };
            $('.floor-item').removeClass('active');
            renderBreadcrumbs();
            $('#appDynamicView').html(\`
                <div class="empty-state">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Select a Floor</h3>
                    <p>Choose a floor from the left panel to begin managing beds.</p>
                </div>
            \`);
        }

        // 4. Content Rendering
        function renderWards(floorName) {
            const floor = hospitalData.floors[floorName];
            if (!floor) return;

            let html = \`<div class="grid-layout">\`;
            
            Object.values(floor.wards).forEach(ward => {
                const occPct = ward.stats.total > 0 ? Math.round((ward.stats.occ / ward.stats.total) * 100) : 0;
                
                html += \`
                    <div class="premium-card searchable-card" data-search="\${ward.name.toLowerCase()}" onclick="navigateTo('ward', '\${floorName}', '\${ward.name}')">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-hospital-alt"></i> \${ward.name}</div>
                            <span class="bed-badge" style="background:var(--bed-bg); color:var(--bed-primary)">\${Object.keys(ward.rooms).length} Rooms</span>
                        </div>
                        <div class="card-stats">
                            <div class="card-stat-item">
                                <div class="card-stat-val occ">\${ward.stats.occ}</div>
                                <div class="card-stat-label">Occupied</div>
                            </div>
                            <div class="card-stat-item">
                                <div class="card-stat-val ava">\${ward.stats.avail}</div>
                                <div class="card-stat-label">Available</div>
                            </div>
                        </div>
                        <div style="font-size:0.75rem; color:var(--bed-text-muted); display:flex; justify-content:space-between; margin-top:1rem;">
                            <span>Total Beds: \${ward.stats.total}</span>
                            <span>\${occPct}% Full</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: \${occPct}%; \${occPct > 80 ? 'background:var(--status-occ)' : ''}"></div>
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        function renderRooms(floorName, wardName) {
            const ward = hospitalData.floors[floorName].wards[wardName];
            if (!ward) return;

            let html = \`<div class="grid-layout">\`;
            
            Object.values(ward.rooms).forEach(room => {
                const occPct = room.stats.total > 0 ? Math.round((room.stats.occ / room.stats.total) * 100) : 0;
                
                html += \`
                    <div class="premium-card searchable-card" data-search="room \${room.number.toLowerCase()} \${room.name ? room.name.toLowerCase() : ''}" onclick="navigateTo('room', '\${floorName}', '\${wardName}', '\${room.number}')">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-door-open"></i> Room \${room.number}</div>
                            <span class="bed-badge" style="background:var(--bed-bg); color:var(--bed-primary)">\${room.type || 'General'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--status-occ)">\${room.stats.occ}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Occupied</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--status-avail)">\${room.stats.avail}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Available</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:1.5rem; font-weight:700; color:var(--bed-text-dark)">\${room.stats.total}</div>
                                <div style="font-size:0.65rem; text-transform:uppercase; color:var(--bed-text-muted)">Total Beds</div>
                            </div>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: \${occPct}%; \${occPct === 100 ? 'background:var(--status-occ)' : ''}"></div>
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        function renderBeds(floorName, wardName, roomNum) {
            const room = hospitalData.floors[floorName].wards[wardName].rooms[roomNum];
            if (!room) return;

            let html = \`<div class="bed-grid-layout">\`;
            
            room.beds.forEach(bed => {
                const st = bed.normalized_status.toLowerCase(); // available, occupied, blocked, maintenance
                
                let actionHtml = '';
                let patientHtml = '';

                if (st === 'occupied') {
                    patientHtml = \`
                        <div class="bed-patient-name"><i class="fas fa-user-injured"></i> \${bed.patient_name || 'Unknown Patient'}</div>
                        <div>PID: \${bed.patient_id}</div>
                        <div>Adm: \${IPD.formatDate(bed.admission_date)}</div>
                    \`;
                    actionHtml = \`<button class="btn-bed-action btn-release" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'release')">Release Bed</button>\`;
                } else if (st === 'available') {
                    patientHtml = \`<div style="padding:1rem 0; text-align:center; color:var(--status-avail)"><i class="fas fa-check-circle fa-2x"></i></div>\`;
                    actionHtml = \`<button class="btn-bed-action btn-manage" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'manage')">Change Status</button>\`;
                } else {
                    patientHtml = \`<div style="padding:1rem 0; text-align:center;"><i class="fas fa-ban fa-2x" style="opacity:0.2"></i></div>\`;
                    actionHtml = \`<button class="btn-bed-action btn-manage" onclick="event.stopPropagation(); handleAction('\${bed.bed_id}', 'manage')">Change Status</button>\`;
                }

                html += \`
                    <div class="bed-card status-\${st} searchable-card" data-search="\${bed.bed_number.toLowerCase()} \${bed.patient_name ? bed.patient_name.toLowerCase() : ''}" data-status="\${bed.normalized_status}">
                        <div class="bed-card-head">
                            <div class="bed-num"><i class="fas fa-bed"></i> \${bed.bed_number}</div>
                            <span class="bed-badge">\${bed.normalized_status}</span>
                        </div>
                        <div class="bed-info">
                            \${patientHtml}
                        </div>
                        <div class="bed-action">
                            \${actionHtml}
                        </div>
                    </div>
                \`;
            });
            html += \`</div>\`;
            $('#appDynamicView').html(html);
        }

        // 5. Actions & Search
        function handleAction(bedId, action) {
            if (action === 'release') {
                if (confirm('Are you sure you want to release this bed?')) {
                    IPD.ajax('beds?action=release', 'POST', { bed_id: bedId })
                        .then(() => {
                            IPD.toast('Bed released successfully', 'success');
                            loadBedData();
                        })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            } else if (action === 'manage') {
                const newStatus = prompt('Change status to (Available/Blocked/Maintenance):', 'Maintenance');
                if (newStatus && ['Available', 'Blocked', 'Maintenance'].includes(newStatus)) {
                    IPD.ajax('beds?id=' + bedId, 'PUT', { status: newStatus })
                        .then(() => {
                            IPD.toast('Status updated', 'success');
                            loadBedData();
                        })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            }
        }

        function filterView() {
            const query = $('#globalSearch').val().toLowerCase();
            const statusFilter = $('#statusFilter').val();

            $('.searchable-card').each(function() {
                let match = true;
                const txt = $(this).data('search') || '';
                const st = $(this).data('status') || '';

                if (query && !txt.includes(query)) match = false;
                if (statusFilter && currentView.level === 'room' && st !== statusFilter) match = false;

                $(this).toggle(match);
            });
        }
    
