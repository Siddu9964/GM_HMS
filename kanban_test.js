function changeDate() {
    const val = document.getElementById('result-date').value;
    window.location.href = 'kanban.php?date=' + val;
}

function filterResults() {
    const query = document.getElementById('resultSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.result-row');
    
    rows.forEach(row => {
        if (!query || row.dataset.search.includes(query)) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

let currentEditId = null;
async function editResult(orderId) {
    currentEditId = orderId;
    document.getElementById('em-order-id').textContent = orderId;
    const tbody = document.getElementById('em-tbody');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">Loading...</td></tr>';
    document.getElementById('editModal').style.display = 'flex';

    try {
        const res = await fetch('/GM_HMS/api/laboratory/orders/' + encodeURIComponent(orderId) + '/result');
        const data = await res.json();
        
        tbody.innerHTML = '';
        if (data.success && data.data && data.data.result_data) {
            let params = [];
            try { params = JSON.parse(data.data.result_data); } catch(e){}
            if(params.length > 0) {
                params.forEach(p => emAddRow(p.name, p.value, p.unit, p.range));
            } else {
                emAddRow();
            }
        } else {
            emAddRow();
        }
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:red;">Error loading results</td></tr>';
    }
}

function emAddRow(name='', val='', unit='', range='') {
    name = (name || '').toString();
    val = (val || '').toString();
    unit = (unit || '').toString();
    range = (range || '').toString();
    
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #f1f5f9';
    tr.innerHTML = `
        <td style="padding:6px 4px;"><input type="text" class="em-name" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${name.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-val" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${val.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-unit" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${unit.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px;"><input type="text" class="em-range" style="width:100%; padding:4px 8px; border:1px solid #cbd5e1; border-radius:4px;" value="${range.replace(/"/g, '&quot;')}"></td>
        <td style="padding:6px 4px; text-align:center;"><button type="button" onclick="this.closest('tr').remove()" style="background:none;border:none;color:#ef4444;cursor:pointer;"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('em-tbody').appendChild(tr);
}

async function emSave() {
    const btn = document.getElementById('em-btn-save');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    const rows = document.querySelectorAll('#em-tbody tr');
    let params = [];
    rows.forEach(tr => {
        const name = tr.querySelector('.em-name')?.value.trim();
        if(name) {
            params.push({
                name: name,
                value: tr.querySelector('.em-val')?.value.trim() || '',
                unit: tr.querySelector('.em-unit')?.value.trim() || '',
                range: tr.querySelector('.em-range')?.value.trim() || ''
            });
        }
    });
    
    const payload = { result_data: JSON.stringify(params) };
    
    try {
        const res = await fetch('/GM_HMS/api/laboratory/orders/' + encodeURIComponent(currentEditId) + '/result', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success) {
            alert('Results saved successfully!');
            document.getElementById('editModal').style.display = 'none';
        } else {
            alert(data.message || 'Error saving results');
        }
    } catch(e) {
        alert('Connection error');
    }
    
    btn.innerHTML = '<i class="fas fa-save"></i> Save Results';
    btn.disabled = false;
}
