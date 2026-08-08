
const SUPPLIERS = [];
let allIndents=[],currentPage=1,selectedIds=new Set(),filteredData=[];
const PER_PAGE=12;
const modal=new bootstrap.Modal(document.getElementById('indentModal'));
const dispatchModal=new bootstrap.Modal(document.getElementById('dispatchModal'));

document.addEventListener('DOMContentLoaded',()=>{
  loadIndents();
  loadHistory();
  ['searchInput','statusFilter'].forEach(id=>document.getElementById(id).addEventListener(id==='searchInput'?'input':'change',()=>{currentPage=1;renderTable();}));
});

async function loadIndents(){
  try{
    const res=await phGet(API_BASE+'pharmacy/indents');
    if(res.success){allIndents=res.data;renderTable();updateActiveBadge();}
    else PH.error(res.message);
  }catch(e){PH.error('Network error');}
}

function updateCompanyName(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const companyInput = document.getElementById('company_name');
    const emailInput = document.getElementById('notify_email');
    
    if (selectedOption && selectedOption.value) {
        companyInput.value = selectedOption.getAttribute('data-company') || '';
        if (emailInput && !emailInput.value) { // Auto-fill email only if it's currently empty
            emailInput.value = selectedOption.getAttribute('data-email') || '';
        }
    } else {
        companyInput.value = '';
    }
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const sf = document.getElementById('statusFilter').value;
    
    filteredData = allIndents.filter(ind => {
        if (q && !((ind.indent_no||'').toLowerCase().includes(q) || (ind.item_name||'').toLowerCase().includes(q) || (ind.department||'').toLowerCase().includes(q))) return false;
        if (sf && ind.status !== sf) return false;
        return true;
    });

    const pager = phPaginate(filteredData, currentPage, PER_PAGE);
    document.getElementById('tableInfo').textContent = `Showing ${pager.items.length} of ${filteredData.length} records`;
    
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="7" class="text-center py-5 text-muted">No records found matching your filters.</td></tr>`;
    } else {
        pager.items.forEach(i => {
            const isSelected = selectedIds.has(i.id);
            const status = (i.status || 'pending').toLowerCase();
            
            // Workflow Stepper
            const steps = ['pending', 'approved', 'ordered', 'received'];
            const curIdx = steps.indexOf(status);
            let stepper = '<div class="stepper">';
            steps.forEach((s, idx) => stepper += `<div class="step ${idx <= curIdx ? 'active' : ''}" title="${s.toUpperCase()}"></div>`);
            stepper += '</div>';

            html += `
            <tr class="indent-row ${isSelected ? 'selected' : ''}" onclick="toggleRow(${i.id}, !selectedIds.has(${i.id}))">
                <td><input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleRow(${i.id}, this.checked)" style="width:20px; height:20px; accent-color: var(--proc-primary);"></td>
                <td>
                    <div style="font-weight: 800; color: var(--proc-slate); font-size: 0.95rem;">${i.indent_no}</div>
                    <div style="font-size: 0.75rem; color: #94A3B8; font-weight: 600; margin-top: 4px;"><i class="far fa-calendar-alt me-1"></i>${fmt.date(i.request_date)}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #475569;">${i.item_name}</div>
                    <div style="font-size: 0.75rem; color: var(--proc-primary); font-weight: 700; margin-top: 4px;">Dept: ${i.department || 'Pharmacy'}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #64748B; font-size: 0.85rem;">${i.company_name || 'N/A'}</div>
                    <div style="font-size: 0.7rem; color: #94A3B8; margin-top: 4px;">ID: ${i.supplier_id || 'ÃŽâ€œÃƒâ€¡ÃƒÂ¶'}</div>
                </td>
                <td>
                    <input type="number" class="inline-qty" value="${i.qty}" onclick="event.stopPropagation()" onchange="updateQty(${i.id}, this.value)">
                    <div class="mt-2">${priorityBadge(i.priority)}</div>
                </td>
                <td>
                    <div style="font-weight: 800; color: var(--proc-slate); font-size: 0.75rem; text-transform: uppercase;">${status}</div>
                    ${stepper}
                </td>
                <td class="text-end" onclick="event.stopPropagation()">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="ph-btn ph-btn-sm ph-btn-outline" style="border-radius:12px; width:40px; height:40px;" onclick='editIndent(${JSON.stringify(i).replace(/'/g, "&apos;")})'><i class="fas fa-pencil-alt"></i></button>
                        <button class="ph-btn ph-btn-sm" style="background: #0F172A; color: white; border-radius:12px; width:40px; height:40px;" onclick="sendToVendor(${i.id})"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    }
    document.getElementById('indentsBody').innerHTML = html;
    phRenderPager(document.getElementById('pager'), pager.pages, currentPage, p => { currentPage = p; renderTable(); });
    updateBulkBar();
}

