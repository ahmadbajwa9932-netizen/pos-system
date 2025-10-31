@extends('layouts.app')

@section('title', 'Generate Receipt')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/make_a_sale/invoice.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<style>
/* Cross button hover effect */
#clearBalanceBtn:hover {
    background: #dc3545 !important;
    color: white !important;
}

#clearBalanceBtn:active {
    transform: translateY(-50%) scale(0.95);
}
.input-error {
    border: 2px solid red !important;
    box-shadow: 0 0 6px rgba(255, 0, 0, 0.6);
    transition: all 0.3s ease;
}

    .swal-custom-btn {
        background-color: #808080 !important;
        color: white !important;
        font-size: 12px !important;
        padding: 6px 14px !important;
        border-radius: 4px !important;
    }

    .swal-close-btn {
        font-size: 14px !important;
        color: #555 !important;
    }

    .swal2-popup {
        font-size: 13px !important;
    }

    .discount-amount-display {
        font-size: 11px;
        color: #28a745;
        font-weight: 500;
        margin-top: 2px;
        display: none;
    }

    /* ✅ Customer name wrapper - normal z-index */
/* ✅ Make all form groups relative for positioning */
.customer-section .form-group {
    position: relative;
    z-index: 1;
}

/* ✅ Increase z-index when field is focused */
.customer-section .form-group:focus-within {
    z-index: 100 !important;
}

/* ✅ Customer name wrapper - normal z-index */
.customer-name-wrapper {
    position: relative;
    z-index: 10 !important;
}

.customer-name-wrapper:focus-within {
    z-index: 100 !important;
}

/* ✅ All Autocomplete lists - same styling */
#customerAutocompleteList,
#cityAutocompleteList,
#contactAutocompleteList,
#shopAutocompleteList {
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1001 !important;
    width: 100%;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-top: 2px;
    top: 100%;
    left: 0;
    display: none;
}

