@extends('layouts.app')

@section('title', 'Customer Purchases')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/search.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<style>
#customerSearchType, #customerTypeFilter {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}

#dateFrom,#dateTo{
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #fff;
    outline: none;
    font-size: 14px;
}
#customerSearchType:focus {
    border-color: #007bff;
}
#customerSearchbar-dropdown{
    display: flex;
    justify-content: center;
    gap:5px;
}
.customerPurchasesSearch-Container{
    display:flex;
    justify-content:space-between;
}
.invoice-section {
    margin-bottom: 30px;
    border: 1px solid #ddd;
    background: white;
}

.invoice-header {
    background: #f5f5f5;
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.invoice-title {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.invoice-date {
    font-size: 12px;
    color: #666;
    margin: 0;
}

.invoice-badges {
    display: flex;
    gap: 8px;
}

.badge {
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-pending {
    background: #dc3545;
    color: white;
}

.badge-paid {
    background: #28a745;
    color: white;
}

.badge-credit {
    background: #6c757d;
    color: white;
}

.badge-cash {
    background: #007bff;
    color: white;
}

.invoice-summary {
    background: #f9f9f9;
    padding: 12px 15px;
    border-top: 1px solid #ddd;
    font-size: 13px;
}

.summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
}

.summary-item {
    display: flex;
    justify-content: flex-start;
    gap:10px;
}

.badge-partial {
    background: #ffc107;
    color: #333;
}

.summary-item strong {
    color: #333;
}

.balance-due {
    color: #dc3545;
    font-weight: 600;
}

.return-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 10px;
}

.strikethrough {
    text-decoration: line-through;
    color: #888;
}
</style>
@endpush

@section('content')
<div class="container">
    <div class="container-child main-text">
        <h1>Customer Purchase History</h1>
    </div>
    <div class="container-child sub-text">
        <p>All Purchases Made By <strong>{{ $customer->name ?? 'Walk-in Customer' }}</strong></p>
        <p>Contact: {{ $customer->contact ?? 'N/A' }} | City: {{ $customer->city ?? 'N/A' }} | Shop Name: {{$customer->shop_name ?? 'N/A'}}</p>
    </div>

    <div class="sub-container">
        <div class="customerPurchasesSearch-Container">
    <div class="customerTypeDropdown">
    <select id="customerTypeFilter" class="input" style="width: 200px;">
        <option value="all">All Sales</option>
        <option value="credit">Credit Sales</option>
        <option value="cash">Cash Sales</option>
        <option value="card">Card Sales</option>
    </select>
</div>
    <div id="customerSearchbar-dropdown">
  <div style="display:flex;justify-content:center;gap:5px">
      <p style="position:relative;top:9px;">Search by:</p>
      <select id="customerSearchType" class="input" style="width: 140px;">
          <option value="invoice_no">Invoice No</option>
          <option value="date">Date</option>
      </select>
  </div>
<div class="search-container" id="search-container">
    <input type="text" name="text" id="customerSearchInput" class="input" placeholder="Search...">
    <span class="icon1"> 
      <svg width="19px" height="19px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path opacity="1" d="M14 5H20" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M14 8H17" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M21 11.5C21 16.75 16.75 21 11.5 21C6.25 21 2 16.75 2 11.5C2 6.25 6.25 2 11.5 2" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> <path opacity="1" d="M22 22L20 20" stroke="#000" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
    </span>
  </div>
</div>
</div>
        @if(count($pagination->items()) > 0)
            @foreach ($pagination->items() as $saleId => $saleData)
                @php
                    $sale = $saleData['sale_info'];
                    $items = $saleData['items'];
                    
                    $paymentStatus = 'pending';
                    if ($sale['remaining_balance'] <= 0) {
                        $paymentStatus = 'paid';
                    } elseif ($sale['total_paid'] > 0) {
                        $paymentStatus = 'partial';
                    }
                @endphp
                
                <div class="invoice-section">
                    <div class="invoice-header">
                        <div>
                            <h4 class="invoice-title">Invoice #{{ $sale['voucher_no'] }}</h4>
                            <p class="invoice-date">{{ \Carbon\Carbon::parse($sale['sale_date'])->timezone('Asia/Karachi')->format('d M Y, g:i A') }}</p>
                            @if($sale['has_returns'])
                                <div class="return-warning">
                                    ⚠️ This sale has returns - showing adjusted amounts
                                </div>
                            @endif
                        </div>
                        <div class="invoice-badges">
                            <span class="badge badge-{{ $paymentStatus }}">{{ strtoupper($paymentStatus) }}</span>
                            <span class="badge badge-{{ strtolower($sale['payment_type']) }}">{{ ucfirst($sale['payment_type']) }}</span>
                        </div>
                    </div>

                    @if(count($items) > 0)
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Rate</th>
                                <th>Discount</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr @if($item['remaining_quantity'] <= 0) style="background-color: #ffe6e6;" @endif>
                                <td>
                                    {{ $item['product_name'] }}
                                    @if($item['remaining_quantity'] <= 0)
                                        <span style="color: #e74c3c; font-size: 11px;">[RETURNED]</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item['returned_quantity'] > 0)
                                        @if($item['remaining_quantity'] > 0)
                                            {{ $item['remaining_quantity'] }}
                                            <small style="color: #888;">({{ $item['returned_quantity'] }} returned)</small>
                                        @else
                                            <span style="color: #e74c3c;">All Returned ({{ $item['original_quantity'] }})</span>
                                        @endif
                                    @else
                                        {{ $item['remaining_quantity'] }}
                                    @endif
                                </td>
                                <td>{{ $item['unit'] }}</td>
                                <td>Rs {{ number_format($item['price'], 2) }}</td>
                                <td>
                                    @if($item['remaining_quantity'] > 0 && $item['adjusted_discount_amount'] > 0)
                                        @if($item['item_discount_type'] === 'percentage')
                                            {{-- ✅ For percentage: show original % but adjusted amount --}}
                                            {{ number_format($item['item_discount_value'], 0) }}% (Rs {{ number_format($item['adjusted_discount_amount'], 2) }})
                                        @else
                                            {{-- ✅ For amount: show adjusted amount only --}}
                                            Rs {{ number_format($item['adjusted_discount_amount'], 2) }}
                                        @endif
                                        @if($item['returned_quantity'] > 0)
                                            <small style="color: #666; display: block; font-style: italic;">
                                                (Adjusted for {{ $item['returned_quantity'] }} returned items)
                                            </small>
                                        @endif
                                    @elseif($item['remaining_quantity'] <= 0)
                                        <span style="color: #e74c3c;">-</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item['remaining_quantity'] > 0)
                                        Rs {{ number_format($item['adjusted_total_after_discount'], 2) }}
                                    @else
                                        <span style="color: #e74c3c;">Rs 0.00</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                    <div class="invoice-summary">
                        <div class="summary-grid">
                            @if($sale['has_returns'])
                                {{-- Show both original and adjusted values for returns --}}
                                <div class="summary-item">
                                    <span>Original Subtotal:</span>
                                    <strong class="strikethrough">Rs {{ number_format($sale['subtotal'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Adjusted Subtotal:</span>
                                    <strong>Rs {{ number_format($sale['adjusted_subtotal'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Original Discount:</span>
                                    <strong class="strikethrough">Rs {{ number_format($sale['discount_amount'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Adjusted Discount:</span>
                                    <strong>Rs {{ number_format($sale['adjusted_discount_amount'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
    <span>Original Tax:</span>
    <strong class="strikethrough">
        @if($sale['tax_type'] === 'percentage')
            {{ number_format($sale['tax'], 0) }}% (Rs {{ number_format($sale['tax_amount'], 2) }})
        @else
            Rs {{ number_format($sale['tax_amount'], 2) }}
        @endif
    </strong>
</div>
<div class="summary-item">
    <span>Adjusted Tax:</span>
    <strong>
        @if($sale['tax_type'] === 'percentage')
        {{ number_format(($sale['adjusted_subtotal'] > 0 ? ($sale['adjusted_tax'] / $sale['adjusted_subtotal']) * 100 : 0), 1) }}% 
        (Rs {{ number_format($sale['adjusted_tax'], 2) }})
        @else
            Rs {{ number_format($sale['adjusted_tax'], 2) }}
        @endif
    </strong>
</div>

                                <div class="summary-item" style="border-top: 2px solid #e74c3c; padding-top: 10px;">
                                    <span>Total Returned Amount:</span>
                                    <strong style="color: #e74c3c;">-Rs {{ number_format($sale['total_returned_amount'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Original Grand Total:</span>
                                    <strong class="strikethrough">Rs {{ number_format($sale['grand_total'], 2) }}</strong>
                                </div>
                                <div class="summary-item" style="border-top: 1px solid #ccc; padding-top: 10px;">
                                    <span><strong>Current Grand Total:</strong></span>
                                    <strong>Rs {{ number_format($sale['adjusted_grand_total'], 2) }}</strong>
                                </div>
                            @else
                                {{-- No returns - show original values --}}
                                <div class="summary-item">
                                    <span>Subtotal:</span>
                                    <strong>Rs {{ number_format($sale['subtotal'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Discount:</span>
                                    <strong>Rs {{ number_format($sale['discount_amount'], 2) }}</strong>
                                </div>
                                <div class="summary-item">
    <span>Tax:</span>
    <strong>
        @if($sale['tax_type'] === 'percentage')
            {{ number_format($sale['tax'], 0) }}% (Rs {{ number_format($sale['tax_amount'], 2) }})
        @else
            Rs {{ number_format($sale['tax_amount'], 2) }}
        @endif
    </strong>
</div>

                                <div class="summary-item">
                                    <span>Grand Total:</span>
                                    <strong>Rs {{ number_format($sale['grand_total'], 2) }}</strong>
                                </div>
                            @endif
                            
                            <div class="summary-item">
                                <span>Amount Paid:</span>
                                <strong>Rs {{ number_format($sale['total_paid'] ?? $sale['received_amount'], 2) }}</strong>
                            </div>
                            @if($sale['remaining_balance'] > 0)
                            <div class="summary-item">
                                <span>Outstanding Balance:</span>
                                <strong class="balance-due">Rs {{ number_format($sale['remaining_balance'], 2) }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div style="text-align: center; padding: 40px;">
                <h3>No Purchase History</h3>
                <p>This customer hasn't made any purchases yet.</p>
            </div>
        @endif

        {{-- Pagination --}}
        @if($pagination->hasPages())
        <div class="custom-pagination">
            @if ($pagination->onFirstPage())
                <span class="disabled">« First</span>
            @else
                <a href="{{ $pagination->url(1) }}">« First</a>
            @endif

            @if ($pagination->onFirstPage())
                <span class="disabled">←</span>
            @else
                <a href="{{ $pagination->previousPageUrl() }}" rel="prev">←</a>
            @endif

            @php
                $start = max($pagination->currentPage() - 2, 1);
                $end = min($pagination->currentPage() + 2, $pagination->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $pagination->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $pagination->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $pagination->lastPage())
                <span class="dots">...</span>
            @endif

            @if ($pagination->hasMorePages())
                <a href="{{ $pagination->nextPageUrl() }}" rel="next">→</a>
            @else
                <span class="disabled">→</span>
            @endif

            @if ($pagination->hasMorePages())
                <a href="{{ $pagination->url($pagination->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">Last »</span>
            @endif
        </div>
        @endif
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const salesFilter = document.getElementById("customerTypeFilter");
    const searchType = document.getElementById("customerSearchType");
    const searchInput = document.getElementById("customerSearchInput");
    const searchInputContainer = document.getElementById("search-container");
    const dropdownContainer = document.getElementById("customerSearchbar-dropdown");

    // Create date range inputs dynamically
    const dateInputsWrapper = document.createElement("div");
    dateInputsWrapper.id = "dateRangeInputs";
    dateInputsWrapper.style.display = "none";
    dateInputsWrapper.style.gap = "8px";
    dateInputsWrapper.style.alignItems = "center";
    dateInputsWrapper.innerHTML = `
        <input type="date" id="dateFrom" class="input" style="padding:6px 10px; width: 130px;">
        <span style="font-weight:500;">to</span>
        <input type="date" id="dateTo" class="input" style="padding:6px 10px; width: 130px;">
    `;
    dropdownContainer.appendChild(dateInputsWrapper);

    const dateFrom = document.getElementById("dateFrom");
    const dateTo = document.getElementById("dateTo");
    const invoices = document.querySelectorAll(".invoice-section");

    // 🔹 Fuzzy match helper
    function fuzzyMatch(text, token) {
        let tIndex = 0;
        for (let i = 0; i < text.length && tIndex < token.length; i++) {
            if (text[i] === token[tIndex]) tIndex++;
        }
        return tIndex === token.length;
    }

    // 🔹 Toggle between invoice search and date search
    searchType.addEventListener("change", function () {
        if (this.value === "date") {
            searchInput.style.display = "none";
            searchInputContainer.style.display = "none";
            dateInputsWrapper.style.display = "flex";
            searchInput.value = "";
        } else {
            searchInputContainer.style.display = "block";
            searchInput.style.display = "block";
            dateInputsWrapper.style.display = "none";
            dateFrom.value = "";
            dateTo.value = "";
        }
        filterInvoices();
    });

    // 🔹 Main filtering function
    function filterInvoices() {
        const typeFilter = salesFilter.value;
        const searchMode = searchType.value;
        const searchValue = searchInput.value.toLowerCase().trim();
        const fromDateVal = dateFrom.value ? new Date(dateFrom.value) : null;
        const toDateVal = dateTo.value ? new Date(dateTo.value) : null;

        invoices.forEach(inv => {
            const invoiceText = inv.querySelector(".invoice-title")?.innerText.toLowerCase() || "";
            const dateText = inv.querySelector(".invoice-date")?.innerText || "";
            const badges = inv.querySelectorAll(".badge");
            let paymentType = "";

            badges.forEach(b => {
                if (b.classList.contains("badge-credit")) paymentType = "credit";
                else if (b.classList.contains("badge-cash")) paymentType = "cash";
                else if (b.classList.contains("badge-card")) paymentType = "card";
            });

            // Filter by type (All / Credit / Cash / Card)
            const matchType = (typeFilter === "all") || (paymentType === typeFilter);
            let matchSearch = true;

            if (searchMode === "invoice_no") {
                if (searchValue) {
                    matchSearch = invoiceText.includes(searchValue) || fuzzyMatch(invoiceText, searchValue);
                }
            } else if (searchMode === "date") {
                if (fromDateVal || toDateVal) {
                    const dateObj = new Date(dateText);
                    if (isNaN(dateObj)) {
                        matchSearch = false;
                    } else if (fromDateVal && toDateVal) {
                        matchSearch = (dateObj >= fromDateVal && dateObj <= toDateVal);
                    } else if (fromDateVal && !toDateVal) {
                        matchSearch = (
                            dateObj.getFullYear() === fromDateVal.getFullYear() &&
                            dateObj.getMonth() === fromDateVal.getMonth() &&
                            dateObj.getDate() === fromDateVal.getDate()
                        );
                    }
                }
            }

            inv.style.display = (matchType && matchSearch) ? "" : "none";
        });
    }

    // Event listeners
    salesFilter.addEventListener("change", filterInvoices);
    searchInput.addEventListener("keyup", filterInvoices);
    dateFrom.addEventListener("change", filterInvoices);
    dateTo.addEventListener("change", filterInvoices);
});
</script>
@endsection