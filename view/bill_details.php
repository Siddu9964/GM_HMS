<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$bill_id = $_GET['bill_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Details - <?php echo htmlspecialchars($bill_id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            body { background: white; }
            .shadow-xl { shadow: none; }
            .rounded-xl { border-radius: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6 no-print">
            <a href="billing_management.php" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Billing
            </a>
            <div class="flex gap-3">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                    <i class="fas fa-print"></i> Print Bill
                </button>
                <button onclick="alert('PDF download coming soon')" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition-all flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <div id="bill-content" class="bg-white rounded-xl shadow-xl overflow-hidden p-8 border border-gray-200">
            <!-- Loading State -->
            <div id="loading" class="text-center py-20">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent mb-4"></div>
                <p class="text-gray-500">Loading bill details...</p>
            </div>

            <!-- Error State -->
            <div id="error" class="hidden text-center py-20">
                <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Oops! Bill Not Found</h2>
                <p class="text-gray-500">The bill ID you requested could not be found or an error occurred.</p>
                <a href="billing_management.php" class="inline-block mt-6 text-blue-600 hover:underline">Return to list</a>
            </div>

            <!-- Bill Template (Hidden until loaded) -->
            <div id="bill-data" class="hidden">
                <!-- Header -->
                <div class="flex justify-between items-start border-b-2 border-gray-100 pb-8 mb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-blue-600 p-3 rounded-lg">
                                <i class="fas fa-hospital text-white text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">GM MEDICAL CENTER</h1>
                                <p class="text-gray-500 text-sm">Professional Healthcare Services</p>
                            </div>
                        </div>
                        <div class="text-gray-600 text-sm space-y-1">
                            <p>123 Healthcare Avenue, Medical District</p>
                            <p>Phone: +91 98765 43210 | Email: billing@gmhms.com</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-3xl font-black text-blue-600 mb-2">INVOICE</h2>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-500">Bill ID</p>
                            <p class="text-xl font-bold text-gray-900" id="bill-id-display">--</p>
                            <p class="text-gray-500 mt-4">Date & Time</p>
                            <p class="font-semibold text-gray-900" id="bill-date-time">--</p>
                        </div>
                    </div>
                </div>

                <!-- Patient & Doctor Info -->
                <div class="grid grid-cols-2 gap-12 mb-10">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Patient Information</h3>
                        <div class="space-y-2">
                            <p class="text-lg font-bold text-gray-900" id="patient-name">--</p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Patient ID</p>
                                    <p class="font-medium" id="patient-id">--</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Age / Sex</p>
                                    <p class="font-medium" id="patient-age-sex">--</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Phone</p>
                                    <p class="font-medium" id="patient-phone">--</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Location</p>
                                    <p class="font-medium" id="patient-address">--</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Consultation Details</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-gray-500 text-sm">Consulting Doctor</p>
                                <p class="text-lg font-bold text-gray-900" id="doctor-name">--</p>
                                <p class="text-blue-600 text-sm font-medium" id="doctor-specialization">--</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Appointment ID</p>
                                <p class="font-medium text-gray-900" id="appointment-id">--</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full mb-10">
                    <thead>
                        <tr class="text-left border-b-2 border-gray-900 pb-4">
                            <th class="py-3 font-bold text-gray-900 uppercase text-xs tracking-wider">Description</th>
                            <th class="py-3 font-bold text-gray-900 uppercase text-xs tracking-wider text-center">Type</th>
                            <th class="py-3 font-bold text-gray-900 uppercase text-xs tracking-wider text-center">Qty</th>
                            <th class="py-3 font-bold text-gray-900 uppercase text-xs tracking-wider text-right">Rate</th>
                            <th class="py-3 font-bold text-gray-900 uppercase text-xs tracking-wider text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <!-- Items populated here -->
                    </tbody>
                </table>

                <!-- Summary -->
                <div class="flex justify-end border-t-2 border-gray-100 pt-6 mb-10">
                    <div class="w-72 space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span id="subtotal">₹0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Discount:</span>
                            <span id="discount" class="text-red-500">-₹0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-3">
                            <span>Taxable Amount:</span>
                            <span id="taxable">₹0.00</span>
                        </div>
                        <div class="flex justify-between text-2xl font-black text-gray-900 pt-3 border-t-2 border-gray-900">
                            <span>Total:</span>
                            <span id="grand-total">₹0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payments -->
                <div class="bg-gray-50 rounded-xl p-6 mb-10">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Payment Summary</h3>
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <p class="text-gray-500 text-sm">Total Paid</p>
                            <p class="text-xl font-bold text-green-600" id="total-paid">₹0.00</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Balance Due</p>
                            <p class="text-xl font-bold text-red-600" id="balance-due">₹0.00</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500 text-sm">Status</p>
                            <span id="payment-status" class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest mt-1">--</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-100 pt-10 text-center">
                    <p class="text-gray-400 text-sm mb-6">This is a computer generated invoice and does not require a physical signature.</p>
                    <div class="flex justify-center gap-20">
                        <div class="text-center">
                            <div class="h-1 bg-gray-200 w-40 mb-2 mx-auto"></div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Receiver's Signature</p>
                        </div>
                        <div class="text-center">
                            <div class="h-1 bg-gray-200 w-40 mb-2 mx-auto"></div>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Authorized Signatory</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const billId = "<?php echo $bill_id; ?>";
        
        $(document).ready(function() {
            if (!billId) {
                showError();
                return;
            }
            loadBillDetails();
        });

        function loadBillDetails() {
            $.ajax({
                url: `../api/index.php/api/billing/opd/${billId}`,
                method: 'GET',
                success: function(response) {
                    if (response.status === 'success') {
                        populateBill(response.data);
                    } else {
                        showError();
                    }
                },
                error: function() {
                    showError();
                }
            });
        }

        function populateBill(bill) {
            $('#loading').addClass('hidden');
            $('#bill-data').removeClass('hidden');

            // ID & Dates
            $('#bill-id-display').text(bill.bill_id);
            $('#bill-date-time').text(`${bill.bill_date} ${bill.bill_time}`);

            // Patient
            $('#patient-name').text(bill.patient_name);
            $('#patient-id').text(bill.patient_id);
            $('#patient-age-sex').text(`${bill.age}Y / ${bill.sex}`);
            $('#patient-phone').text(bill.phone || '-');
            $('#patient-address').text(bill.address || '-');

            // Doctor
            $('#doctor-name').text(bill.doctor_name || 'Walk-in');
            $('#doctor-specialization').text(bill.specialization || 'General Consultation');
            $('#appointment-id').text(bill.appointment_id || 'Direct Visit');

            // Items
            const items = bill.items || [];
            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <tr class="border-b border-gray-100">
                        <td class="py-4">
                            <p class="font-semibold text-gray-900">${item.item_name}</p>
                            <p class="text-xs text-gray-400">${item.item_description || ''}</p>
                        </td>
                        <td class="py-4 text-center text-gray-600 text-sm">${item.item_type}</td>
                        <td class="py-4 text-center text-gray-900 font-medium">${item.quantity}</td>
                        <td class="py-4 text-right text-gray-900 font-medium">₹${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td class="py-4 text-right text-gray-900 font-bold">₹${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });
            $('#items-tbody').html(itemsHtml);

            // Calculation Summary
            $('#subtotal').text(`₹${parseFloat(bill.subtotal).toFixed(2)}`);
            $('#discount').text(`-₹${parseFloat(bill.discount_amount).toFixed(2)}`);
            $('#taxable').text(`₹${parseFloat(bill.taxable_amount).toFixed(2)}`);
            $('#tax').text(`₹${parseFloat(bill.tax_amount).toFixed(2)}`);
            $('#grand-total').text(`₹${parseFloat(bill.grand_total).toFixed(2)}`);

            // Payment Summary
            $('#total-paid').text(`₹${parseFloat(bill.amount_paid).toFixed(2)}`);
            $('#balance-due').text(`₹${parseFloat(bill.balance_due).toFixed(2)}`);
            
            const status = bill.payment_status;
            let statusClass = 'bg-gray-200 text-gray-600';
            if (status === 'Paid') statusClass = 'bg-green-100 text-green-600';
            else if (status === 'Partial') statusClass = 'bg-yellow-100 text-yellow-600';
            else if (status === 'Pending') statusClass = 'bg-red-100 text-red-600';
            
            $('#payment-status').text(status).addClass(statusClass);
        }

        function showError() {
            $('#loading').addClass('hidden');
            $('#error').removeClass('hidden');
        }
    </script>
</body>
</html>
