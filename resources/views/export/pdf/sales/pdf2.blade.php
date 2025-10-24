<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 40px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1b2a41;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #1b2a41;
            font-size: 22px;
        }

        .header p {
            margin: 4px 0;
            color: #555;
            font-size: 13px;
        }

        .invoice-header {
            margin-bottom: 8px;
            font-size: 12px;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            text-align: left;
            font-size: 11px;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th {
            background-color: #1b2a41;
            color: white;
            padding: 6px;
            border: 1px solid #ddd;
            word-break: break-word;
        }

        td {
            border: 1px solid #ddd;
            padding: 6px;
            word-break: break-word;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #666;
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 10.5px;
        }

        .nested-table th {
            background-color: #1b2a41;
            color: #fff;
            padding: 5px;
        }

        .nested-table td {
            border: 1px solid #ccc;
            padding: 5px;
        }

        .separator {
            border-top: 2px solid #1b2a41;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>{{ $subTitle }}</p>
        <p>Generated on: {{ $generatedOn }}</p>
    </div>

    @foreach($generalSales as $index => $sale)
        <div class="invoice-header">
        <strong>No: </strong> {{ $index+1 }} &nbsp;&nbsp;    
        <strong>Invoice:</strong> {{ $sale->voucher_no ?? 'N/A' }} &nbsp;&nbsp;
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d-M-Y h:i A') }}<br>
            <strong>Customer:</strong> {{ $sale->customer_name ?? 'Walk-in Customer' }} &nbsp;&nbsp;
            <strong>Payment:</strong> {{ ucfirst($sale->payment_type) }}<br>
            <strong>Seller:</strong> {{ Auth::user()->name ?? 'Admin' }} &nbsp;&nbsp;
            <strong>Total:</strong> Rs {{ number_format($sale->adjusted_grand_total, 2) }}
        </div>

        <table class="nested-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (per unit)</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Discount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleItems[$sale->sale_id] ?? [] as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>Rs {{ number_format($item->price, 2) }}</td>
                        <td>
                            {{ $item->remaining_quantity }}
                            @if($item->returned_quantity > 0)
                                <small style="color: #888;">({{ $item->returned_quantity }} returned)</small>
                            @endif
                        </td>
                        <td>Rs {{ number_format($item->total_after_discount, 2) }}</td>
                        <td>
                            @if($item->adjusted_discount_amount > 0)
                                @if($item->discount_type === 'percentage')
                                    {{ number_format($item->discount_value, 0) }}% 
                                    (Rs {{ number_format($item->adjusted_discount_amount, 2) }})
                                @else
                                    Rs {{ number_format($item->adjusted_discount_amount, 2) }}
                                @endif
                                @if($item->returned_quantity > 0)
                                    <small style="color: #666; display: block; font-style: italic;">
                                        (Adjusted for {{ $item->returned_quantity }} returned items)
                                    </small>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="separator"></div>
    @endforeach

    <div class="footer">
        <p>Generated by POS System | © {{ date('Y') }}</p>
    </div>
    @if(isset($isPrint) && $isPrint)
<script>
    window.onload = function() {
        window.print();
    };
</script>
@endif
</body>
</html>
