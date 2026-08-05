<?php
$file = 'd:/xampp/htdocs/GM_HMS/laboratory_view/test_orders.php';
$content = file_get_contents($file);

$target = <<<'HTML'
        <div class="lo-fgroup">
          <label><i class="fas fa-search"></i> Search</label>
          <input type="text" class="lo-finput" id="filter-search" placeholder="Test, patient, order ID...">
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;margin-top:15px;justify-content:space-between;flex-wrap:wrap;">
        <button class="lb lb-outline" style="font-size:.75rem;padding:6px 14px;border-color:var(--bdr);" onclick="resetFilters()">
          <i class="fas fa-undo"></i> Reset Filters
        </button>
      </div>
    </div>

    <!-- Quick Chips -->
    <div class="lo-chip-bar">
      <span class="lo-chip-lbl">Quick:</span>
      <span class="lo-chip active" data-filter="today" onclick="quickFilter('today',this)"><i class="fas fa-calendar-day" style="font-size:.65rem;"></i> Today</span>
      <span class="lo-chip" data-filter="pending" onclick="quickFilter('pending',this)"><i class="fas fa-clock" style="font-size:.65rem;"></i> Pending</span>
      <span class="lo-chip" data-filter="urgent" onclick="quickFilter('urgent',this)"><i class="fas fa-exclamation" style="font-size:.65rem;"></i> Urgent</span>
      <span class="lo-chip" data-filter="completed" onclick="quickFilter('completed',this)"><i class="fas fa-check" style="font-size:.65rem;"></i> Completed</span>
      <span class="lo-chip" data-filter="all" onclick="quickFilter('all',this)"><i class="fas fa-database" style="font-size:.65rem;"></i> All Time</span>
    </div>
HTML;

$replacement = <<<'HTML'
        <?php
        $searchQuery = $_GET['search'] ?? '';
        $isAll = $searchQuery !== '' ? 'active' : '';
        $isToday = $searchQuery === '' ? 'active' : '';
        ?>
        <div class="lo-fgroup">
          <label><i class="fas fa-search"></i> Search</label>
          <input type="text" class="lo-finput" id="filter-search" placeholder="Test, patient, order ID..." value="<?= htmlspecialchars($searchQuery) ?>">
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;margin-top:15px;justify-content:space-between;flex-wrap:wrap;">
        <button class="lb lb-outline" style="font-size:.75rem;padding:6px 14px;border-color:var(--bdr);" onclick="resetFilters()">
          <i class="fas fa-undo"></i> Reset Filters
        </button>
      </div>
    </div>

    <!-- Quick Chips -->
    <div class="lo-chip-bar">
      <span class="lo-chip-lbl">Quick:</span>
      <span class="lo-chip <?= $isToday ?>" data-filter="today" onclick="quickFilter('today',this)"><i class="fas fa-calendar-day" style="font-size:.65rem;"></i> Today</span>
      <span class="lo-chip" data-filter="pending" onclick="quickFilter('pending',this)"><i class="fas fa-clock" style="font-size:.65rem;"></i> Pending</span>
      <span class="lo-chip" data-filter="urgent" onclick="quickFilter('urgent',this)"><i class="fas fa-exclamation" style="font-size:.65rem;"></i> Urgent</span>
      <span class="lo-chip" data-filter="completed" onclick="quickFilter('completed',this)"><i class="fas fa-check" style="font-size:.65rem;"></i> Completed</span>
      <span class="lo-chip <?= $isAll ?>" data-filter="all" onclick="quickFilter('all',this)"><i class="fas fa-database" style="font-size:.65rem;"></i> All Time</span>
    </div>
HTML;

$content = str_replace(str_replace("\r\n", "\n", $target), str_replace("\r\n", "\n", $replacement), str_replace("\r\n", "\n", $content));
file_put_contents($file, $content);
echo "Done";
