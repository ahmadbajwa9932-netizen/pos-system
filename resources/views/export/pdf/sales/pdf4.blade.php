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

    @foreach($returnedSales as $index => $sale)
    <table width="100%" style="border-collapse: collapse;">
    <tr>
        <!-- Column 1 -->
        <td style="vertical-align: top; width: 33%;">
        <span><strong>No:</strong> {{ $index+1 }}</span><br>
            <span><strong>Invoice:</strong> {{ $sale->voucher_no ?? 'N/A' }}</span><br>
            <span><strong>Sale Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d-M-Y h:i A') }}</span><br>
        </td>
        
        <!-- Column 2 -->
        <td style="vertical-align: top; width: 33%;">
        <span><strong>Customer:</strong> {{ $sale->customer_name ?? 'Walk-in Customer' }}</span><br>
            <span><strong>Return Transaction:</strong> {{ $sale->return_count }}</span><br>
            <span><strong>Items Returned:</strong> {{  $sale->total_items_returned  }}</span><br>
        </td>

        <!-- Column 3 -->
        <td style="vertical-align: top; width: 33%;">
        <span><strong>Total Refund:</strong> Rs {{  number_format($sale->total_refunded, 2)  }}</span><br>
            <span><strong>Last Return:</strong> {{ \Carbon\Carbon::parse($sale->last_return_date)->format('d-M-Y') }}</span><br>
        </td>
    </tr>
</table>

<div>
                    <strong style="font-size:11px; align-items:center;">Return History for {{ $sale->voucher_no }}</strong>
                    
                    @foreach($returnDetails[$sale->sale_id] as $returnIndex => $return)
                    <div style="border: 1px solid #ddd; border-radius: 5px; padding:0 5px;">
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <strong style="font-size:11px">Return #{{ $returnIndex + 1 }} - RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</strong>
                            <small style="color: #666; font-size:10px">
                                {{ \Carbon\Carbon::parse($return->created_at)->format('d-M-Y h:i A') }} 
                                | Rs {{ number_format($return->total_return_amount, 2) }}
                            </small>
                        </div>
        <table class="nested-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price (per unit)</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                                @foreach($return->items as $item)
                                <tr>
                                    <td>{{ $item->purchase->product_name }}</td>
                                    <td>{{ $item->quantity_returned }}</td>
                                    <td>Rs {{ number_format($item->unit_net_after_discount, 2) }}</td>
                                    <td>Rs {{ number_format($item->amount_refunded, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
        </table>
        @if($return->notes)
                        <p style=" font-style: italic; color: #666; font-size:10px">
                            <small>Notes: {{ $return->notes }}</small>
                        </p>
                        @endif
                        </div>
                    @endforeach
<!-- Summary -->
<div style="padding:5px;margin-top:3px;background: #e9ecef; border-radius: 5px; font-weight: bold; font-size:10px">
                        Total Returns: {{ $sale->return_count }} transactions | 
                        Total Items: {{ $sale->total_items_returned }} | 
                        Total Refunded: Rs {{ number_format($sale->total_refunded, 2) }}
                    </div>
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
