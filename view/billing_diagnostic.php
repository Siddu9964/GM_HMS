<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <title>Billing System Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔍 Billing System Diagnostic</h1>
    
    <div class="test info">
        <h3>Test 1: Check if jQuery is loaded</h3>
        <button onclick="testJQuery()">Run Test</button>
        <div id="jquery-result"></div>
    </div>
    
    <div class="test info">
        <h3>Test 2: Test API - Get All Bills</h3>
        <button onclick="testGetAllBills()">Run Test</button>
        <div id="getall-result"></div>
    </div>
    
    <div class="test info">
        <h3>Test 3: Test API - Get Statistics</h3>
        <button onclick="testGetStats()">Run Test</button>
        <div id="stats-result"></div>
    </div>
    
    <div class="test info">
        <h3>Test 4: Test Patient Search API</h3>
        <button onclick="testPatientSearch()">Run Test</button>
        <div id="patient-result"></div>
    </div>
    
    <div class="test info">
        <h3>Test 5: Check Database View</h3>
        <p>Run this SQL in phpMyAdmin:</p>
        <pre>SELECT * FROM v_opd_billing_summary LIMIT 1;</pre>
        <p>If you get an error, the view doesn't exist. Run: <code>setup/update_billing_views.sql</code></p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function testJQuery() {
            const result = document.getElementById('jquery-result');
            if (typeof jQuery !== 'undefined') {
                result.innerHTML = '<p style="color: green;">✅ jQuery is loaded! Version: ' + jQuery.fn.jquery + '</p>';
            } else {
                result.innerHTML = '<p style="color: red;">❌ jQuery is NOT loaded!</p>';
            }
        }
        
        function testGetAllBills() {
            const result = document.getElementById('getall-result');
            result.innerHTML = '<p>Loading...</p>';
            
            $.ajax({
                url: '../api/index.php/api/billing/opd',
                method: 'GET',
                success: function(response) {
                    result.innerHTML = '<p style="color: green;">✅ API Response:</p><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    result.innerHTML = '<p style="color: red;">❌ Error: ' + error + '</p><pre>' + xhr.responseText + '</pre>';
                }
            });
        }
        
        function testGetStats() {
            const result = document.getElementById('stats-result');
            result.innerHTML = '<p>Loading...</p>';
            
            $.ajax({
                url: '../api/index.php/api/billing/opd/stats',
                method: 'GET',
                success: function(response) {
                    result.innerHTML = '<p style="color: green;">✅ API Response:</p><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    result.innerHTML = '<p style="color: red;">❌ Error: ' + error + '</p><pre>' + xhr.responseText + '</pre>';
                }
            });
        }
        
        function testPatientSearch() {
            const result = document.getElementById('patient-result');
            result.innerHTML = '<p>Loading...</p>';
            
            $.ajax({
                url: '../api/index.php/api/patients?term=a',
                method: 'GET',
                success: function(response) {
                    result.innerHTML = '<p style="color: green;">✅ API Response:</p><pre>' + JSON.stringify(response, null, 2) + '</pre>';
                },
                error: function(xhr, status, error) {
                    result.innerHTML = '<p style="color: red;">❌ Error: ' + error + '</p><pre>' + xhr.responseText + '</pre>';
                }
            });
        }
    </script>
</body>
</html>