function priorityBadge(p) {
    const map = { urgent: ['#FEE2E2','#B91C1C','URGENT'], high: ['#FFEDD5','#9A3412','HIGH'], medium: ['#FEF9C3','#92400E','MEDIUM'], low: ['#DCFCE7','#15803D','LOW'] };
    const c = map[(p||'medium').toLowerCase()];
    return `<span style="background:${c[0]}; color:${c[1]}; padding: 4px 10px; border-radius: 20px; font-size: 0.6rem; font-weight: 800;">${c[2]}</span>`;
}

function toggleRow(id, checked) {
    if (checked) selectedIds.add(id); else selectedIds.delete(id);
    renderTable();
}

function toggleSelectAll(cb) {
    filteredData.forEach(i => cb.checked ? selectedIds.add(i.id) : selectedIds.delete(i.id));
    renderTable();
}

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    if (selectedIds.size > 0) {
        bar.style.display = 'flex';
        bar.classList.add('animate-slide-up');
        document.getElementById('selectedCount').innerHTML = `<i class="fas fa-check-circle me-2" style="color:#10B981"></i> ${selectedIds.size} Selected`;
    } else bar.style.display = 'none';
}

async function updateQty(id, qty) {
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/update-qty', { id: id, qty: qty });
        if (res.success) { PH.success('Quantity updated'); loadIndents(); }
        else PH.error(res.message);
    } catch (e) { PH.error('Sync failed'); }
}

async function bulkChangeStatus(status) {
    if (!selectedIds.size) return;
    try {
        const res = await phPost(API_BASE + 'pharmacy/indents/bulk-status', { ids: Array.from(selectedIds), status: status });
        if (res.success) { PH.success('Batch updated'); selectedIds.clear(); loadIndents(); }
        else PH.error(res.message);
    } catch (e) { PH.error('Sync failed'); }
}

async function bulkDelete() {
    if (!selectedIds.size) return;
    PH.confirm('Delete Selected?', `Permanently remove ${selectedIds.size} requisitions?`, async () => {
        try {
            const res = await phPost(API_BASE + 'pharmacy/indents/bulk-delete', { ids: Array.from(selectedIds) });
            if (res.success) { PH.success('Deleted'); selectedIds.clear(); loadIndents(); }
            else PH.error(res.message);
        } catch (e) { PH.error('Delete failed'); }
    });
}

function openIndentModal(){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value='';
  document.getElementById('modalTitle').textContent='New Indent Request';
  document.getElementById('department').value='Pharmacy Store';
  document.getElementById('status').value='pending';
  modal.show();
}

function editIndent(i){
  document.getElementById('indentForm').reset();
  document.getElementById('id').value=i.id;
  document.getElementById('modalTitle').textContent='Edit Indent';
  ['department','requested_by','product_id','item_name','qty','priority','status','remarks', 'supplier_id', 'company_name'].forEach(f=>{if(document.getElementById(f))document.getElementById(f).value=i[f]||'';});
  modal.show();
}

async function saveIndent(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target).entries());
  PH.loading('Saving...');
  try {
    const res = await fetch(API_BASE + 'pharmacy/indents', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    }).then(r => r.json());
    if (res.success) {
      // If notify email provided, send it after saving
      if (data.notify_email && data.notify_email.trim() !== '') {
        await sendEmailFor(data.notify_email, data.item_name, res.indent_no || data.id || '');
      }
      PH.success(res.message);
      modal.hide();
      loadIndents();
    } else {
      PH.error(res.message);
    }
  } catch (err) {
    PH.error('Failed to save. Please try again.');
  }
}

