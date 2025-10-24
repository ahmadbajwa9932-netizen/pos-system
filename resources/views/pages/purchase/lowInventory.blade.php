@extends('layouts.app')

@section('title', 'Low Inventory Products')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/pagination.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
<style>
    .danger-row {
    background-color: #ffe6e6 !important;
    color:rgb(107, 92, 92) !important;
    
}
    .restock-btn {
        background: #007bff;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
    }
    .restock-btn:hover {
        background: #0056b3;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        text-align: center;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        & form{
            margin-top:10px;
           & .modal-field{
            display:flex;
            flex-direction:row;
            justify-content:flex-end;
            align-items:center;
            }
        } 
    }


    .modal-content input {
        width: 80%;
        padding: 8px;
        /* margin-top: 10px; */
    }
    .close-btn {
        background: #ccc;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        margin-top: 10px;
        cursor: pointer;
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
    <div class="container-child main-text">
        <h1>Low Inventory Products</h1>
    </div>

    <div class="container-child sub-text">
        <p>Low inventory alert: products with 5 or fewer units</p>
    </div>

    <div class="sub-container">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Remaining</th>
                    <th>Purchase Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $index => $purchase)
                <tr class="{{ $purchase->remaining == 0 ? 'danger-row' : '' }}">
                <td>{{ $index + 1 }}</td>
                        <td>{{ $purchase->product_name }}</td>
                        <td>{{ $purchase->category->name ?? 'N/A' }}</td>
                        <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                        <td style="color: red; font-weight: bold;">{{ number_format($purchase->remaining,0) }}</td>
                        <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td>
                        <td>
                            <button class="restock-btn" onclick="openRestockModal({{ $purchase->id }}, '{{ $purchase->product_name }}', '{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') }}')">
                                Restock
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align:center;">No low inventory products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="custom-pagination">
            {{-- First & Previous --}}
            @if ($purchases->onFirstPage())
                <span class="disabled">« First</span>
                <span class="disabled">←</span>
            @else
                <a href="{{ $purchases->url(1) }}">« First</a>
                <a href="{{ $purchases->previousPageUrl() }}">←</a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($purchases->currentPage() - 2, 1);
                $end = min($purchases->currentPage() + 2, $purchases->lastPage());
            @endphp

            @if ($start > 1)
                <span class="dots">...</span>
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $purchases->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $purchases->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($end < $purchases->lastPage())
                <span class="dots">...</span>
            @endif

            {{-- Next & Last --}}
            @if ($purchases->hasMorePages())
                <a href="{{ $purchases->nextPageUrl() }}">→</a>
                <a href="{{ $purchases->url($purchases->lastPage()) }}">Last »</a>
            @else
                <span class="disabled">→</span>
                <span class="disabled">Last »</span>
            @endif
        </div>
    </div>
</div>

<!-- Restock Modal -->
<div class="modal" id="restockModal">
    <div class="modal-content">
        <h3 id="restockProductName"></h3>
        <form id="restockForm" method="POST" action="{{ route('purchase.restock') }}">
            @csrf
            <input type="hidden" name="purchase_id" id="purchaseId">
            <div class="modal-field">
            <label>Quantity:</label>
            <input type="number" name="quantity" placeholder="Enter quantity to add" required>
            </div>
            <br><br>
            <div class="modal-field">
            <label>Purchase Date:</label>
            <input type="date" name="purchase_date" id="purchaseDate" required>
</div>
            <br>
            <button type="submit" class="restock-btn">Update Stock</button>
            <button type="button" class="close-btn" onclick="closeRestockModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openRestockModal(id, name,date) {
    document.getElementById('purchaseId').value = id;
    document.getElementById('restockProductName').innerText = 'Restock ' + name;
    document.getElementById('purchaseDate').value = date;   
    document.getElementById('restockModal').style.display = 'flex';
}

function closeRestockModal() {
    document.getElementById('restockModal').style.display = 'none';
}
</script>
@endsection
