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

.invoice-header > div {
    /* display: flex; */
    /* flex-direction: column; make each child’s content vertical */
    /* width: 32%; ensures three fit side by side */
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

    @foreach($creditSales as $index => $sale)
    <table width="100%" style="border-collapse: collapse; margin-bottom: 10px;">
    <tr>
        <!-- Column 1 -->
        <td style="vertical-align: top; width: 33%;">
            <span><strong>No:</strong> {{ $index+1 }}</span><br>
            <span><strong>Invoice:</strong> {{ $sale->voucher_no ?? 'N/A' }}</span><br>
            <span><strong>Customer:</strong> {{ $sale->customer->name ?? 'Walk-in Customer' }}</span><br>
            <span><strong>Shop Name:</strong> {{ $sale->customer->shop_name ?? 'N/A' }}</span><br>
            <span><strong>Contact:</strong> {{ $sale->customer->contact ?? 'N/A' }}</span>
        </td>

        <!-- Column 2 -->
        <td style="vertical-align: top; width: 33%;">
            <span><strong>Total:</strong> Rs {{ number_format($sale->grand_total, 2) }}</span><br>
            <span><strong>Status:</strong> {{ ucfirst($sale->status) }}</span><br>
            <span><strong>Paid Amount:</strong> Rs {{ number_format($sale->grand_total - $sale->remaining_balance, 2) }}</span><br>
            <span><strong>Remaining Balance:</strong> Rs {{ number_format($sale->remaining_balance, 2) }}</span>
        </td>

        <!-- Column 3 -->
        <td style="vertical-align: top; width: 33%;">
            <span><strong>Sale Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d-M-Y h:i A') }}</span><br>
            <span><strong>Due Date:</strong> {{ $sale->due_date ?? 'N/A' }}</span><br>
            <span><strong>Seller:</strong> {{ Auth::user()->name ?? 'Admin' }}</span>
        </td>
    </tr>
</table>


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
            @foreach($saleItems[$sale->id] ?? [] as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ number_format($item->price, 2) }}</td>
                                        <td>
                                            {{ $item->remaining_quantity }}
                                            @if($item->returned_quantity > 0)
                                                <small style="color: #888;">({{ $item->returned_quantity }} returned)</small>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->total_after_discount, 2) }}</td>
                                        <td>
                                            @if($item->adjusted_discount_amount > 0)
                                                @if($item->discount_type === 'percentage')
                                                    {{ number_format($item->discount_value, 0) }}% (Rs {{ number_format($item->adjusted_discount_amount, 2) }})
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