// Send a quick notification email after saving an indent
async function sendEmailFor(toEmail, itemName, indentRef) {
  const subject = `[QUOTATION REQUEST] New Pharmacy Indent: ${indentRef}`;
  const bodyText = `Dear Partner,\n\nA new procurement requisition has been raised for <strong>${itemName}</strong> (Ref: ${indentRef}).\n\nKindly review the requirements and submit your quotation through our digital portal using the link below.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;
  const htmlBody = `
    <div style="font-family: 'Segoe UI', sans-serif; color: #334155; max-width: 800px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div style="background: #0EA5E9; padding: 30px; text-align: center;">
        <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 800;">Pharmacy Procurement Requisition</h2>
        <p style="color: #BAE6FD; margin: 5px 0 0; font-size: 14px; font-weight: 600;">GM Hospital Management System</p>
      </div>
      <div style="padding: 40px; background: white;">
        <div style="font-size: 16px; line-height: 1.6; color: #475569; margin-bottom: 30px;">${bodyText.replace(/\n/g,'<br>')}</div>
        <div style="text-align: center;">
          <a href="${window.location.origin}/GM_HMS/vendor/vendor_view/login.php?indent_no=${indentRef}&branch=""" 
             style="background: #0F172A; color: white; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: 700; display: inline-block;">
             ACCESS VENDOR PORTAL
          </a>
        </div>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
          This is an automated system notification. Please do not reply directly.
        </div>
      </div>
    </div>`;
  try {
    await fetch('send_email.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email_to: toEmail, subject, body: htmlBody })
    });
  } catch (e) { /* Silent fail ÃŽâ€œÃƒâ€¡ÃƒÂ¶ main save already succeeded */ }
}

function deleteIndent(id){
  PH.confirm('Delete Indent Request?','This cannot be undone.',async()=>{
    const res=await fetch(API_BASE+'pharmacy/indents/'+id,{method:'DELETE'}).then(r=>r.json());
    if(res.success){PH.success('Deleted');loadIndents();}else PH.error(res.message);
  });
}

function autoGenerateIndent() {
  PH.confirm(
    'Auto-Generate Indents?',
    'Draft indent requests will be created for all low-stock items without existing pending/approved indents.',
    async () => {
      PH.loading('Generating drafts...');
      try {
        const r   = await fetch(API_BASE + 'pharmacy/indents/auto-generate', { method: 'POST' });
        const res = await r.json();
        const msg = res.message || res.error || 'Unknown response';
        if (res.success) {
          PH.success(msg);
          loadIndents();
        } else {
          PH.error(msg);
        }
      } catch (e) {
        PH.error('Network error. Could not reach the server.');
      }
    },
    'Yes, Generate'
  );
}

// -- EMAIL ----------------------------------------------------------
function generateHtmlTable(items){
  const rows = items.map(i => {
    return `
    <tr>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #475569;">${i.indent_no}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #1e293b;">${i.item_name}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; text-align:center; color: #475569;">${i.qty}</td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 12px; text-align:center;">
        <span style="background-color: ${i.priority==='high'||i.priority==='urgent'?'#fee2e2':'#f1f5f9'}; color: ${i.priority==='high'||i.priority==='urgent'?'#991b1b':'#475569'}; padding: 4px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
          ${i.priority}
        </span>
      </td>
      <td style="border:1px solid #e2e8f0; padding:12px; font-family: sans-serif; font-size: 14px; color: #475569;">${i.company_name || 'N/A'}</td>
    </tr>`;
  }).join('');

  return `
    <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #ffffff; border: 1px solid #e2e8f0;">
      <thead>
        <tr style="background-color: #f8fafc;">
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Indent No</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Item Name</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Qty</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:center; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Priority</th>
          <th style="border:1px solid #e2e8f0; padding:12px; text-align:left; font-family: sans-serif; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Company</th>
        </tr>
      </thead>
      <tbody>
        ${rows}
      </tbody>
    </table>`;
}

let currentEmailItems = [];

const DEFAULT_EMAIL_MSG = `Dear Manager,\n\nI hope you are doing well.\n\nThis is to inform you that a pharmacy indent request has been generated and is pending your approval. Kindly review the request and take the necessary action at your earliest convenience.\n\nYour prompt approval will help ensure smooth pharmacy operations and avoid any stock shortages.\n\nThank you.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;

function quickEmail(id){
  const item=allIndents.find(i=>i.id===id);
  if(!item)return;
  currentEmailItems = [item];
  document.getElementById('emailTo').value='';
  document.getElementById('emailSubject').value=`[APPROVAL REQUIRED] Pharmacy Indent Request: ${item.indent_no}`; 
  document.getElementById('emailBody').value=DEFAULT_EMAIL_MSG;
  dispatchModal.show();
}

function bulkSendEmail(){
  if(!selectedIds.size)return;
  currentEmailItems = allIndents.filter(i=>selectedIds.has(i.id));
  
  const uniqueSuppliers = [...new Set(currentEmailItems.map(i => i.supplier_id).filter(Boolean))];
  const dispatchModalEl = document.getElementById('dispatchModal');
  const recipientBlock = document.getElementById('recipientBlock');
  const smartDispatchBlock = document.getElementById('smartDispatchBlock');

  document.getElementById('emailTo').value='';
  document.getElementById('customEmail').value = '';

  if (uniqueSuppliers.length > 1) {
      // Smart Dispatch Mode
      dispatchModalEl.dataset.smartMode = "true";
      recipientBlock.style.display = 'none';
      smartDispatchBlock.style.display = 'block';
      smartDispatchBlock.innerHTML = `
        <div class="alert alert-info py-3 mb-3" style="border-radius:12px; border: 1px solid #BAE6FD;">
            <div class="d-flex align-items-center">
                <i class="fas fa-magic fa-2x text-primary me-3"></i>
                <div>
                    <h6 class="mb-1 text-primary fw-bold">Smart Dispatch Mode</h6>
                    <p class="mb-0 small text-secondary">You have selected items assigned to <strong>${uniqueSuppliers.length} different vendors</strong>. The system will automatically group the items and send separate, customized emails to each vendor.</p>
                </div>
            </div>
        </div>
      `;
      document.getElementById('emailSubject').value = `[APPROVAL REQUIRED] Pharmacy Indent Requests`;
      document.getElementById('emailBody').value = `Dear Partner,\n\nPlease find attached the pharmacy indent requests assigned to your company.\nKindly review the requirements and submit your quotation through our digital portal.\n\nBest Regards,\nPharmacy Department\nGM Hospital`;
  } else {
      // Normal Mode (0 or 1 supplier)
      dispatchModalEl.dataset.smartMode = "false";
      recipientBlock.style.display = 'block';
      smartDispatchBlock.style.display = 'none';
      
      const firstItemWithSupplier = currentEmailItems.find(i => i.supplier_id);
      if (firstItemWithSupplier) {
          const vendor = SUPPLIERS.find(s => s.supplier_id == firstItemWithSupplier.supplier_id);
          if (vendor && vendor.email) {
              document.getElementById('emailTo').value = vendor.email;
          }
      }
      document.getElementById('emailSubject').value=`[APPROVAL REQUIRED] Pending Pharmacy Indent Requests (${currentEmailItems.length})`;
      document.getElementById('emailBody').value=DEFAULT_EMAIL_MSG.replace('a pharmacy indent request has', currentEmailItems.length + ' pharmacy indent requests have');
  }

  dispatchModal.show();
}

// Reusable email template builder
function buildEmailTemplate(message, tableHtml, firstIndentNo) {
    return `
    <div style="font-family: 'Segoe UI', sans-serif; color: #334155; max-width: 800px; margin: 20px auto; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
      <div style="background: #0EA5E9; padding: 30px; text-align: center;">
        <h2 style="color: white; margin: 0; font-size: 24px; font-weight: 800;">Pharmacy Procurement Requisition</h2>
        <p style="color: #BAE6FD; margin: 5px 0 0; font-size: 14px; font-weight: 600;">GM Hospital Management System</p>
      </div>
      <div style="padding: 40px; background: white;">
        <div style="font-size: 16px; line-height: 1.6; color: #475569; margin-bottom: 30px;">${message}</div>
        <div style="margin-bottom: 30px;">${tableHtml}</div>
        <div style="text-align: center;">
          <a href="${window.location.origin}/GM_HMS/vendor/vendor_view/login.php?indent_no=${firstIndentNo}&branch=""" 
             style="background: #0F172A; color: white; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: 700; display: inline-block;">
             ACCESS VENDOR PORTAL
          </a>
        </div>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #F1F5F9; font-size: 12px; color: #94A3B8; text-align: center;">
          This is an automated system notification. Please do not reply directly.
        </div>
      </div>
    </div>
    `;
}

async function sendEmailNow(){
  const isSmartMode = document.getElementById('dispatchModal').dataset.smartMode === "true";
  const subject = document.getElementById('emailSubject').value.trim();
  const message = document.getElementById('emailBody').value.trim().replace(/\n/g, '<br>');
  
  if (isSmartMode) {
      PH.loading('Dispatching multiple emails...');
      
      // Group items by supplier_id
      const groups = {};
      currentEmailItems.forEach(item => {
          const sid = item.supplier_id || 'unassigned';
          if(!groups[sid]) groups[sid] = [];
          groups[sid].push(item);
      });
      
      let successCount = 0;
      let failCount = 0;
      
      for (const [sid, items] of Object.entries(groups)) {
          if (sid === 'unassigned') continue; // Skip unassigned items in smart mode
          
          const vendor = SUPPLIERS.find(s => s.supplier_id == sid);
          if (!vendor || !vendor.email) {
              failCount++;
              continue;
          }
          
          const tableHtml = generateHtmlTable(items);
          const fullHtmlBody = buildEmailTemplate(message, tableHtml, items[0].indent_no);
          
          try {
              const res = await fetch('send_email.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  credentials: 'include',
                  body: JSON.stringify({ email_to: vendor.email, subject: subject, body: fullHtmlBody })
              }).then(r => r.json());
              
              if (res.success) {
                  successCount++;
                  try {
                      await fetch(API_BASE + 'pharmacy/indents/bulk-status', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          body: JSON.stringify({ ids: items.map(i => i.id), status: 'ordered' })
                      });
                  } catch(e) {}
              }
              else failCount++;
          } catch(e) { failCount++; }
      }
      
      dispatchModal.hide();
      if (failCount === 0) PH.success(`Successfully dispatched ${successCount} emails.`);
      else if (successCount > 0) PH.success(`Sent ${successCount} emails. (${failCount} failed or missing vendor emails).`);
      else PH.error('Failed to send emails. Ensure selected vendors have valid email addresses.');
      
      if (successCount > 0) loadIndents();
      
  } else {
      // Normal Single-Email Mode
      const selectEl = document.getElementById('emailTo');
      const customTo = document.getElementById('customEmail').value.trim();
      const to = customTo || selectEl.value;
      
      if(!to){PH.error('Please select or enter a recipient email'); return;}
      
      let newSupplierId = '';
      let newCompanyName = '';
      if (!customTo && selectEl.selectedIndex > 0) {
          const opt = selectEl.options[selectEl.selectedIndex];
          newSupplierId = opt.getAttribute('data-id');
          newCompanyName = opt.getAttribute('data-name');
      }

      // Automatically assign this vendor to these indents in the database
      if (newSupplierId) {
          try {
              await fetch(API_BASE + 'pharmacy/indents/bulk-assign', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({
                      ids: currentEmailItems.map(i => i.id),
                      supplier_id: newSupplierId,
                      company_name: newCompanyName,
                      email: to
                  })
              });
              // Update local state so UI doesn't require refresh
              currentEmailItems.forEach(i => {
                  i.supplier_id = newSupplierId;
                  i.company_name = newCompanyName;
              });
          } catch (e) {
              console.warn("Failed to auto-assign vendor", e);
          }
      }
      
      const tableHtml = generateHtmlTable(currentEmailItems);
      const fullHtmlBody = buildEmailTemplate(message, tableHtml, currentEmailItems[0].indent_no);
      
      PH.loading('Dispatching Requisition...');
      try {
        const res = await fetch('send_email.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ email_to: to, subject: subject, body: fullHtmlBody })
        }).then(r => r.json());
        
        if (res.success) {
          PH.success('Notification sent to ' + to);
          dispatchModal.hide();
          try {
              await fetch(API_BASE + 'pharmacy/indents/bulk-status', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ ids: currentEmailItems.map(i => i.id), status: 'ordered' })
              });
          } catch(e) {}
          loadIndents();
        } else PH.error(res.message);
      } catch(e) { PH.error('Dispatch failed'); }
  }
}