/* ✅ CRITICAL: Force hide when SweetAlert is shown */
body.swal2-shown #customerAutocompleteList,
body.swal2-shown #cityAutocompleteList,
body.swal2-shown #contactAutocompleteList,
body.swal2-shown #shopAutocompleteList,
.swal2-shown #customerAutocompleteList,
.swal2-shown #cityAutocompleteList,
.swal2-shown #contactAutocompleteList,
.swal2-shown #shopAutocompleteList {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}
    .sale-discount-amount {
        font-size: 12px;
        color: #28a745;
        font-weight: 500;
        margin-left: 5px;
        display: none;
    }

    .discount-container {
        position: relative;
    }

    /* ✅ Autocomplete styling */
    .autocomplete-list {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 9999 !important;
        width: 100%;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 2px;
        top: 100%;
        left: 0;
    }

    .autocomplete-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s;
        background: white;
    }

    .autocomplete-item:last-child {
        border-bottom: none;
    }

    .autocomplete-item:hover {
        background-color: #f8f9fa;
    }

    .customer-info {
        line-height: 1.5;
    }

    .customer-info strong {
        display: block;
        color: #333;
        font-size: 14px;
        margin-bottom: 3px;
    }

    .customer-info small {
        font-size: 11px;
        color: #6c757d;
    }

    .product-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .product-main {
        flex: 1;
    }

    .product-details {
        display: block;
        font-size: 11px;
        color: #6c757d;
        margin-top: 3px;
    }

    .product-actions {
        display: flex;
        gap: 6px;
    }

    .btn-add, .btn-history {
        padding: 4px 8px;
        font-size: 11px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    .btn-add {
        background-color: #28a745;
        color: white;
    }

    .btn-history {
        background-color: #17a2b8;
        color: white;
    }

    .btn-add:hover, .btn-history:hover {
        opacity: 0.9;
    }

    .no-results {
        padding: 15px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
        font-size: 13px;
        background: white;
    }

    .badge {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .badge-success { background-color: #28a745; color: white; }
    .badge-warning { background-color: #ffc107; color: #212529; }
    .badge-primary { background-color: #007bff; color: white; }

    /* ✅ Form group positioning */
    /* .form-group {
        position: relative;
        z-index: 1;
    } */

    /* ✅ Customer name field gets highest z-index */
    /* .customer-name-wrapper {
        position: relative;
        z-index: 9998 !important;
    } */

    /* .customer-section .form-group {
        position: relative;
    } */
     /* ✅ Toast animation */
@keyframes fadeInOut {
    0% {
        opacity: 0;
        transform: translateY(-5px);
    }
    10% {
        opacity: 1;
        transform: translateY(0);
    }
    90% {
        opacity: 1;
        transform: translateY(0);
    }
    100% {
        opacity: 0;
        transform: translateY(-5px);
    }
}

.price-toast {
    animation: fadeInOut 3s ease-in-out;
}
</style>
@endpush

@section('content')
@if(session('success') || session('error'))
    <div id="popup-message" class="popup {{ session('success') ? 'success' : 'error' }}">
        {{ session('success') ?? session('error') }}
    </div>
@endif

<div class="container">
    <div class="header">
        <h2>POS Invoice Generation</h2>
        <p>Professional Point of Sale System</p>
    </div>

    <form id="receiptForm" method="POST" action="{{ route('sales.store') }}">
        @csrf

        <div class="pos-layout">
            <!-- Left Column - Customer Details & Product Search -->
            <div class="left-column">
                <!-- Customer Details -->
                <div class="customer-section">
                    <h3>Customer Information</h3>
                    <div class="customer-row">
                        <div class="form-group customer-name-wrapper">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" placeholder="Enter customer name">
                            <div id="customerAutocompleteList" ></div>
                        </div>
                        <div class="form-group" id="shopNameWrapper" style="display: none;">
                            <label for="due_date">Shop Name</label>
                            <input type="text" name="shop_name" id="shop_name" placeholder="shop name">
                            <div id="shopAutocompleteList" class="autocomplete-list"></div>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" placeholder="City">
                            <div id="cityAutocompleteList" class="autocomplete-list"></div>
                        </div>
                    </div>
                    
                    <div class="customer-row">
                        <div class="form-group">
                            <label for="contact">Contact No</label>
                            <input type="text" name="contact" id="contact" placeholder="Phone number">
                            <div id="contactAutocompleteList" class="autocomplete-list"></div>
                        </div>
                        <div class="form-group">
                            <label for="voucher_no">Voucher No</label>
                            <input type="text" id="voucher_no" name="voucher_no" readonly>
                        </div>
                    </div>

                    <div class="customer-row">
                        <div class="form-group">
                            <label for="customer_type">Customer Type</label>
                            <select name="customer_type" id="customer_type" required>
                                <option value="cash">Cash Customer</option>
                                <option value="credit">Credit Customer</option>
                                <option value="card">Card Customer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_type">Payment Method</label>
                            <select name="payment_type" id="payment_type" required readonly class="readonly-select">
                                <option value="cash">Cash</option>
                                <option value="credit">Credit</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="form-group" id="dueDateWrapper" style="display: none;">
                            <label for="due_date">Due Date</label>
                            <input type="date" name="due_date" id="due_date">
                        </div>
                    </div>
                </div>

                <!-- Product Search -->
                <div class="product-section">
                    <h3>Add Products</h3>
                    <input type="text" id="productSearch" class="search-bar" placeholder="Search products by name...">
                    <div id="autocompleteList" class="autocomplete-list" style="display: none;"></div>
                </div>
            </div>

            <!-- Right Column - Items Table & Totals -->
            <div class="right-column">
                <!-- Items Table -->
                <div class="table-container">
                    <table id="itemsTable">
                       <!-- 🎯 Current Balance Alert with Close Button -->
<div id="currentBalanceAlert" style="display: none; background: #ffe6e6; border-left: 4px solid #dc3545; padding: 10px 15px; margin-bottom: 12px; border-radius: 4px; position: relative;">
    <span style="color: #721c24; font-size: 13px; font-weight: 600;">
        Last Balance: 
    </span>
    <span id="currentBalanceAmount" style="color: #dc3545; font-size: 14px; font-weight: bold; margin-left: 8px;">
        Rs 0.00
    </span>
    <span style="color: #721c24; font-size: 11px; margin-left: 12px; opacity: 0.8;">
        (Will be added to final amount)
    </span>
    <!-- ❌ Close Button -->
    <button type="button" id="clearBalanceBtn" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #721c24; font-size: 20px; font-weight: bold; cursor: pointer; padding: 0; width: 24px; height: 24px; line-height: 20px; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#dc3545'; this.style.color='white';" onmouseout="this.style.background='none'; this.style.color='#721c24';" title="Remove previous balance from this transaction">
        ×
    </button>
</div>
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic rows will be added here -->
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="totals-section">
                    <div class="totals-grid">
                        <div class="total-item">
                            <label>Subtotal:</label>
                            <input type="text" name="subtotal" id="subtotal" readonly>
                        </div>
</div>
<div class="totals-section" style="border:none;">
                    <div class="totals-grid">
                        <div class="total-item">
                            <label>Discount Type:</label>
                            <select name="discount_type" id="discount_type">
                                <option value="amount">Amount</option>
                                <option value="percentage" selected>Percentage</option>
                            </select>
                        </div>

                        <div class="total-item">
                            <label>Discount:</label>
                            <input type="number" name="discount" id="discount" class="spinner" value="0" step="0.01">
                            <span id="saleDiscountAmount" class="sale-discount-amount"></span>
                        </div>

                        <div class="total-item">
  <label>Tax Type:</label>
  <select name="tax_type" id="tax_type">
        <option value="amount">Amount</option>
        <option value="percentage">Percentage</option>
    </select>
</div>

<div class="total-item">
    <label>Tax:</label>
    <input type="number" name="tax" id="tax" class="spinner" value="0" step="0.01">
    <span id="taxAmount" class="sale-discount-amount"></span>
</div>
</div>
                    </div>

                    <div class="totals-row">
                        <div class="total-item">
                            <label>Received Amount:</label>
                            <input type="number" class="spinner" name="received_amount" id="received_amount" step="0.01" placeholder="0.00">
                        </div>

                        <div class="total-item">
                            <label>Change Amount:</label>
                            <input type="number" name="change_amount" id="change_amount" readonly>
                        </div>

                        <!-- 🔹 New field (hidden by default) -->
    <div class="total-item" id="remaining-container" style="display: none;">
        <label>Remaining Amount:</label>
        <input type="number" name="remaining_amount" id="remaining_amount" readonly>
    </div>
                    </div>

                    <div class="grand-total total-item">
                        <label>Grand Total:</label>
                        <input type="text" name="grand_total" id="grand_total" readonly>
                    </div>
                    <!-- 🎯 Final Amount (Grand Total + Current Balance) -->
<!-- 🎯 Final Amount (Matches your existing total-item style) -->
<!-- 🎯 Clean Final Amount Row -->
<div class="total-item" id="finalAmountContainer" style="display: none; border-top: 2px solid rgb(10, 63, 107); padding-top: 8px; margin-top: 8px;">
    <label style="color: #721c24; font-weight: 600;">Total Payable:</label>
    <input type="text" id="final_amount" readonly style="color: #dc3545; font-weight: bold; font-size: 16px;">
</div>
                </div>



                <!-- Hidden Status Field -->
                <input type="hidden" name="status" id="status" value="paid">
<!-- 🎯 Hidden Current Balance -->
<input type="hidden" id="hidden_current_balance" value="0">
                <!-- Submit Button -->
                <div class="submit-section">
                <input type="hidden" name="action_type" id="action_type" value="generate">

<!-- Save only -->
<button type="submit" class="btn-submit" 
        onclick="document.getElementById('action_type').value='generate'">
    Save Record
</button>

<!-- Save and Print -->
<button type="submit" class="btn-print"
        onclick="document.getElementById('action_type').value='print'">
    Save & Print Receipt
</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- SweetAlert for stock check --}}
<script src="{{asset('js/sweetAlert2.all.min.js')}}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // 1. ELEMENT REFERENCES (Declare once!)
    // ========================================
    const productSearch = document.getElementById('productSearch');
    const autocompleteList = document.getElementById('autocompleteList');
    const itemsTableBody = document.querySelector('#itemsTable tbody');
    const subtotalInput = document.getElementById('subtotal');
    const discountTypeSel = document.getElementById('discount_type');
    const discountInput = document.getElementById('discount');
    const taxInput = document.getElementById('tax');
    const grandTotalInput = document.getElementById('grand_total');
    const receivedInput = document.getElementById('received_amount');
    const changeInput = document.getElementById('change_amount');
    const voucherNoInput = document.getElementById('voucher_no');
    const saleDiscountAmountSpan = document.getElementById('saleDiscountAmount');
    const taxTypeSel = document.getElementById('tax_type');
const taxAmountSpan = document.getElementById('taxAmount');
    
    // Customer elements
    const customerInput = document.getElementById("customer_name");
    const customerList = document.getElementById("customerAutocompleteList");
    const cityInput = document.getElementById("city");
    const contactInput = document.getElementById("contact");
    const shopInput = document.getElementById("shop_name");
    
    // Customer type elements
    const customerType = document.getElementById('customer_type');
    const paymentType = document.getElementById('payment_type');
    const dueDateWrapper = document.getElementById('dueDateWrapper');
    const dueDateInput = document.getElementById('due_date');
    const shopNameWrapper = document.getElementById('shopNameWrapper');
    const shopNameInput = document.getElementById('shop_name');
    
    // Balance elements
    const clearBalanceBtn = document.getElementById('clearBalanceBtn');
    const hiddenCurrentBalance = document.getElementById('hidden_current_balance');
    const currentBalanceAlert = document.getElementById('currentBalanceAlert');
    const finalAmountContainer = document.getElementById('finalAmountContainer');
    const finalAmountInput = document.getElementById('final_amount');
    const remainingContainer = document.getElementById('remaining-container');
    const remainingInput = document.getElementById('remaining_amount');
    
    // Variables
    let selectedCustomerId = null;
    let itemIndex = 0;
    const allCustomers = @json($customers);
    const allProducts = @json($products);

    // ========================================
    // 2. HELPER FUNCTIONS
    // ========================================
    function fuzzyMatchCustomer(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) {
                tIndex++;
            }
        }
        return tIndex === token.length;
    }

    function fuzzyMatch(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) {
                tIndex++;
            }
        }
        return tIndex === token.length;
    }

    function highlightField(field) {
        if (!field) return;
        field.classList.add('input-error');
        field.focus();
        field.addEventListener('input', () => {
            field.classList.remove('input-error');
        }, { once: true });
    }

    // ========================================
    // 3. BALANCE FUNCTIONS
    // ========================================
    function fetchCustomerBalance(customerId) {
        if (!customerId) {
            hideCurrentBalance();
            return;
        }

        fetch(`/customer-balance/${customerId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.current_balance > 0) {
                    showCurrentBalance(data.current_balance);
                } else {
                    hideCurrentBalance();
                }
            })
            .catch(err => {
                console.error('Balance fetch error:', err);
                hideCurrentBalance();
            });
    }

    function showCurrentBalance(balance) {
        if (currentBalanceAlert) {
            currentBalanceAlert.style.display = 'block';
        }
        
        const currentBalanceAmount = document.getElementById('currentBalanceAmount');
        if (currentBalanceAmount) {
            currentBalanceAmount.textContent = `Rs ${balance.toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        }
        
        if (hiddenCurrentBalance) {
            hiddenCurrentBalance.value = balance;
        }
        
        calculateTotals();
    }

    function hideCurrentBalance() {
        if (currentBalanceAlert) {
            currentBalanceAlert.style.display = 'none';
        }
        
        const currentBalanceAmount = document.getElementById('currentBalanceAmount');
        if (currentBalanceAmount) {
            currentBalanceAmount.textContent = 'Rs 0.00';
        }
        
        if (hiddenCurrentBalance) {
            hiddenCurrentBalance.value = 0;
        }
        
        if (finalAmountContainer) {
            finalAmountContainer.style.display = 'none';
        }
        
        calculateTotals();
    }

// ========================================
// 4. CUSTOMER AUTOCOMPLETE FOR ALL FIELDS
// ========================================

// Function to create autocomplete for a specific field
function setupCustomerAutocomplete(inputField, listElement, searchField) {
    if (!inputField || !listElement) return;

    inputField.addEventListener("input", function () {
        const query = this.value.toLowerCase().trim();
        listElement.innerHTML = "";

        if (query.length < 2) {
            listElement.style.display = "none";
            return;
        }

        const tokens = query.split(/\s+/);

        const matchedCustomers = allCustomers.filter(cust => {
            if (cust.name && cust.name.toLowerCase() === "walk-in customer") {
                return false;
            }
            
            // Search only in the specific field
            let searchText = "";
            switch(searchField) {
                case 'name':
                    searchText = (cust.name || "").toLowerCase();
                    break;
                case 'city':
                    searchText = (cust.city || "").toLowerCase();
                    break;
                case 'contact':
                    searchText = (cust.contact || "").toLowerCase();
                    break;
                case 'shop':
                    searchText = (cust.shop_name || "").toLowerCase();
                    break;
            }
            
            return tokens.every(token =>
                searchText.includes(token) || fuzzyMatchCustomer(searchText, token)
            );
        });

        if (matchedCustomers.length > 0) {
            matchedCustomers.slice(0, 10).forEach(cust => {
                const item = document.createElement("div");
                item.classList.add("autocomplete-item");
                
                const details = [];
                if (cust.shop_name) details.push(`Shop: ${cust.shop_name}`);
                if (cust.city) details.push(cust.city);
                if (cust.contact) details.push(cust.contact);
                
                item.innerHTML = `
                    <div class="product-info">
                        <div class="product-main">
                            <strong>${cust.name}</strong>
                            ${details.length > 0 ? `<span class="product-details">${details.join(' | ')}</span>` : ''}
                        </div>
                    </div>
                `;

                item.addEventListener("click", function () {
                    customerInput.value = cust.name;
                    if (cityInput) cityInput.value = cust.city || "";
                    if (contactInput) contactInput.value = cust.contact || "";
                    if (shopInput) shopInput.value = cust.shop_name || "";
                    
                    // Hide all autocomplete lists
                    document.querySelectorAll('[id$="AutocompleteList"]').forEach(list => {
                        list.style.display = "none";
                    });
                    
                    selectedCustomerId = cust.id;
                    fetchCustomerBalance(cust.id);
                });

                listElement.appendChild(item);
            });
            listElement.style.display = "block";
        } else {
            listElement.style.display = "none";
        }
    });

    // Clear fields when input is cleared
    inputField.addEventListener("keyup", function() {
        if (this.value.trim() === "") {
            if (cityInput) cityInput.value = "";
            if (contactInput) contactInput.value = "";
            if (shopInput) shopInput.value = "";
            if (customerInput) customerInput.value = "";
            selectedCustomerId = null;
            hideCurrentBalance();
        }
    });

    // Hide on blur
    inputField.addEventListener("blur", function() {
        setTimeout(() => {
            listElement.style.display = "none";
        }, 200);
    });

    // Show parent z-index on focus
    inputField.addEventListener("focus", function() {
        const parentGroup = this.closest('.form-group');
        if (parentGroup) {
            parentGroup.style.zIndex = '100';
        }
    });

    // Reset z-index on blur
    inputField.addEventListener("blur", function() {
        setTimeout(() => {
            const parentGroup = this.closest('.form-group');
            if (parentGroup && !parentGroup.classList.contains('customer-name-wrapper')) {
                parentGroup.style.zIndex = '1';
            }
        }, 300);
    });
}

// Setup autocomplete for all customer fields with specific search fields
if (customerInput && customerList) {
    setupCustomerAutocomplete(customerInput, customerList, 'name');
}

if (cityInput) {
    const cityList = document.getElementById('cityAutocompleteList');
    setupCustomerAutocomplete(cityInput, cityList, 'city');
}

if (contactInput) {
    const contactList = document.getElementById('contactAutocompleteList');
    setupCustomerAutocomplete(contactInput, contactList, 'contact');
}

if (shopInput) {
    const shopList = document.getElementById('shopAutocompleteList');
    setupCustomerAutocomplete(shopInput, shopList, 'shop');
}

    // ========================================
    // 5. CLEAR BALANCE BUTTON
    // ========================================
    if (clearBalanceBtn) {
        clearBalanceBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if Swal is loaded
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 not loaded');
                hideCurrentBalance();
                return;
            }
            
            Swal.fire({
                title: 'Remove Previous Balance?',
                html: `
                    <p style="font-size:13px;">
                        This will remove the previous balance from this transaction only.<br>
                        The customer's actual balance will remain unchanged.
                    </p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Remove',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'swal-custom-btn',
                    cancelButton: 'swal-custom-btn',
                    closeButton: 'swal-close-btn'
                },
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    hideCurrentBalance();
                    
                    Swal.fire({
                        title: 'Removed!',
                        text: 'Previous balance removed from this transaction.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal2-popup'
                        }
                    });
                }
            });
        });
    }

    // ========================================
    // 6. CUSTOMER TYPE & PAYMENT SYNC
    // ========================================
    function syncPaymentType() {
        if (!customerType || !paymentType) return;
        const selectedType = customerType.value;
        if (['cash', 'credit', 'card'].includes(selectedType)) {
            paymentType.value = selectedType;
        }
    }

    if (paymentType) {
        paymentType.addEventListener('mousedown', function (e) {
            e.preventDefault();
            this.blur();
            return false;
        });
    }

    if (customerType) {
        customerType.addEventListener('change', syncPaymentType);
        syncPaymentType();
    }

    function toggleDueDateAndShopName() {
        if (!customerType || !paymentType || !dueDateWrapper || !shopNameWrapper) return;
        
        if (customerType.value === 'credit' && paymentType.value === 'credit') {
            dueDateWrapper.style.display = 'block';
            shopNameWrapper.style.display = 'block';
        } else {
            dueDateWrapper.style.display = 'none';
            if (dueDateInput) dueDateInput.value = '';
            shopNameWrapper.style.display = 'none';
        }
    }

    if (customerType && paymentType) {
        customerType.addEventListener('change', toggleDueDateAndShopName);
        paymentType.addEventListener('change', toggleDueDateAndShopName);
        toggleDueDateAndShopName();
    }

    // ========================================
    // 7. FETCH VOUCHER NUMBER
    // ========================================
    if (voucherNoInput) {
        fetch("{{ url('/sales/next-voucher-no') }}")
            .then(response => response.json())
            .then(data => {
                voucherNoInput.value = data.voucher_no;
            })
            .catch(error => console.error('Error fetching voucher number:', error));
    }

    // ========================================
    // 8. PRODUCT SEARCH AUTOCOMPLETE
    // ========================================
    if (productSearch && autocompleteList) {
        productSearch.addEventListener("input", function () {
            const query = this.value.toLowerCase().trim();
            autocompleteList.innerHTML = "";

            if (query.length < 2) {
                autocompleteList.style.display = "none";
                return;
            }

            const tokens = query.split(/\s+/);

            const matchedProducts = allProducts.filter(product => {
                const name = product.name.toLowerCase();
                return tokens.every(token => name.includes(token) || fuzzyMatch(name, token));
            });

            if (matchedProducts.length > 0) {
                matchedProducts.forEach(product => {
                    const item = document.createElement("div");
                    item.classList.add("autocomplete-item");
                    item.innerHTML = `
                        <div class="product-info">
                            <div class="product-main">
                                <strong>${product.name}</strong>
                                <span class="product-details">
                                    Unit: ${product.unit} |
                                    Stock: ${product.available_stock}
                                </span>
                            </div>
                            <div class="product-actions">
                                <button type="button" class="btn-history" onclick="showProductHistory(${product.id}, '${product.name}')">History</button>
                            </div>
                        </div>
                    `;

                    item.addEventListener("click", function (e) {
                        if (e.target.closest(".btn-history")) return;
                        addProductRow(product);
                        productSearch.value = "";
                        autocompleteList.style.display = "none";
                    });

                    autocompleteList.appendChild(item);
                });
            } else {
                autocompleteList.innerHTML = '<div class="no-results">No products found</div>';
            }

            autocompleteList.style.display = "block";
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function (event) {
            if (!productSearch.contains(event.target) && !autocompleteList.contains(event.target)) {
                autocompleteList.style.display = "none";
            }
        });
    }


    // ========================================
    // 9. ADD PRODUCT ROW
    // ========================================
    // ========================================
// 🎯 PRICE VALIDATION FUNCTION
// ========================================
function validatePrice(priceInput, purchasedPrice, productName) {
    const enteredPrice = parseFloat(priceInput.value) || 0;
    
    if (enteredPrice > 0 && enteredPrice < purchasedPrice) {
        priceInput.style.color = "red"; // below cost → red
    } else {
        priceInput.style.color = ""; // reset to default
    }
    
    if (enteredPrice > 0 && enteredPrice < purchasedPrice) {
        // Auto-correct to purchased price
        priceInput.value = purchasedPrice.toFixed(2);
        
        // Show toast message under the input
        const row = priceInput.closest('tr');
        const priceCell = priceInput.closest('td');
        
        // Remove existing toast if any
        const existingToast = priceCell.querySelector('.price-toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // Create toast message
        const toast = document.createElement('div');
        toast.className = 'price-toast';
        toast.style.cssText = `
            position: absolute;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 2px;
            z-index: 1000;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: fadeInOut 3s ease-in-out;
        `;
        toast.textContent = `Price adjusted to Rs ${purchasedPrice.toFixed(2)}`;
        
        // Make parent cell relative
        priceCell.style.position = 'relative';
        priceCell.appendChild(toast);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
        
        // Recalculate row
        recalcRow(row);
        calculateTotals();
        
        return false;
    }
    
    return true;
}
    function addProductRow(product) {
        if (!itemsTableBody) return;
        
        const price = parseFloat(product.price).toFixed(2);
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="text-align: left; padding-left: 8px;">
                <strong>${product.name}</strong>
                <input type="hidden" name="items[${itemIndex}][purchase_id]" value="${product.id}">
                <input type="hidden" name="items[${itemIndex}][total]" class="total_raw" value="${price}">
                <input type="hidden" name="items[${itemIndex}][discount_amount]" class="discount_amount" value="0">
                <input type="hidden" name="items[${itemIndex}][total_after_discount]" class="total_after_discount" value="${price}">
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="qty spinner" value="1" min="1"></td>
            <td>${product.unit}</td>
            <td><input type="number" name="items[${itemIndex}][price]" class="price spinner" value="${price}" step="0.01" min="0" style="width: 80px; text-align: right;"></td>
            <td>
                <div class="discount-container">
                    <select name="items[${itemIndex}][discount_type]" class="discount_type">
                        <option value="amount">Amt</option>
                        <option value="percentage">%</option>
                    </select>
                    <input type="number" name="items[${itemIndex}][discount_value]" class="discount_value  spinner" value="0" step="0.01" min="0">
                    <div class="discount-amount-display"></div>
                </div>
            </td>
            <td class="value">${price}</td>
            <td><button type="button" class="removeRow">×</button></td>
        `;
        itemsTableBody.appendChild(row);
        itemIndex++;
        recalcRow(row);
        calculateTotals();
        
       // ✅ Price validation listener
const priceInput = row.querySelector('.price');
const purchasedPrice = parseFloat(product.purchased_price) || parseFloat(product.price);

//  🎯 Real-time color change while typing
priceInput.addEventListener('input', function() {
    const enteredPrice = parseFloat(this.value) || 0;
    
    // Change color to red if less than purchased price
    if (enteredPrice > 0 && enteredPrice < purchasedPrice) {
        this.style.color = '#dc3545';
        this.style.fontWeight = 'bold';
    } else {
        this.style.color = '';
        this.style.fontWeight = '';
    }
    
    recalcRow(row);
    calculateTotals();
});

priceInput.addEventListener('blur', function() {
    validatePrice(this, purchasedPrice, product.name);
});

priceInput.addEventListener('input', () => {
    recalcRow(row);
    calculateTotals();
});

// Store purchased price as data attribute for later validation
priceInput.setAttribute('data-purchased-price', purchasedPrice);
priceInput.setAttribute('data-product-name', product.name);

        // Stock check
        const existingRows = Array.from(itemsTableBody.querySelectorAll('tr'));
        const sameProductRows = existingRows.filter(r => 
            r.querySelector('input[name*="[purchase_id]"]').value === String(product.id)
        );
        const totalQty = sameProductRows.reduce((sum, r) => sum + parseInt(r.querySelector('.qty').value || 0), 0);

        fetch(`/check-stock/${product.id}?quantity=${totalQty}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'insufficient' && typeof Swal !== 'undefined') {
                    const remaining = totalQty - data.available;
                    
                    Swal.fire({
                        icon: 'warning',
                        title: '<span style="font-size:14px;">Stock Warning</span>',
                        html: `
                            <p style="font-size:13px; margin:0;">
                                Only <strong>${data.available}</strong> units of 
                                <strong style="color:#d33;">${product.name}</strong> are available.<br>
                                Remaining (oversold): <strong style="color:#d33;">-${remaining}</strong>
                            </p>`,
                        confirmButtonText: 'OK',
                        width: '330px',
                        padding: '0.8em',
                        customClass: { confirmButton: 'swal-custom-btn', closeButton: 'swal-close-btn' },
                        showCloseButton: true,
                        position: 'top-start',
                        backdrop: false
                    });
                }else {
            // ✅ NEW: Auto-close if stock is now sufficient
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }
        }
                calculateTotals();
            })
            .catch(err => console.error('Stock check error:', err));
    }

 // ========================================
// 10. REMOVE ROW
// ========================================
if (itemsTableBody) {
    itemsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('removeRow')) {
            const row = e.target.closest('tr');
            const purchaseId = row.querySelector('input[name*="[purchase_id]"]')?.value;
            const productName = row.querySelector('strong')?.textContent.trim();
            
            // Remove the row
            row.remove();
            calculateTotals();
            
            // ✅ Check if remaining quantity is valid after row removal
            if (purchaseId && typeof Swal !== 'undefined') {
                const remainingRows = Array.from(itemsTableBody.querySelectorAll('tr'));
                const sameProductRows = remainingRows.filter(r => 
                    r.querySelector('input[name*="[purchase_id]"]')?.value === purchaseId
                );
                
                const totalQty = sameProductRows.reduce((sum, r) => 
                    sum + parseInt(r.querySelector('.qty')?.value || 0), 0
                );
                
                // Check stock for remaining quantity
                fetch(`/check-stock/${purchaseId}?quantity=${totalQty}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'insufficient') {
                            // ✅ Still oversold, UPDATE the popup with new oversold amount
                            const oversold = totalQty - data.available;
                            
                            Swal.fire({
                                icon: 'warning',
                                title: '<span style="font-size:14px;">Stock Warning</span>',
                                html: `
                                    <p style="font-size:13px; margin:0;">
                                        Only <strong>${data.available}</strong> units of 
                                        <strong style="color:#d33;">${productName}</strong> are available.<br>
                                        Remaining (oversold): <strong style="color:#d33;">-${oversold}</strong>
                                    </p>`,
                                confirmButtonText: 'OK',
                                width: '330px',
                                padding: '0.8em',
                                customClass: { 
                                    confirmButton: 'swal-custom-btn', 
                                    closeButton: 'swal-close-btn' 
                                },
                                showCloseButton: true,
                                position: 'top-start',
                                backdrop: false
                            });
                        } else {
                            // ✅ Stock is now sufficient, close any open warning
                            if (Swal.isVisible()) {
                                Swal.close();
                            }
                        }
                    })
                    .catch(err => console.error('Stock check error:', err));
            }
        }
    });
}

    // ========================================
    // 11. ROW CHANGES
    // ========================================
    function handleRowChange(e) {
        if (e.target.classList.contains('qty') ||
            e.target.classList.contains('discount_value') ||
            e.target.classList.contains('discount_type')) {
            const row = e.target.closest('tr');
            recalcRow(row);
            calculateTotals();
        }
    }

    // ========================================
