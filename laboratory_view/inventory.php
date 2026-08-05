<?php
$pageTitle = 'Inventory';
$pageIcon  = 'fa-boxes';
$navTitle  = 'Reagent & Inventory';
$navSub    = 'Stock levels, expiry dates and supplier management';
require_once 'includes/lab_head.php';
?>
<?php require_once 'includes/lab_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/lab_navbar.php'; ?>

<div class="lis-content">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up">
    <div>
      <div class="lis-page-title">
        <div class="lis-page-title-icon"><i class="fas fa-boxes"></i></div>
        <div>
          Inventory Management
          <div class="lis-page-subtitle">Reagents, consumables, stock levels and expiry alerts</div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="lis-btn lis-btn-outline" onclick="filterInventory('')">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
      <button class="lis-btn lis-btn-primary" onclick="alert('Feature: Add Item — Connect to Inventory API')">
        <i class="fas fa-plus"></i> Add Item
      </button>
    </div>
  </div>

  <!-- Inventory KPIs -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;" class="lis-fade-up-1">
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-green" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-boxes"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="inv-total" style="font-size:1.6rem;">24</div>
        <div class="lis-kpi-label">Total Items</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-amber" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-exclamation-triangle"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="inv-low" style="font-size:1.6rem;color:var(--lis-warning);">4</div>
        <div class="lis-kpi-label">Low Stock</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-red" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-calendar-times"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="inv-expiring" style="font-size:1.6rem;color:var(--lis-danger);">2</div>
        <div class="lis-kpi-label">Expiring Soon</div>
      </div>
    </div>
    <div class="lis-kpi-card" style="padding:16px;">
      <div class="lis-kpi-icon c-emerald" style="width:40px;height:40px;font-size:1rem;"><i class="fas fa-check-circle"></i></div>
      <div class="lis-kpi-info">
        <div class="lis-kpi-value" id="inv-ok" style="font-size:1.6rem;">18</div>
        <div class="lis-kpi-label">In Stock OK</div>
      </div>
    </div>
  </div>

  <!-- Search + Filter -->
  <div class="lis-filter-bar lis-fade-up-2">
    <div class="lis-search-wrap" style="flex:1;max-width:300px;">
      <i class="fas fa-search"></i>
      <input type="text" class="lis-input" id="inv-search" placeholder="Search reagents, consumables..." oninput="filterInventory(this.value)">
    </div>
    <span style="font-size:0.68rem;font-weight:800;color:var(--lis-text-muted);text-transform:uppercase;letter-spacing:0.08em;">Category:</span>
    <button class="lis-filter-chip active" onclick="setCat('all',this)">All</button>
    <button class="lis-filter-chip" onclick="setCat('reagent',this)"><i class="fas fa-flask"></i> Reagents</button>
    <button class="lis-filter-chip" onclick="setCat('consumable',this)"><i class="fas fa-box"></i> Consumables</button>
    <button class="lis-filter-chip" onclick="setCat('equipment',this)"><i class="fas fa-tools"></i> Equipment</button>
    <div style="margin-left:auto;">
      <button class="lis-btn lis-btn-outline lis-btn-sm" onclick="window.print()">
        <i class="fas fa-print"></i> Print List
      </button>
    </div>
  </div>

  <!-- Inventory Table -->
  <div class="lis-card lis-fade-up-3">
    <div class="lis-card-header">
      <div class="lis-card-title"><i class="fas fa-table"></i> Inventory Items</div>
      <span class="lis-badge lis-badge-lab" id="inv-count">24 Items</span>
    </div>
    <div class="lis-table-wrap">
      <table class="lis-table" id="invTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Supplier</th>
            <th>Stock Level</th>
            <th>Current / Min</th>
            <th>Expiry Date</th>
            <th>Status</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="invBody"></tbody>
      </table>
    </div>
  </div>

</div>
<?php require_once 'includes/lab_foot.php'; ?>