function sendToVendor(id) {
    const item = allIndents.find(i => i.id === id);
    if (!item) return;
    
    // Group all items that share the exact same indent_no
    currentEmailItems = allIndents.filter(i => i.indent_no === item.indent_no);
    
    // Auto-fill logic
    document.getElementById('emailTo').value = '';
    document.getElementById('customEmail').value = '';
    
    if (item.supplier_id) {
        const vendor = SUPPLIERS.find(s => s.supplier_id == item.supplier_id);
        if (vendor && vendor.email) {
            document.getElementById('emailTo').value = vendor.email;
        }
    }
    
    document.getElementById('emailSubject').value = `[QUOTATION REQUEST] Requisition ${item.indent_no}`;
    document.getElementById('emailBody').value = `Dear Partner,\n\nPlease find our latest procurement requisition (${item.indent_no}) for ${item.item_name}. \n\nKindly review the requirements and submit your quotation through our digital portal using the link below.\n\nBest Regards,\nGM Hospital Procurement Team`;
    dispatchModal.show();
}
// -- EXPORT ---------------------------------------------------------
function exportCSV(){
  const data=filteredData.length?filteredData:allIndents;
  const cols=['indent_no','request_date','request_time','item_name','qty','priority','status','department','requested_by','supplier_id','company_name','remarks'];
  const hdr=cols.join(',');
  const rows=data.map(r=>cols.map(c=>JSON.stringify(r[c]||'')).join(','));
  const csv='data:text/csv;charset=utf-8,'+[hdr,...rows].join('\n');
  const a=document.createElement('a');a.href=encodeURI(csv);a.download='indent_requests_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
  PH.success('CSV exported!');
}

