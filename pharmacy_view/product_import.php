<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'Product Import / Export';
include 'includes/ph_head.php';
?>
<style>
.preview-summary-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem;margin-bottom:.75rem;}
.psb-card{background:#fff;border:1.5px solid var(--ph-border);border-radius:10px;padding:.65rem 1rem;display:flex;align-items:center;gap:.65rem;box-shadow:var(--ph-shadow);}
.psb-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
.psb-val{font-size:1.3rem;font-weight:900;line-height:1;}
.psb-lbl{font-size:.62rem;font-weight:700;color:var(--ph-muted);text-transform:uppercase;}
.preview-cards-wrap{display:flex;flex-direction:column;gap:.45rem;max-height:380px;overflow-y:auto;padding-right:2px;}
.preview-cards-wrap::-webkit-scrollbar{width:5px;}
.preview-cards-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px;}
.pv-card{background:#fff;border:1.5px solid var(--ph-border);border-radius:10px;padding:.6rem .85rem;display:grid;grid-template-columns:auto 1fr repeat(4,auto);align-items:center;gap:.6rem 1rem;transition:.15s;}
.pv-card:hover{border-color:var(--ph-primary);box-shadow:0 2px 8px rgba(31, 107, 74,.1);}
.pv-row-num{width:24px;height:24px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:var(--ph-muted);flex-shrink:0;}
.pv-name{font-size:.82rem;font-weight:700;color:var(--ph-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.pv-sub{font-size:.66rem;color:var(--ph-muted);margin-top:.1rem;}
.pv-chip{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .55rem;border-radius:6px;font-size:.68rem;font-weight:700;white-space:nowrap;}
.pv-qty{background:#dbeafe;color:#1d4ed8;}
.pv-batch{background:#F1F5F9;color:var(--ph-muted);}
.pv-mrp{background:#dcfce7;color:#15803d;}
.pv-exp{background:#fef9c3;color:#92400e;}
.pv-exp.danger{background:#fee2e2;color:#b91c1c;}
.pv-exp.ok{background:#dcfce7;color:#15803d;}
.imp-hero{background:#f3efe6;border-radius:14px;padding:.8rem 1.25rem;color:#0f172a;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;}
.imp-hero h1{font-size:1.05rem;font-weight:800;margin:0;color:#1f6b4a;}
.imp-hero p{font-size:.75rem;color:#64748b;margin:.15rem 0 0;}
.step-wizard{display:flex;background:#fff;border:1px solid var(--ph-border);border-radius:12px;overflow:hidden;margin-bottom:1.25rem;box-shadow:var(--ph-shadow);}
.wz-step{flex:1;display:flex;align-items:center;gap:.5rem;padding:.65rem .9rem;border-right:1px solid var(--ph-border);transition:.2s;}
.wz-step:last-child{border-right:none;}
.wz-num{width:24px;height:24px;border-radius:50%;background:#F1F5F9;color:var(--ph-muted);font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.2s;}
.wz-lbl{font-size:.73rem;font-weight:700;color:var(--ph-muted);}
.wz-sub{font-size:.62rem;color:var(--ph-muted);opacity:.65;}
.wz-step.active{background:rgba(31, 107, 74,.06);}
.wz-step.active .wz-num{background:var(--ph-primary);color:#fff;box-shadow:0 3px 10px rgba(31, 107, 74,.4);}
.wz-step.active .wz-lbl{color:var(--ph-primary);}
.wz-step.done .wz-num{background:#10b981;color:#fff;}
.wz-step.done .wz-lbl{color:#10b981;}
.import-zone{border:2px dashed var(--ph-border);border-radius:12px;padding:1.75rem;text-align:center;cursor:pointer;transition:.3s;background:#F8FAFC;position:relative;}
.import-zone:hover,.import-zone.drag{border-color:var(--ph-primary);background:#f0fdfd;}
.import-zone .iz-icon{font-size:2.2rem;color:var(--ph-primary);margin-bottom:.6rem;}
.import-zone h4{font-size:.95rem;font-weight:700;margin-bottom:.25rem;}
.import-zone p{font-size:.76rem;color:var(--ph-muted);margin:0;}
.file-chip{display:inline-flex;align-items:center;gap:.4rem;background:var(--ph-primary-light);color:var(--ph-primary-dark);border-radius:99px;padding:.25rem .75rem;font-size:.75rem;font-weight:700;margin-top:.6rem;}
.fmap-section-title{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--ph-muted);padding:.4rem .5rem;margin-bottom:.3rem;}
.fmap-row{display:flex;align-items:center;gap:.75rem;padding:.65rem .85rem;border-radius:10px;border:1.5px solid var(--ph-border);margin-bottom:.45rem;transition:.2s;background:#fff;}
.fmap-row.fmap-ok{border-color:#a7f3d0;background:#f0fdf4;}
.fmap-row.fmap-miss{border-color:#fecaca;background:#fff5f5;}
.fmap-row.fmap-opt{border-color:#e2e8f0;background:#fafafa;}
.fmap-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;}
.fmap-icon-ok{background:#d1fae5;color:#065f46;}
.fmap-icon-miss{background:#fee2e2;color:#b91c1c;}
.fmap-icon-opt{background:#e0f2fe;color:#0369a1;}
.fmap-label{font-size:.83rem;font-weight:700;color:var(--ph-text);}
.fmap-hint{font-size:.68rem;color:var(--ph-muted);margin-top:.1rem;}
.fmap-sample-tag{display:inline-block;margin-top:.2rem;background:#f1f5f9;border-radius:4px;padding:.1rem .45rem;font-size:.7rem;font-family:monospace;color:#334155;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.fmap-select{flex:1;border:1.5px solid var(--ph-border);border-radius:8px;padding:.4rem .65rem;font-size:.82rem;font-weight:600;background:#fff;cursor:pointer;transition:.2s;min-width:0;}
.fmap-select:focus{outline:none;border-color:var(--ph-primary);box-shadow:0 0 0 3px rgba(31, 107, 74,.1);}
.fmap-select.sel-ok{border-color:#10b981;background:#f0fdf4;color:#065f46;}
.fmap-select.sel-empty{border-color:#fca5a5;}
.fmap-badge{flex-shrink:0;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;}
.fbadge-ok{background:#d1fae5;color:#065f46;}
.fbadge-no{background:#fee2e2;color:#b91c1c;}
.fbadge-opt{background:#f1f5f9;color:#94a3b8;}
.fmap-ready-banner{background:linear-gradient(135deg,#10b981,#059669);color:#fff;border-radius:10px;padding:.75rem 1rem;display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;font-weight:700;font-size:.85rem;}
.fmap-err-banner{background:#fee2e2;color:#b91c1c;border-radius:10px;padding:.65rem 1rem;display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;font-weight:700;font-size:.82rem;border:1.5px solid #fca5a5;}
.fmap-progress{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:1rem;}
.fmap-progress-bar{height:100%;background:linear-gradient(90deg,#1f6b4a,#10b981);border-radius:99px;transition:.4s;}
.preview-wrap{overflow-x:auto;border-radius:8px;border:1px solid var(--ph-border);max-height:320px;overflow-y:auto;}
.preview-tbl{font-size:.7rem;width:100%;border-collapse:collapse;}
.preview-tbl th{background:var(--ph-primary);color:#fff;padding:.35rem .6rem;white-space:nowrap;font-weight:700;font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;position:sticky;top:0;}
.preview-tbl td{padding:.35rem .6rem;white-space:nowrap;border-bottom:1px solid #F1F5F9;}
.preview-tbl tr:hover td{background:#F8FAFC;}
.pane-actions{display:flex;align-items:center;justify-content:space-between;padding:.85rem 0 0;border-top:1px solid var(--ph-border);margin-top:1rem;gap:.5rem;}
.emr{display:flex;align-items:center;justify-content:space-between;gap:.6rem;padding:.55rem .75rem;background:#F8FAFC;border-radius:8px;border:1px solid var(--ph-border);margin-bottom:.4rem;transition:.2s;}
.emr:hover{border-color:var(--ph-primary);}
.emr-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0;}
.emr h6{font-size:.74rem;font-weight:700;margin:0;}
.emr p{font-size:.63rem;color:var(--ph-muted);margin:0;}
.field-pill{display:inline-flex;align-items:center;padding:.15rem .45rem;border-radius:5px;font-size:.63rem;font-weight:700;margin:.1rem;}
.fp-req{background:#fee2e2;color:#b91c1c;}
.fp-opt{background:#F1F5F9;color:var(--ph-muted);}
</style>

<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<!-- Hero -->
<div class="imp-hero">
  <div>
    <h1><i class="fas fa-file-import me-2"></i>Product Import / Export</h1>
    <p>Bulk import products from CSV/Excel or export the full catalogue</p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <button class="ph-btn ph-btn-sm" style="background:#fff;color:#1f6b4a;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);" onclick="downloadTemplate()">
      <i class="fas fa-download"></i> Template
    </button>
    <a href="products.php" class="ph-btn ph-btn-sm" style="background:#fff;color:#1f6b4a;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.05);">
      <i class="fas fa-arrow-left"></i> Back
    </a>
  </div>
</div>

<!-- Step Wizard -->
<div class="step-wizard" id="stepBar">
  <div class="wz-step active" id="step1">
    <div class="wz-num">1</div>
    <div><div class="wz-lbl">Upload</div><div class="wz-sub">CSV / Excel</div></div>
  </div>
  <div class="wz-step" id="step2">
    <div class="wz-num">2</div>
    <div><div class="wz-lbl">Map Columns</div><div class="wz-sub">Match fields</div></div>
  </div>
  <div class="wz-step" id="step3">
    <div class="wz-num">3</div>
    <div><div class="wz-lbl">Preview</div><div class="wz-sub">Verify data</div></div>
  </div>
  <div class="wz-step" id="step4">
    <div class="wz-num">4</div>
    <div><div class="wz-lbl">Import</div><div class="wz-sub">Save to DB</div></div>
  </div>
</div>

<div class="row g-3">
<!-- LEFT: Wizard -->
<div class="col-lg-8">

  <!-- STEP 1 -->
  <div id="pane1">
    <div class="ph-card mb-3">
      <div class="ph-card-header"><i class="fas fa-upload me-2"></i>Step 1 — Upload your file</div>
      <div class="p-3">
        <div class="import-zone" id="dropZone">
          <input type="file" id="fileInput" accept=".csv,.xlsx,.xls">
          <div class="iz-icon"><i class="fas fa-cloud-upload-alt"></i></div>
          <h4>Drop CSV / Excel here</h4>
          <p>or click to browse &nbsp;·&nbsp; Max 5 MB &nbsp;·&nbsp; .csv, .xlsx, .xls</p>
          <div id="fileName"></div>
        </div>
      </div>
    </div>
    <div class="pane-actions">
      <span class="text-muted" style="font-size:.75rem;"><i class="fas fa-info-circle me-1"></i>Auto-ID generated if not in file</span>
      <button class="ph-btn ph-btn-primary" id="nextToMap" disabled onclick="goToStep(2)">
        Map Columns <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>

  <!-- STEP 2 -->
  <div id="pane2" style="display:none;">
    <div class="ph-card mb-3">
      <div class="ph-card-header"><i class="fas fa-columns me-2"></i>Step 2 — Map Columns</div>
      <div class="p-3">
        <p class="text-muted mb-3" style="font-size:.78rem;"><i class="fas fa-magic me-1 text-primary"></i>We detected your file columns. For each field below, <strong>select which column in your Excel contains this data</strong>. Green = auto-found. Red = needs your help.</p>
        <div id="mapSummaryBar"></div>
        <div class="fmap-progress"><div class="fmap-progress-bar" id="fmapProgressBar" style="width:0%"></div></div>
        <div id="columnMapper"></div>
      </div>
    </div>
    <div class="pane-actions">
      <button class="ph-btn ph-btn-outline" onclick="goToStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="ph-btn ph-btn-primary" onclick="goToStep(3)"><i class="fas fa-eye"></i> Preview Data</button>
    </div>
  </div>

  <!-- STEP 3 -->
  <div id="pane3" style="display:none;">
    <!-- Summary Bar -->
    <div id="previewSummaryBar" class="preview-summary-bar mb-3"></div>
    <!-- Card Grid Preview -->
    <div id="previewCards" class="preview-cards-wrap"></div>
    <div id="validationBox" class="mb-2"></div>
    <div class="pane-actions">
      <button class="ph-btn ph-btn-outline" onclick="goToStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="ph-btn ph-btn-success" id="confirmImportBtn" onclick="goToStep(4)"><i class="fas fa-check"></i> Confirm &amp; Import</button>
    </div>
  </div>

  <!-- STEP 4 -->
  <div id="pane4" style="display:none;">
    <div class="ph-card">
      <div class="ph-card-header"><i class="fas fa-database me-2"></i>Step 4 — Import</div>
      <div class="p-4" id="importResult">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;"></div>
          <p class="mt-3 fw-bold">Processing your file...</p>
        </div>
      </div>
    </div>
  </div>

</div><!-- col-lg-8 -->

<!-- RIGHT: Export + Guide -->
<div class="col-lg-4">

  <!-- Export Panel -->
  <div class="ph-card mb-3">
    <div class="ph-card-header"><i class="fas fa-file-export me-2"></i>Export</div>
    <div class="p-2">
      <?php $cnt = (int)getDB()->query("SELECT COUNT(*) FROM ph_product")->fetchColumn(); ?>
      <div class="emr">
        <div class="d-flex align-items-center gap-2 flex-1">
          <div class="emr-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-file-csv"></i></div>
          <div><h6>Full Catalogue</h6><p><?= $cnt ?> products</p></div>
        </div>
        <button class="ph-btn ph-btn-success ph-btn-sm" onclick="exportProducts('all','csv')"><i class="fas fa-download"></i></button>
      </div>
      <div class="emr">
        <div class="d-flex align-items-center gap-2 flex-1">
          <div class="emr-icon" style="background:#fef9c3;color:#92400e;"><i class="fas fa-exclamation-triangle"></i></div>
          <div><h6>Low Stock</h6><p>Below min level</p></div>
        </div>
        <button class="ph-btn ph-btn-warning ph-btn-sm text-dark" onclick="exportProducts('lowstock','csv')"><i class="fas fa-download"></i></button>
      </div>
      <div class="emr">
        <div class="d-flex align-items-center gap-2 flex-1">
          <div class="emr-icon" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-clock"></i></div>
          <div><h6>Expiry Alert</h6><p>Expiring ≤ 60 days</p></div>
        </div>
        <button class="ph-btn ph-btn-danger ph-btn-sm" onclick="exportProducts('expiry','csv')"><i class="fas fa-download"></i></button>
      </div>
      <div class="emr">
        <div class="d-flex align-items-center gap-2 flex-1">
          <div class="emr-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-print"></i></div>
          <div><h6>Print Catalogue</h6><p>Open print view</p></div>
        </div>
        <button class="ph-btn ph-btn-outline ph-btn-sm" onclick="window.open(API_BASE+'pharmacy/export/print','_blank')"><i class="fas fa-print"></i></button>
      </div>
    </div>
  </div>

  <!-- Field Guide -->
  <div class="ph-card">
    <div class="ph-card-header"><i class="fas fa-info-circle me-2"></i>Field Guide</div>
    <div class="p-2" style="max-height:280px;overflow-y:auto;">
      <p style="font-size:.72rem;color:var(--ph-muted);" class="mb-2">Supported CSV column names:</p>
      <?php
        $required = ['product_name'=>'Medicine Name'];
        $optional = [
          'product_id'=>'Unique ID (auto if missing)','content'=>'Composition',
          'strength'=>'Dosage strength','form'=>'Tablet/Syrup etc','therapeutic'=>'Category',
          'manufacturer'=>'Company','hsn_code'=>'HSN','batch_number'=>'Batch No',
          'expiry_date'=>'Expiry (YYYY-MM-DD)','quantity'=>'Stock qty',
          'purchase_rate'=>'Cost rate','pack_rate'=>'Pack rate',
          'individual_rate'=>'Unit rate','mrp'=>'MRP','tax_percent'=>'GST %',
          'unit'=>'Unit','min_stock'=>'Reorder level','rack_location'=>'Storage',
        ];
        foreach($required as $k=>$v): ?>
        <span class="field-pill fp-req" title="<?= $v ?>"><i class="fas fa-asterisk" style="font-size:.5rem;"></i> <?= $k ?></span>
      <?php endforeach; ?>
      <div class="divider" style="margin:.5rem 0;"></div>
      <?php foreach($optional as $k=>$v): ?>
        <span class="field-pill fp-opt" title="<?= $v ?>"><?= $k ?></span>
      <?php endforeach; ?>
    </div>
  </div>

</div><!-- col-lg-4 -->
</div><!-- row -->

</div><!-- ph-page-body -->
</div><!-- ph-content -->
</div><!-- ph-wrap -->


<?php include 'includes/ph_foot.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
// ── DB Field definitions ─────────────────────────────────
const DB_FIELDS = [
  {key:'product_id',      label:'Product ID',       required:false, aliases:['id','code','pcode']},
  {key:'product_name',    label:'Product Name',     required:true, aliases:['name','medicine','item']},
  {key:'content',         label:'Content',          aliases:['composition','ingredients']},
  {key:'strength',        label:'Strength',         aliases:['power','dosage']},
  {key:'form',            label:'Form',             aliases:['type','packaging']},
  {key:'therapeutic',     label:'Therapeutic',      aliases:['category','group','class']},
  {key:'manufacturer',    label:'Manufacturer',     aliases:['mfg','company','brand']},
  {key:'hsn_code',        label:'HSN Code',         aliases:['hsn']},
  {key:'batch_number',    label:'Batch Number',     aliases:['batch','batchno','lot']},
  {key:'expiry_date',     label:'Expiry Date',      aliases:['expiry','exp']},
  {key:'quantity',        label:'Quantity',         aliases:['stock','qty','currentqty','balance']},
  {key:'pack',            label:'Pack',             aliases:['packdetails','packing']},
  {key:'unit',            label:'Unit',             aliases:['uom','measure']},
  {key:'pack_size',       label:'Pack Size',        aliases:['size']},
  {key:'pack_rate',       label:'Pack Rate',        aliases:['prate']},
  {key:'individual_rate', label:'Individual Rate',  aliases:['irate','unitrate']},
  {key:'purchase_rate',   label:'Purchase Rate',    aliases:['cost','purchase']},
  {key:'mrp',             label:'MRP',              aliases:['price','sellingprice','mrprate']},
  {key:'sales_price',     label:'Sales Price',      aliases:['saleprice','sp']},
  {key:'GST_price',       label:'GST Price',        aliases:['gstprice','gstamt','gstamount']},
  {key:'total_MRP',       label:'Total MRP',        aliases:['totalmrp','totalrate']},
  {key:'tax_no',          label:'Tax No',           aliases:['taxno','taxnumber','taxcode']},
  {key:'t_sale_price',    label:'Total Sale Price', aliases:['tsaleprice','totalsaleprice','totalsale']},
  {key:'total_cost',      label:'Total Cost',       aliases:['totalcost','totalamount']},
  {key:'tax_percent',     label:'Tax %',            aliases:['gst','tax','gstpercent','taxpercent']},
  {key:'min_stock',       label:'Min Stock',        aliases:['reorder','min','minstock','reorderlevel']},
  {key:'max_stock',       label:'Max Stock',        aliases:['max','maxstock']},
  {key:'rack_location',   label:'Rack Location',    aliases:['rack','shelf','loc','location']},
  {key:'attachment',      label:'Attachment',       aliases:['attach','doc','document']},
];

let parsedData = [];   // all rows
let csvHeaders = [];   // headers from file
let mapping    = {};   // csvHeader → dbKey

// ── Drag & Drop / File Select ────────────────────────────
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', ()=> dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('drag'); handleFile(e.dataTransfer.files[0]); });
fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));

function handleFile(file) {
  if (!file) return;
  document.getElementById('fileName').innerHTML = `<div class="file-chip"><i class="fas fa-paperclip"></i> ${file.name} &nbsp;(${(file.size/1024).toFixed(1)} KB)</div>`;
  document.getElementById('nextToMap').disabled = false;
  const reader = new FileReader();
  reader.onload = e => {
    const wb   = XLSX.read(e.target.result, {type:'array', cellDates:true});
    const ws   = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(ws, {header:1, raw:false, dateNF:'YYYY-MM-DD'});
    csvHeaders = rows[0] || [];
    parsedData = rows.slice(1).filter(r => r.some(c => c !== null && c !== undefined && String(c).trim() !== ''));
    autoMap();
  };
  reader.readAsArrayBuffer(file);
}

// ── Auto-map CSV headers to DB fields ───────────────────
function autoMap() {
  mapping = {};
  csvHeaders.forEach(h => {
    const norm = h.toLowerCase().replace(/[\s_\-]/g,'');
    const match = DB_FIELDS.find(f => {
      // Don't map if this DB field is already mapped to another column
      if (Object.values(mapping).includes(f.key)) return false;
      
      const fk = f.key.toLowerCase().replace(/_/g,'');
      const fl = f.label.toLowerCase().replace(/[\s_]/g,'');
      const fa = (f.aliases || []).map(a => a.toLowerCase().replace(/[\s_]/g,''));
      return norm === fk || norm === fl || fa.includes(norm) || 
             (fk.length > 3 && norm.includes(fk)) || 
             fa.some(a => a.length > 3 && norm.includes(a));
    });
    if (match) mapping[h] = match.key;
    else       mapping[h] = '';
  });
}

// ── Step navigation ──────────────────────────────────────
function goToStep(n) {
  [1,2,3,4].forEach(i => {
    document.getElementById('pane'+i).style.display = i===n ? '' : 'none';
    const s = document.getElementById('step'+i);
    if(s) s.className = 'wz-step' + (i < n ? ' done' : i === n ? ' active' : '');
  });
  if (n===2) renderMapper();
  if (n===3) renderPreview();
  if (n===4) runImport();
}

// ── Inverted field-first mapper ──────────────────────────
// dbKey → which csvHeader maps to it
let dbMapping = {}; // dbKey: csvHeader or ''

function setDbMapping(dbKey, csvH) {
  // If this csvH is already used by another dbKey, clear it first
  Object.keys(dbMapping).forEach(k => { if(dbMapping[k] === csvH && k !== dbKey) dbMapping[k] = ''; });
  dbMapping[dbKey] = csvH;
  // Rebuild the legacy mapping object (csvH → dbKey) for compatibility with preview/import
  mapping = {};
  csvHeaders.forEach(h => { mapping[h] = ''; });
  Object.entries(dbMapping).forEach(([dk, csvH]) => { if(csvH) mapping[csvH] = dk; });
  renderMapper();
}

function renderMapper() {
  const wrap = document.getElementById('columnMapper');

  // Build dbMapping from existing mapping if empty (first render after autoMap)
  if (Object.keys(dbMapping).length === 0) {
    DB_FIELDS.forEach(f => { dbMapping[f.key] = ''; });
    Object.entries(mapping).forEach(([csvH, dk]) => { if(dk) dbMapping[dk] = csvH; });
  }

  // CSV column options builder
  function csvColOpts(selectedH) {
    const used = new Set(Object.values(dbMapping).filter(Boolean));
    return csvHeaders.map(h => {
      const alreadyUsed = used.has(h) && h !== selectedH;
      return `<option value="${h}" ${h===selectedH?'selected':''} ${alreadyUsed?'style="color:#aaa;"':''}>${h}</option>`;
    }).join('');
  }

  // Get sample from row 1
  function getSample(csvH) {
    if (!csvH) return '';
    const idx = csvHeaders.indexOf(csvH);
    return idx >= 0 ? String(parsedData[0]?.[idx] ?? '').trim().slice(0, 40) : '';
  }

  // Count mapped required fields
  const reqFields  = DB_FIELDS.filter(f => f.required);
  const optFields  = DB_FIELDS.filter(f => !f.required);
  const mappedReq  = reqFields.filter(f => dbMapping[f.key]).length;
  const totalMapped = DB_FIELDS.filter(f => dbMapping[f.key]).length;
  const allReqDone = mappedReq === reqFields.length;
  const pct = Math.round((totalMapped / DB_FIELDS.length) * 100);

  // Progress bar
  const pb = document.getElementById('fmapProgressBar');
  if(pb) pb.style.width = pct + '%';

  // Status banner
  const bar = document.getElementById('mapSummaryBar');
  if(bar) {
    if(allReqDone) {
      bar.innerHTML = `<div class="fmap-ready-banner"><i class="fas fa-check-circle" style="font-size:1.4rem;"></i><div><div>All required fields are matched!</div><div style="font-weight:500;font-size:.75rem;opacity:.9;">${totalMapped} of ${DB_FIELDS.length} fields mapped — ready to preview your data</div></div></div>`;
    } else {
      const miss = reqFields.filter(f => !dbMapping[f.key]).map(f => f.label).join(', ');
      bar.innerHTML = `<div class="fmap-err-banner"><i class="fas fa-exclamation-circle" style="font-size:1.2rem;"></i><div>Please map these required fields: <strong>${miss}</strong></div></div>`;
    }
  }

  // Build field row
  function fieldRow(f) {
    const csvH   = dbMapping[f.key] || '';
    const sample = getSample(csvH);
    const isReq  = !!f.required;
    const isMapped = !!csvH;

    let rowCls, iconCls, badgeCls, badgeIcon;
    if (isMapped) {
      rowCls = 'fmap-ok'; iconCls = 'fmap-icon-ok'; badgeCls = 'fbadge-ok'; badgeIcon = 'fa-check';
    } else if (isReq) {
      rowCls = 'fmap-miss'; iconCls = 'fmap-icon-miss'; badgeCls = 'fbadge-no'; badgeIcon = 'fa-times';
    } else {
      rowCls = 'fmap-opt'; iconCls = 'fmap-icon-opt'; badgeCls = 'fbadge-opt'; badgeIcon = 'fa-minus';
    }

    const safeKey = f.key.replace(/'/g,"\\'");
    return `
      <div class="fmap-row ${rowCls}">
        <div class="fmap-icon ${iconCls}"><i class="fas ${badgeIcon}"></i></div>
        <div style="flex:0 0 180px; min-width:0;">
          <div class="fmap-label">${f.label}${isReq ? ' <span style="color:#ef4444;font-size:.7rem;">●</span>' : ''}</div>
          ${sample ? `<span class="fmap-sample-tag">${sample}</span>` : `<div class="fmap-hint">${isReq ? 'Required — please select below' : 'Optional — leave blank to skip'}</div>`}
        </div>
        <i class="fas fa-arrow-right" style="color:#94a3b8; flex-shrink:0;"></i>
        <select class="fmap-select ${isMapped?'sel-ok':(isReq?'sel-empty':'')}" onchange="setDbMapping('${safeKey}', this.value)">
          <option value="">${isReq ? '⚠ Select a column…' : '— Not in my file —'}</option>
          ${csvColOpts(csvH)}
        </select>
        <div class="fmap-badge ${badgeCls}"><i class="fas ${badgeIcon}"></i></div>
      </div>`;
  }

  const existingDetails = document.getElementById('optFieldsDetails');
  const detailsOpenAttr = existingDetails && existingDetails.open ? 'open' : '';

  wrap.innerHTML = `
    <div class="fmap-section-title"><i class="fas fa-star me-1" style="color:#f59e0b;"></i>Required Fields</div>
    ${reqFields.map(fieldRow).join('')}
    <div style="margin:.85rem 0 .4rem; border-top:1px dashed var(--ph-border); padding-top:.75rem;">
      <details id="optFieldsDetails" ${detailsOpenAttr}>
        <summary style="cursor:pointer; font-size:.75rem; font-weight:700; color:var(--ph-muted); list-style:none; display:flex; align-items:center; gap:.4rem;"><i class="fas fa-caret-right"></i> Optional Fields (${optFields.length}) — click to expand</summary>
        <div style="margin-top:.6rem;">${optFields.map(fieldRow).join('')}</div>
      </details>
    </div>`;
}

// ── Step navigation ──────────────────────────────────────
function goToStep(n) {
  [1,2,3,4].forEach(i => {
    document.getElementById('pane'+i).style.display = i===n ? '' : 'none';
    const s = document.getElementById('step'+i);
    s.className = 'wz-step' + (i < n ? ' done' : i === n ? ' active' : '');
  });
  if (n===2) renderMapper();
  if (n===3) renderPreview();
  if (n===4) runImport();
}

// ── Render preview (card-per-row style) ─────────────────
function renderPreview() {
  const preview = parsedData.slice(0, 15);
  const total   = parsedData.length;

  // Helper: get value from a row by DB key
  function val(row, dbKey) {
    const h = csvHeaders.find(h => mapping[h] === dbKey);
    return h ? (row[csvHeaders.indexOf(h)] ?? '') : '';
  }

  // Validation
  const missing = DB_FIELDS.filter(f => f.required && !Object.values(mapping).includes(f.key));
  const vBox    = document.getElementById('validationBox');
  const confirmBtn = document.getElementById('confirmImportBtn');
  if (missing.length) {
    vBox.innerHTML = `<div class="alert alert-danger" style="font-size:.78rem;padding:.6rem .9rem;"><i class="fas fa-exclamation-circle me-2"></i><strong>Required fields not mapped:</strong> ${missing.map(f=>f.label).join(', ')}</div>`;
    if (confirmBtn) confirmBtn.disabled = true;
  } else {
    vBox.innerHTML = `<div style="background:#f0fdf4;border:1.5px solid #a7f3d0;border-radius:8px;padding:.55rem 1rem;font-size:.78rem;color:#15803d;margin-bottom:.5rem;"><i class="fas fa-check-circle me-2"></i>All required fields mapped. Ready to import <strong>${total}</strong> rows.</div>`;
    if (confirmBtn) confirmBtn.disabled = false;
  }

  // Summary Stats
  const mappedCount = Object.values(mapping).filter(v=>v).length;
  const hasBatch    = preview.some(r => val(r,'batch_number'));
  const hasExpiry   = preview.some(r => val(r,'expiry_date'));
  document.getElementById('previewSummaryBar').innerHTML = `
    <div class="psb-card">
      <div class="psb-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-boxes"></i></div>
      <div><div class="psb-val">${total}</div><div class="psb-lbl">Total Rows</div></div>
    </div>
    <div class="psb-card">
      <div class="psb-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-columns"></i></div>
      <div><div class="psb-val">${mappedCount}</div><div class="psb-lbl">Fields Mapped</div></div>
    </div>
    <div class="psb-card">
      <div class="psb-icon" style="background:${hasBatch?'#d1fae5':'#fef9c3'};color:${hasBatch?'#065f46':'#92400e'};"><i class="fas fa-barcode"></i></div>
      <div><div class="psb-val" style="font-size:.9rem;padding-top:.15rem;">${hasBatch?'Yes':'No'}</div><div class="psb-lbl">Batch Info</div></div>
    </div>
    <div class="psb-card">
      <div class="psb-icon" style="background:${hasExpiry?'#d1fae5':'#fee2e2'};color:${hasExpiry?'#065f46':'#b91c1c'};"><i class="fas fa-calendar-alt"></i></div>
      <div><div class="psb-val" style="font-size:.9rem;padding-top:.15rem;">${hasExpiry?'Yes':'No'}</div><div class="psb-lbl">Expiry Date</div></div>
    </div>`;

  // Cards
  const wrap = document.getElementById('previewCards');
  wrap.innerHTML = preview.map((row, idx) => {
    const name   = val(row,'product_name') || '—';
    const form   = val(row,'form');
    const thera  = val(row,'therapeutic');
    const mfg    = val(row,'manufacturer');
    const qty    = val(row,'quantity');
    const batch  = val(row,'batch_number');
    const mrp    = val(row,'mrp');
    const expRaw = val(row,'expiry_date');
    const pid    = val(row,'product_id');

    let expChipClass = 'pv-exp';
    let expLabel = expRaw || '—';
    if (expRaw) {
      const diff = (new Date(expRaw) - new Date()) / 86400000;
      if (diff < 0)        { expChipClass += ' danger'; expLabel = 'Expired'; }
      else if (diff < 90)  { expChipClass += ' danger'; expLabel = expRaw; }
      else if (diff < 180) { expLabel = expRaw; }
      else                 { expChipClass += ' ok'; expLabel = expRaw; }
    }

    const sub = [form, thera, mfg].filter(Boolean).join(' · ');
    return `
    <div class="pv-card">
      <div class="pv-row-num">${idx+1}</div>
      <div style="min-width:0;">
        <div class="pv-name">${name}</div>
        <div class="pv-sub">${pid ? '<code style="font-size:.6rem;">'+pid+'</code> · ' : ''}${sub || 'No details'}</div>
      </div>
      ${qty !== '' ? `<span class="pv-chip pv-qty"><i class="fas fa-cubes"></i> ${qty} units</span>` : '<span class="pv-chip pv-batch">No Qty</span>'}
      ${batch ? `<span class="pv-chip pv-batch"><i class="fas fa-barcode"></i> ${batch}</span>` : '<span class="pv-chip pv-batch" style="opacity:.4;">No Batch</span>'}
      ${mrp ? `<span class="pv-chip pv-mrp">₹${mrp}</span>` : '<span class="pv-chip pv-batch" style="opacity:.4;">No MRP</span>'}
      <span class="pv-chip ${expChipClass}"><i class="fas fa-calendar-alt"></i> ${expLabel}</span>
    </div>`;
  }).join('');
}


// ── Run actual import ────────────────────────────────────
async function runImport() {
  // Build array of objects using mapping
  const rows = parsedData.map(row => {
    const obj = {};
    csvHeaders.forEach((h, i) => {
      if (mapping[h]) obj[mapping[h]] = row[i] ?? '';
    });
    return obj;
  });

  try {
    const res = await fetch(API_BASE + 'pharmacy/import/products', {
      method:  'POST',
      headers: {'Content-Type':'application/json'},
      body:    JSON.stringify({rows})
    });
    const j = await res.json();
    const box = document.getElementById('importResult');
    if (j.success) {
      box.innerHTML = `
        <div class="text-center py-4">
          <div style="font-size:4rem;color:${j.data.errors > 0 ? '#f59e0b' : '#10b981'};"><i class="fas ${j.data.errors > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i></div>
          <h4 class="mt-3 fw-bold ${j.data.errors > 0 ? 'text-warning' : 'text-success'}">Import Completed</h4>
          <div class="row g-3 mt-3 justify-content-center">
            <div class="col-auto"><div class="ph-stat px-4"><div class="ph-stat-val text-success">${j.data.inserted}</div><div class="ph-stat-lbl">Inserted</div></div></div>
            <div class="col-auto"><div class="ph-stat px-4"><div class="ph-stat-val text-info">${j.data.updated}</div><div class="ph-stat-lbl">Updated</div></div></div>
            <div class="col-auto"><div class="ph-stat px-4"><div class="ph-stat-val text-danger">${j.data.errors}</div><div class="ph-stat-lbl">Failed</div></div></div>
          </div>
          ${j.data.error_details && j.data.error_details.length > 0 ? `<div class="alert alert-danger mt-3 text-start" style="max-height:150px;overflow-y:auto;font-size:.78rem;"><i class="fas fa-times-circle me-1"></i> <strong>Errors encountered:</strong><br>${j.data.error_details.join('<br>')}</div>` : ''}
          <div class="mt-4 d-flex gap-2 justify-content-center">
            <a href="products.php" class="ph-btn ph-btn-primary"><i class="fas fa-pills"></i> View Products</a>
            <button class="ph-btn ph-btn-outline" onclick="location.reload()"><i class="fas fa-redo"></i> Import More</button>
          </div>
        </div>`;
    } else {
      box.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><strong>Import Failed:</strong> ${j.error || j.message || 'Unknown error'}</div>`;
    }
  } catch(e) {
    document.getElementById('importResult').innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Network error: ${e.message}</div>`;
  }
}

// ── CSV Template Download ────────────────────────────────
function downloadTemplate() {
  const headers = DB_FIELDS.map(f => f.key);
  const sample  = [
    'P001','Paracetamol 500mg','Paracetamol','500mg','Tablet','Analgesic',
    'Sun Pharma','30049099','B2401','2027-12-31','100','10 Tabs x 10',
    'Tablet','10','28.50','2.85','20.00','32.00','12','20','500','R1-A1'
  ];
  const csv = headers.join(',') + '\n' + sample.join(',');
  const a   = document.createElement('a');
  a.href    = 'data:text/csv,' + encodeURIComponent(csv);
  a.download= 'product_import_template.csv';
  a.click();
}

// ── Export Products ──────────────────────────────────────
function exportProducts(type, format) {
  window.location.href = API_BASE + `pharmacy/export/csv?type=${type}`;
}
</script>