<script>
const inventoryData = [
  { id:1, name:'EDTA Tubes (3mL)',         cat:'consumable', supplier:'BD Diagnostics',    stock:850, min:200, expiry:'2027-06-30', unit:'pcs' },
  { id:2, name:'CBC Reagent Pack',          cat:'reagent',    supplier:'Sysmex Corp',       stock:12,  min:5,   expiry:'2026-12-15', unit:'kits' },
  { id:3, name:'Glucose Calibrator',        cat:'reagent',    supplier:'Roche Diagnostics', stock:3,   min:5,   expiry:'2026-09-30', unit:'kits' },
  { id:4, name:'Serum Separator Tubes',     cat:'consumable', supplier:'BD Diagnostics',    stock:450, min:100, expiry:'2027-03-15', unit:'pcs' },
  { id:5, name:'HbA1c Reagent',             cat:'reagent',    supplier:'Tosoh Bioscience',  stock:8,   min:3,   expiry:'2026-08-25', unit:'kits' },
  { id:6, name:'Latex Gloves (L)',          cat:'consumable', supplier:'MedLine',           stock:1200,min:500, expiry:'2028-01-01', unit:'pcs' },
  { id:7, name:'Urine Dipsticks (100)',     cat:'reagent',    supplier:'Analyticon',        stock:25,  min:10,  expiry:'2027-05-20', unit:'packs' },
  { id:8, name:'Electrolyte Calibrator',    cat:'reagent',    supplier:'Roche Diagnostics', stock:2,   min:4,   expiry:'2026-09-10', unit:'kits' },
  { id:9, name:'Blood Glucose Strips',      cat:'reagent',    supplier:'Abbott',            stock:400, min:200, expiry:'2027-02-28', unit:'strips' },
  { id:10,name:'Centrifuge Tubes (15mL)',   cat:'consumable', supplier:'Eppendorf',         stock:600, min:200, expiry:'2029-01-01', unit:'pcs' },
  { id:11,name:'PT/APTT Reagent',           cat:'reagent',    supplier:'Stago',             stock:6,   min:4,   expiry:'2026-10-30', unit:'kits' },
  { id:12,name:'CRP Rapid Test Kit',        cat:'reagent',    supplier:'BioMerieux',        stock:18,  min:10,  expiry:'2027-01-15', unit:'kits' },
  { id:13,name:'Lancets (28G)',             cat:'consumable', supplier:'Roche',             stock:900, min:300, expiry:'2028-06-01', unit:'pcs' },
  { id:14,name:'Microscope Slides',         cat:'equipment',  supplier:'Fisher Scientific', stock:200, min:100, expiry: null,       unit:'pcs' },
  { id:15,name:'Immersion Oil',             cat:'equipment',  supplier:'Leica',             stock:8,   min:3,   expiry:'2027-08-01', unit:'bottles' },
  { id:16,name:'Thyroid Panel Reagent',     cat:'reagent',    supplier:'Roche Diagnostics', stock:10,  min:5,   expiry:'2026-11-30', unit:'kits' },
  { id:17,name:'Syringe 5mL',              cat:'consumable', supplier:'B.Braun',           stock:350, min:100, expiry:'2028-01-01', unit:'pcs' },
  { id:18,name:'D-Dimer Reagent',           cat:'reagent',    supplier:'Stago',             stock:4,   min:3,   expiry:'2026-09-15', unit:'kits' },
  { id:19,name:'Sterile Gauze Pads',        cat:'consumable', supplier:'Medline',           stock:500, min:200, expiry:'2028-05-01', unit:'pcs' },
  { id:20,name:'Iron Studies Reagent',      cat:'reagent',    supplier:'Randox',            stock:7,   min:4,   expiry:'2026-12-01', unit:'kits' },
  { id:21,name:'Micropipette Tips 1000µL',  cat:'equipment',  supplier:'Eppendorf',         stock:1000,min:200, expiry: null,       unit:'pcs' },
  { id:22,name:'Westergren ESR Tubes',      cat:'consumable', supplier:'Greiner',           stock:150, min:50,  expiry:'2027-04-01', unit:'pcs' },
  { id:23,name:'Alcohol Swabs',             cat:'consumable', supplier:'Medline',           stock:2000,min:500, expiry:'2028-01-01', unit:'pcs' },
  { id:24,name:'Liver Panel Reagent',       cat:'reagent',    supplier:'Roche Diagnostics', stock:9,   min:5,   expiry:'2026-11-01', unit:'kits' },
];

let currentCat    = 'all';
let currentSearch = '';