function exportPrint(){
  const data=filteredData.length?filteredData:allIndents;
  const rows=data.map(r=>`<tr>
    <td>${r.indent_no}</td><td>${fmt.date(r.request_date)} ${r.request_time||''}</td><td>${r.item_name}</td>
    <td>${r.qty}</td><td>${r.priority}</td><td>${r.company_name||''}</td><td>${r.status}</td>
    <td>${r.department||''}</td><td>${r.requested_by||''}</td>
  </tr>`).join('');
  const html=`<!DOCTYPE html><html><head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
<title>Indent Requests</title>
  <style>body{font-family:Arial,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#1f6b4a;color:#fff}tr:nth-child(even){background:#f9f9f9}h2{color:#0F172A}</style>
  </head><body>
  <h2>Indent Requests Report</h2><p>Generated: ${new Date().toLocaleString()} | Total: ${data.length}</p>
  <table><thead><tr><th>Indent No</th><th>Date & Time</th><th>Item</th><th>Qty</th><th>Priority</th><th>Company</th><th>Status</th><th>Department</th><th>Requested By</th></tr></thead>
  <tbody>${rows}</tbody></table>
  <script>window.onload=()=>window.print()<\/script></body></html>`;
  const w=window.open('','_blank','width=1000,height=700');w.document.write(html);w.document.close();
}