// 🎯 ITEM-LEVEL DISCOUNT VALIDATION
// ========================================
if (itemsTableBody) {
    itemsTableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('discount_value')) {
            const row = e.target.closest('tr');
            const discountInput = e.target;
            const discountType = row.querySelector('.discount_type').value;
            
            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;
            const totalRaw = qty * price;
            
            let currentValue = parseFloat(discountInput.value);
            
            if (discountType === 'percentage') {
                // Limit percentage to 100
                if (currentValue > 100) {
                    discountInput.value = 100;
                }
            } else {
                // Limit amount to total
                if (currentValue > totalRaw) {
                    discountInput.value = totalRaw.toFixed(2);
                }
            }
        }
    });
}

    if (itemsTableBody) {
        itemsTableBody.addEventListener('input', handleRowChange);
        itemsTableBody.addEventListener('change', handleRowChange);
    }

    function recalcRow(row) {
        if (!row) return;
        
        const qty = Math.max(parseFloat(row.querySelector('.qty')?.value) || 0, 0);
        const price = Math.max(parseFloat(row.querySelector('.price')?.value) || 0, 0);
        const dType = row.querySelector('.discount_type')?.value || 'amount';
        const dValue = Math.max(parseFloat(row.querySelector('.discount_value')?.value) || 0, 0);
        const discountAmountDisplay = row.querySelector('.discount-amount-display');

        let totalRaw = qty * price;
        let discountAmt = dType === 'percentage' ? (totalRaw * dValue / 100) : dValue;
        discountAmt = Math.min(Math.max(discountAmt, 0), totalRaw);
        const totalAfter = totalRaw - discountAmt;

        const valueCell = row.querySelector('.value');
        const totalRawInput = row.querySelector('.total_raw');
        const discountAmountInput = row.querySelector('.discount_amount');
        const totalAfterInput = row.querySelector('.total_after_discount');

        if (valueCell) valueCell.textContent = totalAfter.toFixed(2);
        if (totalRawInput) totalRawInput.value = totalRaw.toFixed(2);
        if (discountAmountInput) discountAmountInput.value = discountAmt.toFixed(2);
        if (totalAfterInput) totalAfterInput.value = totalAfter.toFixed(2);

        if (discountAmountDisplay) {
            if (dType === 'percentage' && dValue > 0) {
                discountAmountDisplay.textContent = `(-${discountAmt.toFixed(2)})`;
                discountAmountDisplay.style.display = 'block';
            } else {
                discountAmountDisplay.style.display = 'none';
            }
        }
    }

    // ========================================
    // 12. CALCULATE TOTALS
    // ========================================
    function calculateTotals() {
        if (!itemsTableBody || !subtotalInput || !grandTotalInput) return;
        
        let subtotal = 0;
        itemsTableBody.querySelectorAll('.total_after_discount').forEach(inp => {
            subtotal += parseFloat(inp.value) || 0;
        });
        subtotalInput.value = subtotal.toFixed(2);

        const discountType = discountTypeSel?.value || 'amount';
        const discountVal = Math.max(parseFloat(discountInput?.value) || 0, 0);
        const taxType = taxTypeSel?.value || 'amount';
const taxVal = Math.max(parseFloat(taxInput?.value) || 0, 0);

let taxAmount = 0;
if (taxType === 'percentage') {
    taxAmount = (subtotal * taxVal) / 100;
} else {
    taxAmount = taxVal;
}
taxAmount = Math.min(Math.max(taxAmount, 0), subtotal);

// Show calculated tax amount if percentage
if (taxAmountSpan) {
    if (taxType === 'percentage' && taxVal > 0) {
        taxAmountSpan.textContent = `(+${taxAmount.toFixed(2)})`;
        taxAmountSpan.style.display = 'inline';
    } else {
        taxAmountSpan.style.display = 'none';
    }
}
        let overallDiscountAmount = 0;
        if (discountType === 'percentage') {
            overallDiscountAmount = (subtotal * discountVal) / 100;
        } else {
            overallDiscountAmount = discountVal;
        }
        overallDiscountAmount = Math.min(Math.max(overallDiscountAmount, 0), subtotal);

        if (saleDiscountAmountSpan) {
            if (discountType === 'percentage' && discountVal > 0) {
                saleDiscountAmountSpan.textContent = `(-${overallDiscountAmount.toFixed(2)})`;
                saleDiscountAmountSpan.style.display = 'inline';
            } else {
                saleDiscountAmountSpan.style.display = 'none';
            }
        }

        const grandTotal = subtotal - overallDiscountAmount + taxAmount;
        grandTotalInput.value = grandTotal.toFixed(2);

        // Calculate Final Amount with current balance
        const currentBalance = parseFloat(hiddenCurrentBalance?.value) || 0;
        
        if (finalAmountContainer && finalAmountInput) {
            if (currentBalance > 0) {
                const finalAmount = grandTotal + currentBalance;
                finalAmountInput.value = finalAmount.toFixed(2);
                finalAmountContainer.style.display = 'flex';
            } else {
                finalAmountContainer.style.display = 'none';
            }
        }

        // Calculate change/remaining
        const received = Math.max(parseFloat(receivedInput?.value) || 0, 0);
        const totalPayable = grandTotal + currentBalance;

        const change = received > totalPayable ? (received - totalPayable) : 0;
        if (changeInput) changeInput.value = change.toFixed(2);

        if (remainingContainer && remainingInput) {
            if (!isNaN(received) && received > 0) {
                if (received < totalPayable) {
                    const remaining = totalPayable - received;
                    remainingInput.value = remaining.toFixed(2);
                    remainingContainer.style.display = "block";
                } else {
                    remainingContainer.style.display = "none";
                    remainingInput.value = "";
                }
            } else {
                remainingContainer.style.display = "none";
                remainingInput.value = "";
            }
        }
    }

    // ========================================
    // 13. EVENT LISTENERS FOR TOTALS
    // ========================================
    if (discountTypeSel) discountTypeSel.addEventListener('change', calculateTotals);
    if (discountInput) discountInput.addEventListener('input', calculateTotals);
    if (taxInput) taxInput.addEventListener('input', calculateTotals);
    if (receivedInput) receivedInput.addEventListener('input', calculateTotals);
    if (taxTypeSel) taxTypeSel.addEventListener('change', calculateTotals);
    // ========================================