function setCat(cat, el) {
  currentCat = cat;
  document.querySelectorAll('.lis-filter-chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  renderInventory();
}
function filterInventory(q) { currentSearch = q.toLowerCase(); renderInventory(); }

function getStockPct(item) { return Math.min(Math.round((item.stock / (item.min * 4)) * 100), 100); }
function getStockStatus(item) {
  if (item.stock < item.min)        return { cls:'low',    label:'Critical Low', badgeCls:'lis-badge-urgent' };
  if (item.stock < item.min * 1.5)  return { cls:'medium', label:'Low Stock',    badgeCls:'lis-badge-processing' };
  return                                   { cls:'good',   label:'In Stock',     badgeCls:'lis-badge-completed' };
}
function getExpiryStatus(expiry) {
  if (!expiry) return null;
  const days = Math.round((new Date(expiry) - new Date()) / 86400000);
  if (days < 0)   return { cls:'lis-badge-urgent', label:`Expired` };
  if (days < 30)  return { cls:'lis-badge-urgent', label:`${days}d left` };
  if (days < 90)  return { cls:'lis-badge-processing', label:`${days}d left` };
  return null;
}

function renderInventory() {
  const body = document.getElementById('invBody');
  const filtered = inventoryData.filter(i => {
    if (currentCat !== 'all' && i.cat !== currentCat) return false;
    if (currentSearch && !i.name.toLowerCase().includes(currentSearch) && !i.supplier.toLowerCase().includes(currentSearch)) return false;
    return true;
  });

  document.getElementById('inv-count').textContent = `${filtered.length} Items`;

  if (!filtered.length) {
    body.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--lis-text-muted);">No inventory items found</td></tr>`;
    return;
  }

  const catIcons = { reagent:'fa-flask', consumable:'fa-box', equipment:'fa-tools' };
  const catColors = { reagent:'lis-badge-lab', consumable:'lis-badge-ordered', equipment:'lis-badge-radiology' };

  body.innerHTML = filtered.map((item, i) => {
    const pct       = getStockPct(item);
    const sts       = getStockStatus(item);
    const expSts    = getExpiryStatus(item.expiry);
    const barColor  = sts.cls === 'low' ? 'var(--lis-danger)' : sts.cls === 'medium' ? 'var(--lis-warning)' : 'var(--lis-success)';

    return `<tr class="inv-row" data-name="${escHtml(item.name.toLowerCase())}" data-cat="${item.cat}">
      <td style="color:var(--lis-text-muted);font-weight:700;">${i+1}</td>
      <td>
        <div style="font-weight:700;font-size:0.82rem;">${escHtml(item.name)}</div>
        <div style="font-size:0.65rem;color:var(--lis-text-muted);">Min: ${item.min} ${item.unit}</div>
      </td>
      <td><span class="lis-badge ${catColors[item.cat]}"><i class="fas ${catIcons[item.cat]}"></i> ${item.cat}</span></td>
      <td style="font-size:0.78rem;">${escHtml(item.supplier)}</td>
      <td style="min-width:120px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
          <span style="font-size:0.65rem;color:var(--lis-text-muted);">${pct}%</span>
        </div>
        <div class="lis-progress-bar-wrap">
          <div class="lis-progress-bar-fill" style="width:${pct}%;background:${barColor};transition:width 1s ease;"></div>
        </div>
      </td>
      <td>
        <span style="font-size:0.85rem;font-weight:800;color:${barColor};">${item.stock}</span>
        <span style="font-size:0.72rem;color:var(--lis-text-muted);"> / ${item.min} ${item.unit}</span>
      </td>
      <td>
        ${item.expiry ? `<span class="lis-code">${item.expiry}</span>
          ${expSts ? `<span class="lis-badge ${expSts.cls}" style="display:block;margin-top:3px;">${expSts.label}</span>` : ''}` : '<span style="color:var(--lis-text-muted);">—</span>'}
      </td>
      <td><span class="lis-badge ${sts.badgeCls}">${sts.label}</span></td>
      <td style="text-align:center;">
        <div style="display:flex;gap:5px;justify-content:center;">
          <button class="lis-btn lis-btn-outline lis-btn-icon lis-btn-sm" title="Edit" onclick="lisToast('Edit inventory — connect to Inventory API','info')">
            <i class="fas fa-edit"></i>
          </button>
          <button class="lis-btn lis-btn-success lis-btn-icon lis-btn-sm" title="Reorder" onclick="lisToast('Reorder initiated for ${escHtml(item.name)}','success')">
            <i class="fas fa-shopping-cart"></i>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

renderInventory();
</script>
