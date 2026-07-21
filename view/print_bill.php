<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$bill_id = $_GET['bill_id'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <title>Print Bill - <?php echo htmlspecialchars($bill_id); ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 20px; line-height: 1.2; }
        .receipt { width: 300px; margin: auto; }
        .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        @media print {
            body { padding: 0; }
            .receipt { width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <div class="text-center">
            <h2 style="margin: 0;">GM MEDICAL CENTER</h2>
            <p style="margin: 2px 0;">123 Healthcare Avenue, Medical District</p>
            <p style="margin: 2px 0;">Ph: +91 98765 43210</p>
            <h3 style="margin: 10px 0;">CASH RECEIPT</h3>
        </div>

        <div class="divider"></div>

        <div id="loading">Loading...</div>

        <div id="bill-content" style="display: none;">
            <div class="item-row">
                <span>Bill #:<span id="bill-id"></span></span>
                <span style="float: right;" id="bill-date"></span>
            </div>
            <div class="item-row">
                <span>Patient: <span id="patient-name"></span></span>
            </div>
            <div class="item-row">
                <span>Doctor : <span id="doctor-name"></span></span>
            </div>

            <div class="divider"></div>

            <div style="margin-bottom: 3px;" class="bold">
                <span style="display:inline-block; width: 180px;">ITEM</span>
                <span style="display:inline-block; width: 30px; text-align:center;">QTY</span>
                <span style="display:inline-block; width: 80px; text-align:right;">AMOUNT</span>
            </div>

            <div id="items-list"></div>

            <div class="divider"></div>

            <div class="item-row bold">
                <span>SUBTOTAL</span>
                <span id="subtotal"></span>
            </div>
            <div class="item-row">
                <span>DISCOUNT</span>
                <span id="discount"></span>
            </div>
            <div class="item-row bold" style="font-size: 14px; margin-top: 5px;">
                <span>GRAND TOTAL</span>
                <span id="grand-total"></span>
            </div>

            <div class="divider"></div>

            <div class="item-row">
                <span>RECEIVED</span>
                <span id="paid"></span>
            </div>
            <div class="item-row">
                <span>BALANCE</span>
                <span id="balance"></span>
            </div>

            <div class="divider"></div>

            <div class="text-center" style="margin-top: 20px;">
                <p>Thank You!</p>
                <p>Wishing you a healthy recovery.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const billId = "<?php echo $bill_id; ?>";
        if (billId) {
            $.get(`../api/index.php/api/billing/opd/${billId}`, function(response) {
                if (response.status === 'success') {
                    const bill = response.data;
                    $('#bill-id').text(bill.bill_id);
                    $('#bill-date').text(bill.bill_date);
                    $('#patient-name').text(bill.patient_name);
                    $('#doctor-name').text(bill.doctor_name || 'N/A');
                    
                    let itemsHtml = '';
                    bill.items.forEach(item => {
                        itemsHtml += `
                            <div class="item-row">
                                <span style="display:inline-block; width: 180px;">${item.item_name}</span>
                                <span style="display:inline-block; width: 30px; text-align:center;">${item.quantity}</span>
                                <span style="display:inline-block; width: 80px; text-align:right;">${parseFloat(item.total_price).toFixed(2)}</span>
                            </div>
                        `;
                    });
                    $('#items-list').html(itemsHtml);
                    
                    $('#subtotal').text(parseFloat(bill.subtotal).toFixed(2));
                    $('#discount').text(parseFloat(bill.discount_amount).toFixed(2));
                    $('#grand-total').text(parseFloat(bill.grand_total).toFixed(2));
                    $('#paid').text(parseFloat(bill.amount_paid).toFixed(2));
                    $('#balance').text(parseFloat(bill.balance_due).toFixed(2));
                    
                    $('#loading').hide();
                    $('#bill-content').show();
                    
                    // Trigger print after a short delay to ensure content is rendered
                    setTimeout(() => {
                        window.print();
                        // window.close(); // Optional: close tab after printing
                    }, 500);
                }
            });
        }
    </script>
</body>
</html>