// 🎯 SALE-LEVEL DISCOUNT VALIDATION
// ========================================
if (discountInput) {
    discountInput.addEventListener('input', function() {
        const discountType = discountTypeSel?.value || 'amount';
        const subtotal = parseFloat(subtotalInput?.value) || 0;
        let currentValue = parseFloat(this.value);
        
        if (discountType === 'percentage') {
            // Limit percentage to 100
            if (currentValue > 100) {
                this.value = 100;
                calculateTotals();
            }
        } else {
            // Limit amount to subtotal
            if (currentValue > subtotal) {
                this.value = subtotal.toFixed(2);
                calculateTotals();
            }
        }
    });
}

// Also validate when discount type changes
if (discountTypeSel) {
    discountTypeSel.addEventListener('change', function() {
        const discountType = this.value;
        const subtotal = parseFloat(subtotalInput?.value) || 0;
        let currentValue = parseFloat(discountInput?.value) || 0;
        
        if (discountType === 'percentage' && currentValue > 100) {
            discountInput.value = 100;
        } else if (discountType === 'amount' && currentValue > subtotal) {
            discountInput.value = subtotal.toFixed(2);
        }
        calculateTotals();
    });
}
// Tax validation
if (taxInput) {
    taxInput.addEventListener('input', function() {
        const taxType = taxTypeSel?.value || 'amount';
        const subtotal = parseFloat(subtotalInput?.value) || 0;
        let currentValue = parseFloat(this.value);
        
        if (taxType === 'percentage') {
            if (currentValue > 100) {
                this.value = 100;
                calculateTotals();
            }
        } else {
            if (currentValue > subtotal) {
                this.value = subtotal.toFixed(2);
                calculateTotals();
            }
        }
    });
}