// Ã¢â€â‚¬Ã¢â€â‚¬ HISTORY LOAD & RENDER Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let historyIndents = [], historyCurrentPage = 1, historySelectedIds = new Set(), historyFilteredData = [];

async function loadHistory(){
  try{
    const res=await phGet(API_BASE+'pharmacy/indents/history');
    if(res.success){historyIndents=res.data;renderHistoryTable();updateHistoryBadge();}
    else PH.error(res.message);
  }catch(e){PH.error('Network error');}
}

function renderHistoryTable() {
    const q = (document.getElementById('historySearchInput').value||'').toLowerCase();
    historyFilteredData = historyIndents.filter(ind => {
        if (q && !((ind.indent_no||'').toLowerCase().includes(q)||(ind.item_name||'').toLowerCase().includes(q)||(ind.department||'').toLowerCase().includes(q)||(ind.company_name||'').toLowerCase().includes(q))) return false;
        return true;
    });
    const pager = phPaginate(historyFilteredData, historyCurrentPage, PER_PAGE);
    document.getElementById('historyTableInfo').textContent = `Showing ${pager.items.length} of ${historyFilteredData.length} records`;
    let html = '';
    if (!pager.items.length) {
        html = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-history" style="font-size:2rem;color:#CBD5E1;display:block;margin-bottom:10px;"></i>No dispatched indents yet.</td></tr>`;
    } else {
        pager.items.forEach(i => {
            const isSel = historySelectedIds.has(i.id);
            const method = i.communication_method || 'Manual';
            const sentBy = i.sent_by || 'Unknown';
            const pri = (i.priority||'').toLowerCase();
            const pColor = pri==='urgent'?'#EF4444':pri==='high'?'#F59E0B':pri==='medium'?'#0EA5E9':'#10B981';
            const mIcon = method==='WhatsApp'?'fab fa-whatsapp':method==='Phone'?'fas fa-phone-alt':'fas fa-envelope';
            html += `<tr class="indent-row ${isSel?'selected':''}" onclick="toggleHistoryRow(${i.id},!historySelectedIds.has(${i.id}))">
                <td><input type="checkbox" ${isSel?'checked':''} onclick="event.stopPropagation();toggleHistoryRow(${i.id},this.checked)" style="width:20px;height:20px;accent-color:#059669;"></td>
                <td><div style="font-weight:800;color:var(--proc-slate);font-size:0.9rem;">${i.indent_no}</div>
                    <div style="font-size:0.7rem;color:#94A3B8;font-weight:600;margin-top:3px;"><i class="far fa-calendar-alt me-1"></i>${fmt.date(i.request_date)}</div>
                    <div style="font-size:0.7rem;color:#94A3B8;margin-top:2px;"><i class="fas fa-building me-1"></i>${i.department||'N/A'}</div></td>
                <td><div style="font-weight:700;color:#1E293B;">${i.item_name}</div>
                    <div style="font-size:0.75rem;font-weight:700;margin-top:3px;"><span style="color:var(--proc-primary);">Qty: ${i.qty}</span> &nbsp;&middot;&nbsp; <span style="color:${pColor};text-transform:uppercase;font-size:0.65rem;">${i.priority||'N/A'}</span></div>
                    <div style="font-size:0.7rem;color:#94A3B8;margin-top:2px;">By: ${i.requested_by||'N/A'}</div></td>
                <td><div style="font-weight:700;color:#334155;font-size:0.85rem;">${i.company_name||'<span style="color:#CBD5E1;">Unassigned</span>'}</div>
                    <div style="font-size:0.7rem;color:#94A3B8;margin-top:2px;">${i.email||''}</div></td>
                <td><span style="display:inline-flex;align-items:center;gap:5px;background:#F0FDF4;color:#059669;border-radius:20px;padding:4px 10px;font-size:0.75rem;font-weight:800;"><i class="${mIcon}"></i>${method}</span>
                    <div style="font-size:0.7rem;color:#94A3B8;margin-top:4px;">By: <strong>${sentBy}</strong></div></td>
                <td style="font-size:0.8rem;font-weight:700;color:#475569;">${i.remarks||'Ã¢â‚¬â€'}</td>
                <td class="text-end" onclick="event.stopPropagation()">
                    <button class="ph-btn ph-btn-sm ph-btn-outline" style="border-radius:10px;width:36px;height:36px;" onclick='viewHistoryIndent(${JSON.stringify(i).replace(/'/g,"&apos;")})'><i class="fas fa-eye"></i></button>
                </td></tr>`;
        });
    }
    document.getElementById('historyBody').innerHTML = html;
    phRenderPager(document.getElementById('historyPager'), pager.pages, historyCurrentPage, p => { historyCurrentPage = p; renderHistoryTable(); });
    updateHistoryBulkBar();
}