if (taxTypeSel) {
    taxTypeSel.addEventListener('change', function() {
        const taxType = this.value;
        const subtotal = parseFloat(subtotalInput?.value) || 0;
        let currentValue = parseFloat(taxInput?.value) || 0;
        
        if (taxType === 'percentage' && currentValue > 100) {
            taxInput.value = 100;
        } else if (taxType === 'amount' && currentValue > subtotal) {
            taxInput.value = subtotal.toFixed(2);
        }
        calculateTotals();
    });
}
    // ========================================
    // 14. PAYMENT TYPE CHANGE
    // ========================================
    const paymentTypeSelect = document.getElementById('payment_type');
    if (paymentTypeSelect) {
        paymentTypeSelect.addEventListener('change', function() {
            const statusField = document.getElementById('status');
            const receivedField = document.getElementById('received_amount');
            const changeField = document.getElementById('change_amount');

            if (this.value === 'credit') {
                if (statusField) statusField.value = 'pending';
                if (receivedField) {
                    receivedField.value = '';
                    receivedField.disabled = true;
                }
                if (changeField) changeField.value = '';
            } else {
                if (statusField) statusField.value = 'paid';
                if (receivedField) receivedField.disabled = false;
            }
        });
    }

    // ========================================
// 15. STOCK CHECK ON QTY CHANGE
// ========================================
if (itemsTableBody) {
    itemsTableBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('qty') && typeof Swal !== 'undefined') {
            const qtyInput = e.target;
            const row = qtyInput.closest('tr');
            const purchaseId = row.querySelector('input[name*="[purchase_id]"]')?.value;
            const productName = row.querySelector('strong')?.textContent.trim();
            const qty = parseInt(qtyInput.value) || 0;

            if (qty > 0 && purchaseId) {
                fetch(`/check-stock/${purchaseId}?quantity=${qty}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'insufficient') {
                            const oversold = qty - data.available;

                            Swal.fire({
                                icon: 'warning',
                                title: '<span style="font-size:14px;">Stock Warning</span>',
                                html: `
                                    <p style="font-size:13px; margin:0;">
                                        Only <strong>${data.available}</strong> units of 
                                        <strong style="color:#d33;">${productName}</strong> are available.<br>
                                        Remaining (oversold): <strong style="color:#d33;">-${oversold}</strong>
                                    </p>`,
                                confirmButtonText: 'OK',
                                width: '330px',
                                padding: '0.8em',
                                customClass: {
                                    confirmButton: 'swal-custom-btn',
                                    closeButton: 'swal-close-btn'
                                },
                                showCloseButton: true,
                                position: 'top-start',
                                backdrop: false
                            });

                            recalcRow(row);
                            calculateTotals();
                        } else {
                            // ✅ NEW: Auto-close the warning if quantity is now valid
                            if (Swal.isVisible()) {
                                Swal.close();
                            }
                            recalcRow(row);
                            calculateTotals();
                        }
                    })
                    .catch(error => console.error('Stock check error:', error));
            } else {
                // ✅ NEW: Also close if quantity is 0 or empty
                if (Swal.isVisible()) {
                    Swal.close();
                }
            }
        }
    });
}

    // ========================================
// COMPLETE FORM VALIDATION FIX
// Place this in your blade file, replacing section 16
// ========================================

const receiptForm = document.getElementById('receiptForm');
if (receiptForm) {
    receiptForm.addEventListener('submit', function(e) {
        const customerTypeVal = customerType?.value;
        const paymentTypeVal = paymentType?.value;
        const contactField = document.getElementById('contact');
        const customerNameField = document.getElementById('customer_name');
        
        const grandTotalVal = parseFloat(grandTotalInput?.value) || 0;
        const receivedVal = parseFloat(receivedInput?.value) || 0;
        const currentBalanceVal = parseFloat(hiddenCurrentBalance?.value) || 0;
        const totalPayable = grandTotalVal + currentBalanceVal;
        
        // Check if there are any products in the table
        const hasProducts = itemsTableBody && itemsTableBody.querySelectorAll('tr').length > 0;

        // ========================================
        // 🎯 SCENARIO 1: Payment-only transaction
        // ========================================
        if (grandTotalVal === 0 && currentBalanceVal > 0 && receivedVal > 0) {
            // This is ONLY paying old balance, not buying anything new
            
            // Validate customer information exists
            if (!contactField?.value.trim()) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Required',
                        text: 'Please select a customer to apply payment.',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'swal-custom-btn',
                            closeButton: 'swal-close-btn'
                        }
                    }).then(() => {
                        highlightField(contactField);
                    });
                }
                return false;
            }
            
            // ✅ Valid payment-only transaction - allow submission
            console.log('Payment-only transaction detected - allowing submission');
            return true;
        }

        // ========================================
        // 🎯 SCENARIO 2: No transaction at all
        // ========================================
        if (grandTotalVal === 0 && receivedVal === 0) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Transaction',
                    text: 'Please add products or enter a payment amount.',
                    confirmButtonText: 'OK'
                });
            }
            return false;
        }

        // ========================================
        // 🎯 SCENARIO 3: Credit sale validation
        // ========================================
        if (customerTypeVal === 'credit' && paymentTypeVal === 'credit' && hasProducts) {
            if (!customerNameField?.value.trim()) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Name Required',
                        text: 'Please enter customer name for credit sales.',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'swal-custom-btn',
                            closeButton: 'swal-close-btn'
                        }
                    }).then(() => {
                        highlightField(customerNameField);
                    });
                }
                return false;
            }

            if (!contactField?.value.trim()) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Contact Number Required',
                        text: 'Please enter customer contact number for credit sales.',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'swal-custom-btn',
                            closeButton: 'swal-close-btn'
                        }
                    }).then(() => {
                        highlightField(contactField);
                    });
                }
                return false;
            }
            
            // For credit sales, allow zero or partial payment
            return true;
        }

        // ========================================
        // 🎯 SCENARIO 4: Cash/Card sale validation
        // (Only trigger if there ARE products)
        // ========================================
        if ((paymentTypeVal === 'cash' || paymentTypeVal === 'card') && hasProducts) {
            
            // Case 4A: Partial payment on NEW sale (no old balance)
            if (currentBalanceVal === 0 && receivedVal > 0 && receivedVal < grandTotalVal) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Partial Payment Detected',
                        html: `
                            <p style="font-size:14px;">
                                You have entered a partial payment of 
                                <strong>Rs ${receivedVal.toFixed(2)}</strong> out of 
                                <strong>Rs ${grandTotalVal.toFixed(2)}</strong>.<br><br>
                                Please set <strong>Customer Type = Credit</strong> 
                                if this is a credit sale.
                            </p>
                        `,
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'swal-custom-btn',
                            closeButton: 'swal-close-btn'
                        }
                    });
                }
                return false;
            }

            // Case 4B: Insufficient payment for TOTAL (new sale + old balance)
            if (currentBalanceVal > 0 && receivedVal > 0 && receivedVal < totalPayable) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Payment',
                        html: `
                            <p style="font-size:14px;">
                                <strong>Current Sale:</strong> Rs ${grandTotalVal.toFixed(2)}<br>
                                <strong>Previous Balance:</strong> Rs ${currentBalanceVal.toFixed(2)}<br>
                                <strong>Total Payable:</strong> Rs ${totalPayable.toFixed(2)}<br><br>
                                <strong>Received:</strong> Rs ${receivedVal.toFixed(2)}<br>
                                <strong>Shortage:</strong> <span style="color:#dc3545;">Rs ${(totalPayable - receivedVal).toFixed(2)}</span><br><br>
                                For cash/card sales with previous balance, you must either:<br>
                                • Collect full amount (Rs ${totalPayable.toFixed(2)}), OR<br>
                                • Set <strong>Customer Type = Credit</strong>
                            </p>
                        `,
                        confirmButtonText: 'OK',
                        width: '450px',
                        customClass: {
                            confirmButton: 'swal-custom-btn',
                            closeButton: 'swal-close-btn'
                        }
                    });
                }
                return false;
            }
        }

        // ✅ All validations passed
        console.log('Form validation passed - allowing submission');
        return true;
    });
}

    // ========================================
    // 17. HIDE AUTOCOMPLETE WHEN CLICKING OUTSIDE
    // ========================================
    document.addEventListener("click", function (e) {
        if (customerList && customerInput) {
            if (!customerList.contains(e.target) && e.target !== customerInput) {
                customerList.style.display = "none";
            }
        }
    });

    // ========================================
    // 18. INITIALIZE
    // ========================================
    console.log('Receipt form JavaScript initialized successfully');
});

// ========================================
// 19. GLOBAL FUNCTIONS (Outside DOMContentLoaded)
// ========================================

/**
 * Show product history modal
 * @param {number} purchaseId - Product purchase ID
 * @param {string} productName - Product name
 */
function showProductHistory(purchaseId, productName) {
      // ✅ Hide autocomplete list immediately
      const autocompleteList = document.getElementById('autocompleteList');
    if (autocompleteList) {
        autocompleteList.style.display = 'none';
    }
    // Check if Swal is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded');
        alert('Unable to load product history. Please refresh the page.');
        return;
    }

    Swal.fire({
        title: 'Loading History...',
        text: `Getting sales history for ${productName}`,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`/pos/product-history/${purchaseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showHistoryModal(data.summary, data.history);
            } else {
                Swal.fire('Error', 'Could not load product history', 'error');
            }
        })
        .catch(error => {
            console.error('History error:', error);
            Swal.fire('Error', 'Failed to load product history', 'error');
        });
}

/**
 * Display history modal with product details
 * @param {object} summary - Product summary data
 * @param {array} history - Sales history array
 */
function showHistoryModal(summary, history) {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded');
        return;
    }

    let historyRows = '';
    
    if (history && history.length > 0) {
        historyRows = history.map(sale => {
            const saleDate = new Date(sale.sale_date).toLocaleDateString();
            const returnedInfo = sale.returned_qty > 0 ? ` <span style="color: #dc3545;">(-${sale.returned_qty})</span>` : '';
            const discountInfo = sale.discount_amount > 0 ? `<br><small style="color: #28a745;">Discount: ${sale.discount_type === 'percentage' ? sale.discount_value + '%' : 'Rs ' + sale.discount_value}</small>` : '';
            return `
                <tr style="font-size: 12px;">
                    <td>${saleDate}</td>
                    <td>${sale.voucher_no}</td>
                    <td>${sale.customer_name}<small style="color: #6c757d;"> (${sale.customer_type})</small></td>
                    <td>${sale.quantity}${returnedInfo}</td>
                    <td>Rs ${parseFloat(sale.price).toFixed(2)}${discountInfo}</td>
<td>Rs ${parseFloat(sale.final_total || sale.total_after_discount).toFixed(2)}</td>         
           <td><span class="badge badge-${sale.payment_type === 'cash' ? 'success' : sale.payment_type === 'credit' ? 'warning' : 'primary'}">${sale.payment_type}</span></td>
                </tr>
            `;
        }).join('');
    } else {
        historyRows = '<tr><td colspan="7" style="text-align: center; color: #6c757d; padding: 20px;">No sales history found</td></tr>';
    }

    const modalContent = `
        <div class="history-modal-content">
            <div class="product-summary" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; font-size: 13px;">
                    <div><strong>Product:</strong> ${summary.product_name}</div>
                    <div><strong>Unit:</strong> ${summary.unit}</div>
                    <div><strong>Category:</strong> ${summary.category}</div>
                    <div><strong>Supplier:</strong> ${summary.supplier}</div>
                    <div><strong>Purchase Price:</strong> Rs ${parseFloat(summary.purchase_price).toFixed(2)}</div>
                    <div><strong>Selling Price:</strong> Rs ${parseFloat(summary.selling_price).toFixed(2)}</div>
                    <div><strong>Total Purchased:</strong> ${summary.total_purchased}</div>
                    <div><strong>Total Sold:</strong> ${summary.total_sold}</div>
                    <div><strong>Available Stock:</strong> <span style="color: #28a745; font-weight: bold;">${summary.available_stock}</span></div>
                    <div><strong>Total Transactions:</strong> ${summary.total_transactions}</div>
                    <div><strong>Total Revenue:</strong> <span style="color: #007bff; font-weight: bold;">Rs ${parseFloat(summary.total_revenue).toFixed(2)}</span></div>
<div><strong>${summary.total_profit < 0 ? 'Total Loss:' : 'Total Profit:'}</strong>
<span style="color: ${summary.total_profit < 0 ? '#dc3545' : '#28a745'}; font-weight: bold;">
Rs ${Math.abs(summary.total_profit).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
</span></div>
                </div>
            </div>

            <div class="history-table-container" style="max-height: 300px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead style="background: #e9ecef; position: sticky; top: 0;">
                        <tr>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Date</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Voucher</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Customer</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Qty</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Price</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Total</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6;">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${historyRows}
                    </tbody>
                </table>
            </div>
        </div>
    `;

    Swal.fire({
        title: `Sales History - ${summary.product_name}`,
        html: modalContent,
        width: '900px',
        height: '900px',
        showConfirmButton: true,
        confirmButtonText: 'Close',
        customClass: {
            confirmButton: 'swal-custom-btn'
        }
    });
}
</script>
@endsection