function toggleHistoryRow(id, checked) { if(checked) historySelectedIds.add(id); else historySelectedIds.delete(id); renderHistoryTable(); }
function toggleHistorySelectAll(cb) { historyFilteredData.forEach(i => cb.checked ? historySelectedIds.add(i.id) : historySelectedIds.delete(i.id)); renderHistoryTable(); }

function updateHistoryBulkBar() {
    const bar = document.getElementById('historyBulkBar'); if(!bar) return;
    if(historySelectedIds.size>0){bar.style.display='flex';document.getElementById('historySelectedCount').innerHTML=`<i class="fas fa-check-circle me-2" style="color:#10B981"></i> ${historySelectedIds.size} Selected`;}
    else bar.style.display='none';
}

async function bulkRevertSent() {
    if(!historySelectedIds.size) return;
    PH.confirm('Un-send Selected?',`Revert ${historySelectedIds.size} indent(s) back to Active Workspace?`,async()=>{
        const res = await phPost(API_BASE+'pharmacy/indents/revert-sent',{ids:Array.from(historySelectedIds)});
        if(res.success){PH.success('Reverted successfully');historySelectedIds.clear();loadHistory();loadIndents();}
        else PH.error(res.message);
    });
}

function viewHistoryIndent(i) {
    PH.alert('Indent Details',`<strong>Indent No:</strong> ${i.indent_no}<br><strong>Item:</strong> ${i.item_name} (Qty: ${i.qty})<br><strong>Dept:</strong> ${i.department||'N/A'}<br><strong>Supplier:</strong> ${i.company_name||'N/A'}<br><strong>Sent Via:</strong> ${i.communication_method}<br><strong>Sent By:</strong> ${i.sent_by}<br><strong>Date:</strong> ${fmt.date(i.request_date)}`);
}

// Ã¢â€â‚¬Ã¢â€â‚¬ WhatsApp & Phone dispatch Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
async function sendWhatsappNow() {
    const to = (document.getElementById('whatsappTo').value.trim() || document.getElementById('customWhatsapp').value.trim()).replace(/[^0-9]/g,'');
    if(!to){PH.error('Please enter a WhatsApp number');return;}
    const vendor = (currentEmailItems[0]||{}).company_name || 'Supplier';
    const itemLines = currentEmailItems.map(i=>`  - ${i.item_name} | Qty: ${i.qty} | Priority: ${(i.priority||'').toUpperCase()}`).join('\n');
    const msg = `*GM Hospital - Procurement Requisition*\n\nDear ${vendor},\n\nKindly arrange the following items:\n\n${itemLines}\n\nRef No: ${(currentEmailItems[0]||{}).indent_no||''}\nDate: ${new Date().toLocaleDateString()}\n\nPlease confirm receipt.\n\nÃ¢â‚¬â€œ GM Hospital Pharmacy`;
    document.getElementById('whatsappBody').value = msg;
    const url = `https://wa.me/${to}?text=${encodeURIComponent(msg)}`;
    window.open(url,'_blank');
    try{
        const res=await phPost(API_BASE+'pharmacy/indents/mark-sent',{ids:currentEmailItems.map(i=>i.id),communication_method:'WhatsApp'});
        if(res.success){PH.success('Marked as sent via WhatsApp');dispatchModal.hide();loadIndents();loadHistory();}
        else PH.error(res.message);
    }catch(e){PH.error('Failed to update status');}
}

async function markPhoneSentNow() {
    PH.loading('Marking as sent via Phone...');
    try{
        const res=await phPost(API_BASE+'pharmacy/indents/mark-sent',{ids:currentEmailItems.map(i=>i.id),communication_method:'Phone'});
        if(res.success){PH.success('Marked as informed by Phone');dispatchModal.hide();loadIndents();loadHistory();}
        else PH.error(res.message);
    }catch(e){PH.error('Failed to update status');}
}

// Ã¢â€â‚¬Ã¢â€â‚¬ PDF Export for selected indents Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function exportSelectedPDF() {
    const data = selectedIds.size > 0 ? allIndents.filter(i=>selectedIds.has(i.id)) : filteredData.length ? filteredData : allIndents;
    if(!data.length){PH.error('No items to export');return;}
    const rows = data.map(r=>`<tr>
        <td>${r.indent_no}</td><td>${fmt.date(r.request_date)}</td><td>${r.item_name}</td>
        <td>${r.qty}</td><td>${(r.priority||'').toUpperCase()}</td><td>${r.company_name||'N/A'}</td>
        <td>${(r.status||'').toUpperCase()}</td><td>${r.department||'N/A'}</td><td>${r.requested_by||'N/A'}</td>
    </tr>`).join('');
    const html=`<!DOCTYPE html><html><head><title>Indent Requests</title>
    <style>body{font-family:Arial,sans-serif;font-size:11px;padding:20px;}
    h2{color:#0F172A;margin-bottom:5px;}p{color:#64748B;font-size:11px;margin-bottom:15px;}
    table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left;}
    th{background:#0EA5E9;color:#fff;font-weight:700;text-transform:uppercase;font-size:10px;}
    tr:nth-child(even){background:#F8FAFC;}.logo{font-size:18px;font-weight:900;color:#0EA5E9;}
    @media print{button{display:none;}}</style></head><body>
    <div class="logo">GM Hospital</div>
    <h2>Pharmacy Indent Requests</h2>
    <p>Generated: ${new Date().toLocaleString()} &nbsp;|&nbsp; Total: ${data.length} item(s)${selectedIds.size>0?' &nbsp;|&nbsp; <strong>Selected items only</strong>':''}</p>
    <table><thead><tr><th>Indent No</th><th>Date</th><th>Item</th><th>Qty</th><th>Priority</th><th>Company</th><th>Status</th><th>Department</th><th>Requested By</th></tr></thead>
    <tbody>${rows}</tbody></table>
    <script>window.onload=()=>window.print()<\/script></body></html>`;
    const w=window.open('','_blank','width=1100,height=750');w.document.write(html);w.document.close();
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Tab Switcher Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let currentTab = 'active';
function switchWorkspaceTab(tab) {
    currentTab = tab;
    const aP=document.getElementById('panel-active-workspace'), hP=document.getElementById('panel-sent-history');
    const bA=document.getElementById('tab-btn-active'), bH=document.getElementById('tab-btn-history');
    const bdA=document.getElementById('badge-active'), bdH=document.getElementById('badge-history');
    if(tab==='active'){
        aP.style.display='';hP.style.display='none';
        bA.style.background='var(--proc-primary)';bA.style.color='#fff';
        bdA.style.background='rgba(255,255,255,0.25)';bdA.style.color='#fff';
        bH.style.background='transparent';bH.style.color='#64748B';
        bdH.style.background='#F1F5F9';bdH.style.color='#64748B';
        const hBar=document.getElementById('historyBulkBar');if(hBar)hBar.style.display='none';
        if(typeof historySelectedIds!=='undefined')historySelectedIds.clear();
    } else {
        aP.style.display='none';hP.style.display='';
        bH.style.background='#059669';bH.style.color='#fff';
        bdH.style.background='rgba(255,255,255,0.25)';bdH.style.color='#fff';
        bA.style.background='transparent';bA.style.color='#64748B';
        bdA.style.background='#F1F5F9';bdA.style.color='#64748B';
        const aBar=document.getElementById('bulkBar');if(aBar)aBar.style.display='none';
        selectedIds.clear();loadHistory();
    }
}
function updateActiveBadge(){const b=document.getElementById('badge-active');if(b)b.textContent=allIndents.length;}
function updateHistoryBadge(){const b=document.getElementById('badge-history');if(b)b.textContent=historyIndents.length;}

document.addEventListener('DOMContentLoaded',()=>{
    const hsi=document.getElementById('historySearchInput');
    if(hsi)hsi.addEventListener('input',()=>{historyCurrentPage=1;renderHistoryTable();});
});

