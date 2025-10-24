<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Sale_item;
use App\Models\Expense;
use App\Models\SaleReturn;   
use App\Models\SaleReturnItem;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    
    // ---------------------------------
    //         Purchase page pdf's
    // ---------------------------------

    public function exportPurchaseCurrentPagePDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
    $purchases = Purchase::with(['supplier', 'category'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

    $data = [
        'purchases' => $purchases,
        'title' => 'Current Page Purchases Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];

    // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.purchase.pdf1', $data);
    }
$pdf=Pdf::loadView('export.pdf.purchase.pdf1',$data);
    return $pdf->download('purchase_current_page.pdf');
}

public function exportPurchaseAllPDF(Request $request,$mode = 'pdf')
{
    $purchases = Purchase::with(['supplier', 'category'])
        ->orderBy('created_at', 'desc')
        ->get();

    $data =[
        'purchases' => $purchases,
        'title' => 'All Purchases Report',
        'subTitle' => 'Complete Purchase Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];

    // 🔹 If this request is for printing
    if ($mode === 'print') {
        $data['isPrint'] = true;
        return view('export.pdf.purchase.pdf1', $data);
    }
$pdf=Pdf::loadView('export.pdf.purchase.pdf1',$data);
    return $pdf->download('purchase_all_records.pdf');
}

public function exportSupplierCurrentPagePDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
    $suppliers = Supplier::withCount('purchases')
    ->with(['purchases' => function ($query) {
        $query->select('supplier_id', 'purchased_price', 'quantity');
    }])
    ->orderBy('created_at', 'desc')
    ->paginate(10);

// Calculate total purchase amount manually
foreach ($suppliers as $supplier) {
    $supplier->total_purchase_amount = $supplier->purchases->sum(function ($purchase) {
        return $purchase->purchased_price * $purchase->quantity;
    });
}

    $data= [
        'suppliers' => $suppliers,
        'title' => 'Current Page supplier Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.purchase.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.purchase.pdf2',$data);
    return $pdf->download('supplier_current_page.pdf');

}

public function exportSupplierAllPDF(Request $request,$mode = 'pdf')
{
    $suppliers = Supplier::withCount('purchases')->with(['purchases' => function ($query) {
        $query->select('supplier_id', 'purchased_price', 'quantity');
    }])
    ->orderBy('created_at', 'desc')
        ->get();
        // Calculate total purchase amount manually
        foreach ($suppliers as $supplier) {
            $supplier->total_purchase_amount = $supplier->purchases->sum(function ($purchase) {
                return $purchase->purchased_price * $purchase->quantity;
            });
        }

     $data=[
        'suppliers' => $suppliers,
        'title' => 'All suppliers Report',
        'subTitle' => 'Complete Supplier Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.purchase.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.purchase.pdf2',$data);
    return $pdf->download('supplier_all_records.pdf');
}

public function exportSupplierPurchasesCurrentPagePDF(Request $request,$mode = 'pdf',$id)
{
    $supplier = Supplier::findOrFail($id);
    $purchases = Purchase::where('supplier_id', $supplier->id)
        ->orderBy('purchase_date', 'desc')
        ->get();

     $data=[
        'supplier' => $supplier,
        'purchases' => $purchases,
        'title' => 'Current Page Suppliers Report',
        'subTitle' => 'Products Supplied By '.$supplier->name,
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.purchase.pdf3', $data);
    }
    $pdf = Pdf::loadView('export.pdf.purchase.pdf3',$data);
    return $pdf->download('supplier_purchases_current_page.pdf');
}

public function exportSupplierPurchasesAllPDF(Request $request,$mode = 'pdf',$id)
{
    $supplier = Supplier::findOrFail($id);
    $purchases = Purchase::where('supplier_id', $supplier->id)
        ->orderBy('purchase_date', 'desc')
        ->get();

    $data= [
        'supplier' => $supplier,
        'purchases' => $purchases,
        'title' => 'All Suppliers Report',
        'subTitle' => 'Products Supplied By '.$supplier->name,
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.purchase.pdf3', $data);
    }
    $pdf = Pdf::loadView('export.pdf.purchase.pdf3',$data);
    return $pdf->download('supplier_purchases_all_records.pdf');
}
// ---------------------------------
    //         Category page pdf's
    // ---------------------------------

    public function exportCategoryCurrentPagePDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
    $categories = Category::withCount('purchases')->orderBy('created_at', 'desc')
        ->paginate(10);

$data= [
        'categories' => $categories,
        'title' => 'Current Page Categories Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.category.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.category.pdf1',$data);
    return $pdf->download('categories_current_page.pdf');
}

public function exportCategoryAllPDF(Request $request,$mode = 'pdf')
{
    $categories = Category::withCount('purchases')->orderBy('created_at', 'desc')
        ->get();

   $data= [
        'categories' => $categories,
        'title' => 'All categories Report',
        'subTitle' => 'Complete Categories Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.category.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.category.pdf1',$data);
    return $pdf->download('categories_all_records.pdf');
}

public function exportCategoryPurchasesCurrentPagePDF(Request $request,$mode = 'pdf',$id)
{
    $category = Category::findOrFail($id);
    $purchases = Purchase::where('category_id', $category->id)
        ->orderBy('purchase_date', 'desc')
        ->get();

    $data= [
        'category' => $category,
        'purchases' => $purchases,
        'title' => 'Current Page categories Report',
        'subTitle' => 'Products of '.$category->name,
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.category.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.category.pdf2',$data);
    return $pdf->download('category_products_current_page.pdf');
}

public function exportCategoryPurchasesAllPDF(Request $request,$mode = 'pdf',$id)
{
    $category = Category::findOrFail($id);
    $purchases = Purchase::where('category_id', $category->id)
        ->orderBy('purchase_date', 'desc')
        ->get();

    $data=[
        'category' => $category,
        'purchases' => $purchases,
        'title' => 'All categories Report',
        'subTitle' => 'All Products of '.$category->name,
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.category.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.category.pdf2', $data);
    return $pdf->download('category_products_all_records.pdf');
}
// ---------------------------------
    //         Purchase page pdf's
    // ---------------------------------

    public function exportExpenseCurrentPagePDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
    $expenses = Expense::orderBy('created_at', 'desc')
        ->paginate(10);

    $data = [
        'expenses' => $expenses,
        'title' => 'Current Page Expenses Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];

    // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.expenses.pdf1', $data);
    }
$pdf=Pdf::loadView('export.pdf.expenses.pdf1',$data);
    return $pdf->download('expense_current_page.pdf');
}


public function exportExpenseAllPDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
    $expenses = Expense::orderBy('created_at', 'desc')
        ->get();

    $data = [
        'expenses' => $expenses,
        'title' => 'All Expenses Report',
        'subTitle' => 'Complete Expenses Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];

    // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.expenses.pdf1', $data);
    }
$pdf=Pdf::loadView('export.pdf.expenses.pdf1',$data);
    return $pdf->download('expense_all_page.pdf');
}

// ---------------------------------
    //         Customer page pdf's
    // ---------------------------------

    public function exportCustomerCurrentPagePDF(Request $request,$mode = 'pdf')
{
    // Current page (same as in your paginated list)
     // Get all customers with sales count and payment type breakdown
     $customers = Customer::withCount(['sales' => function ($query) {
        $query->whereNull('deleted_at'); // ✅ exclude soft-deleted sales
    }])
        ->with(['sales' => function ($query) {
            $query->whereNull('deleted_at')->latest(); // Get latest sale for last purchase date
        }])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        $salesBreakdown = [];
    foreach ($customers as $customer) {
        $salesBreakdown[$customer->id] = DB::table('sales')
            ->select('payment_type', DB::raw('count(*) as count'))
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');
    }

    $data=[
        'customers' => $customers,
        'salesBreakdown' => $salesBreakdown,
        'title' => 'Current Page Customer Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.customer.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.customer.pdf1', $data);
    return $pdf->download('customer_current_page.pdf');
}

public function exportCustomerAllPDF(Request $request,$mode = 'pdf')
{
    $customers = Customer::withCount(['sales' => function ($query) {
        $query->whereNull('deleted_at'); // ✅ exclude soft-deleted sales
    }])
        ->with(['sales' => function ($query) {
            $query->whereNull('deleted_at')->latest(); // Get latest sale for last purchase date
        }])
        ->orderBy('created_at', 'desc')
        ->get();

        $salesBreakdown = [];
    foreach ($customers as $customer) {
        $salesBreakdown[$customer->id] = DB::table('sales')
            ->select('payment_type', DB::raw('count(*) as count'))
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');
    }

    $data= [
        'customers' => $customers,
        'salesBreakdown' => $salesBreakdown,
        'title' => 'All Customers Report',
        'subTitle' => 'View All Customers',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.customer.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.customer.pdf1',$data);
    return $pdf->download('customer_all_records.pdf');
}

public function exportCreditCustomerCurrentPagePDF(Request $request,$mode = 'pdf')
{
    $customers = Customer::withCount(['sales as credit_sales_count' => function ($query) {
        $query->where('payment_type', 'credit');
    }])
    ->with(['sales' => function ($query) {
        $query->where('payment_type', 'credit')->latest();
    }])
    ->has('sales', '>=', 1) // ✅ Must have at least 1 sale
    ->whereHas('sales', function ($query) {
        $query->where('payment_type', 'credit'); // ✅ At least 1 credit sale
    })
    ->orderBy('created_at', 'desc')
    ->paginate(10);

    $data= [
        'customers' => $customers,
        'title' => 'Current Page Credit Customer Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.customer.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.customer.pdf2',$data);
    return $pdf->download('credit_customer_current_page.pdf');
}

public function exportCreditCustomerAllPDF(Request $request,$mode = 'pdf')
{
    $customers = Customer::withCount(['sales as credit_sales_count' => function ($query) {
        $query->where('payment_type', 'credit');
    }])
    ->with(['sales' => function ($query) {
        $query->where('payment_type', 'credit')->latest();
    }])
    ->has('sales', '>=', 1) // ✅ Must have at least 1 sale
    ->whereHas('sales', function ($query) {
        $query->where('payment_type', 'credit'); // ✅ At least 1 credit sale
    })
    ->orderBy('created_at', 'desc')
    ->get();

    $data=[
        'customers' => $customers,
        'title' => 'All Credit Customers Report',
        'subTitle' => 'View All Credit Customers',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.customer.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.customer.pdf2', $data);
    return $pdf->download('credit_customer_all_records.pdf');
}

// ---------------------------------
    //         Daily Sales page pdf's
    // ---------------------------------

    public function exportDailySalesCurrentPagePDF(Request $request,$mode = 'pdf')
{
    $today = now()->format('Y-m-d');

    // Get sales with calculated remaining quantities after returns
    $dailySales = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'customers.name as customer_name',
            'sales.grand_total',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id') 
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereDate('sales.created_at', $today)
        ->whereNull('sales.deleted_at')
        // ->where('sales.status', 'paid')
        ->whereIn('sales.payment_type',['cash','card'])
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->paginate(10);

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $dailySales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');

    $data= [
        'dailySales' => $dailySales,
        'saleItems' => $saleItems,
        'title' => 'Current Page Daily Sales Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.sales.pdf1',$data);
    return $pdf->download('dailySales_current_page.pdf');
}

public function exportDailySalesAllPDF(Request $request,$mode = 'pdf')
{
    $today = now()->format('Y-m-d');

    // Get sales with calculated remaining quantities after returns
    $dailySales = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'customers.name as customer_name',
            'sales.grand_total',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id') 
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereDate('sales.created_at', $today)
        ->whereNull('sales.deleted_at')
        // ->where('sales.status', 'paid')
        ->whereIn('sales.payment_type',['cash','card'])
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->get();

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $dailySales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');

   $data= [
        'dailySales' => $dailySales,
        'saleItems' => $saleItems,
        'title' => 'All Daily Sales Report',
        'subTitle' => 'View All Daily Sales',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf1', $data);
    }
    $pdf = Pdf::loadView('export.pdf.sales.pdf1',$data);
    return $pdf->download('dailySales_all_records.pdf');
}


public function exportGeneralSalesCurrentPagePDF(Request $request,$mode = 'pdf')
{
$query = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'sales.grand_total',
            'customers.name as customer_name',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
          // 🔹 ADD THESE LINES:
    ->whereNull('sales.deleted_at');
    // ->whereNull('customers.deleted_at');

    // Group and paginate
    $generalSales = $query
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->paginate(10); // 🔹 CHANGE THIS LINE

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $generalSales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');
        $data=[
            'generalSales' => $generalSales,
            'saleItems' => $saleItems,
            'title' => 'Current Page General Sales Report',
        'subTitle' => 'Showing Only Current Page Records',
            'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
        ];
        // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf2', $data);
    }
        $pdf = Pdf::loadView('export.pdf.sales.pdf2', $data);
        return $pdf->download('generalSales_all_records.pdf');
    }


public function exportGeneralSalesAllPDF(Request $request,$mode = 'pdf')
{
$query = \DB::table('sales')
        ->select(
            'sales.id as sale_id',
            'sales.voucher_no',
            'sales.created_at as sale_date',
            'sales.payment_type',
            'sales.grand_total',
            'customers.name as customer_name',
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN 1 
                    ELSE 0 
                END
            ) as total_items'),
            \DB::raw('SUM(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                    ELSE 0 
                END
            ) as total_amount'),
            \DB::raw('GROUP_CONCAT(
                CASE 
                    WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                    THEN purchases.product_name 
                    ELSE NULL 
                END 
                ORDER BY purchases.id ASC
                SEPARATOR ", "
            ) as products'),
            \DB::raw('COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as total_returned_amount'),
            \DB::raw('sales.grand_total - COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) as adjusted_grand_total'),
            \DB::raw('CASE 
            WHEN sales.grand_total > 0 
            THEN COALESCE(
                (SELECT SUM(total_return_amount) 
                 FROM sale_returns 
                 WHERE sale_returns.sale_id = sales.id), 
                0
            ) / sales.grand_total
            ELSE 0 
        END as return_percentage'),
        )
        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
          // 🔹 ADD THESE LINES:
    ->whereNull('sales.deleted_at');
    // ->whereNull('customers.deleted_at');

    // Group and paginate
    $generalSales = $query
        ->groupBy('sales.id', 'sales.voucher_no', 'sales.created_at', 'sales.payment_type','customers.name','sales.grand_total')
        ->having('total_items', '>', 0) // Only show sales that have remaining items
        ->orderBy('sales.created_at', 'desc')
        ->get(); // 🔹 CHANGE THIS LINE

    // Get detailed items with remaining quantities and ADJUSTED DISCOUNT VALUES
    $saleIds = $generalSales->pluck('sale_id');
    $saleItems = \DB::table('sale_items')
        ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
        ->leftJoin(\DB::raw('(
            SELECT sale_item_id, SUM(quantity_returned) as total_returned 
            FROM sale_return_items 
            GROUP BY sale_item_id
        ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
        ->whereIn('sale_items.sale_id', $saleIds)
        ->select(
            'sale_items.sale_id',
            'purchases.product_name',
            'sale_items.price',
            'sale_items.quantity as original_quantity',
            \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
            \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
            'sale_items.discount_type',
            'sale_items.discount_value',
            'sale_items.discount_amount as original_discount_amount',
            // ✅ Calculate adjusted discount amount based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_amount'),
            // ✅ Calculate adjusted discount percentage based on remaining quantity
            \DB::raw('CASE 
                WHEN sale_items.discount_type = "percentage" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN sale_items.discount_value
                WHEN sale_items.discount_type = "amount" AND sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
                ELSE 0 
            END as adjusted_discount_value'),
            \DB::raw('CASE 
                WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
                THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
                ELSE 0 
            END as total_after_discount')
        )
        ->having('remaining_quantity', '>', 0) // Only items with remaining quantity
        ->get()
        ->groupBy('sale_id');
        $data=[
            'generalSales' => $generalSales,
            'saleItems' => $saleItems,
            'title' => 'All General Sales Report',
            'subTitle' => 'View All General Sales',
            'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
        ];
        // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf2', $data);
    }
    $pdf = Pdf::loadView('export.pdf.sales.pdf2', $data);
        return $pdf->download('generalSales_all_records.pdf');
    }

    public function exportCreditSalesCurrentPagePDF(Request $request,$mode = 'pdf')
    {
    $creditSales = Sale::with('customer')
    ->where('payment_type', 'credit')
    ->whereIn('status', ['unpaid','partial'])
    ->orderBy('created_at', 'desc')
    ->paginate(10);

// Get detailed items with remaining quantities for credit sales
$saleIds = $creditSales->pluck('id');
$saleItems = \DB::table('sale_items')
    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
    ->leftJoin(\DB::raw('(
        SELECT sale_item_id, SUM(quantity_returned) as total_returned 
        FROM sale_return_items 
        GROUP BY sale_item_id
    ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
    ->whereIn('sale_items.sale_id', $saleIds)
    ->select(
        'sale_items.id',  // ADD THIS LINE - this is the missing piece!
        'sale_items.sale_id',
        'purchases.product_name',
        'sale_items.price',
        'sale_items.quantity as original_quantity',
        \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
        \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
        'sale_items.discount_type',
        'sale_items.discount_value',
        'sale_items.discount_amount as original_discount_amount',
        \DB::raw('CASE 
            WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
            THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
            ELSE 0 
        END as adjusted_discount_amount'),
        \DB::raw('CASE 
            WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
            THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
            ELSE 0 
        END as total_after_discount')
    )
    ->having('remaining_quantity', '>', 0)
    ->paginate(10)
    ->groupBy('sale_id');
    $data= [
        'creditSales' => $creditSales,
        'saleItems' => $saleItems,
  'title' => 'Current Page Credit Sales Report',
        'subTitle' => 'Showing Only Current Page Records',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf3', $data);
    }
    $pdf = Pdf::loadView('export.pdf.sales.pdf3',$data);
    return $pdf->download('creditSales_CurrentPage_records.pdf');
}


    public function exportCreditSalesAllPDF(Request $request,$mode = 'pdf')
    {
    $creditSales = Sale::with('customer')
    ->where('payment_type', 'credit')
    ->whereIn('status', ['unpaid','partial'])
    ->orderBy('created_at', 'desc')
    ->paginate(10);

// Get detailed items with remaining quantities for credit sales
$saleIds = $creditSales->pluck('id');
$saleItems = \DB::table('sale_items')
    ->join('purchases', 'sale_items.purchase_id', '=', 'purchases.id')
    ->leftJoin(\DB::raw('(
        SELECT sale_item_id, SUM(quantity_returned) as total_returned 
        FROM sale_return_items 
        GROUP BY sale_item_id
    ) as returned_qty'), 'sale_items.id', '=', 'returned_qty.sale_item_id')
    ->whereIn('sale_items.sale_id', $saleIds)
    ->select(
        'sale_items.id',  // ADD THIS LINE - this is the missing piece!
        'sale_items.sale_id',
        'purchases.product_name',
        'sale_items.price',
        'sale_items.quantity as original_quantity',
        \DB::raw('COALESCE(returned_qty.total_returned, 0) as returned_quantity'),
        \DB::raw('sale_items.quantity - COALESCE(returned_qty.total_returned, 0) as remaining_quantity'),
        'sale_items.discount_type',
        'sale_items.discount_value',
        'sale_items.discount_amount as original_discount_amount',
        \DB::raw('CASE 
            WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
            THEN (sale_items.discount_amount * (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) / sale_items.quantity)
            ELSE 0 
        END as adjusted_discount_amount'),
        \DB::raw('CASE 
            WHEN sale_items.quantity - COALESCE(returned_qty.total_returned, 0) > 0 
            THEN (sale_items.quantity - COALESCE(returned_qty.total_returned, 0)) * (sale_items.total_after_discount / sale_items.quantity)
            ELSE 0 
        END as total_after_discount')
    )
    ->having('remaining_quantity', '>', 0)
    ->get()
    ->groupBy('sale_id');
    $data= [
        'creditSales' => $creditSales,
        'saleItems' => $saleItems,
        'title' => 'All Credit Sales Report',
        'subTitle' => 'View All Credit Sales',
        'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
    ];
 // 🔹 If this request is for printing, just return the HTML view
    if ($mode === 'print') {
        $data['isPrint'] = true; // helps detect print mode in the Blade
        return view('export.pdf.sales.pdf3', $data);
    }
    $pdf = Pdf::loadView('export.pdf.sales.pdf3',$data);
    return $pdf->download('creditSales_all_records.pdf');
}


public function exportReturnSalesCurrentPagePDF(Request $request,$mode = 'pdf')
    {
   // Group returns by sale_id and get aggregated data
   $returnedSales = DB::table('sale_returns')
   ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
   ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
   ->select([
       'sales.id as sale_id',
       'sales.voucher_no',
       'sales.created_at as sale_date',
       'sales.grand_total as original_amount',
       'customers.name as customer_name',
       DB::raw('COUNT(sale_returns.id) as return_count'),
       DB::raw('SUM(sale_returns.total_return_amount) as total_refunded'),
       DB::raw('SUM((SELECT COUNT(*) FROM sale_return_items WHERE sale_return_items.sale_return_id = sale_returns.id)) as total_items_returned'),
       DB::raw('MAX(sale_returns.created_at) as last_return_date'),
       DB::raw('GROUP_CONCAT(sale_returns.status) as return_statuses')
   ])
   ->groupBy([
       'sales.id', 
       'sales.voucher_no', 
       'sales.created_at',
       'sales.grand_total',
       'customers.name'
   ])
   ->orderBy('last_return_date', 'desc')
   ->paginate(10);

// Get detailed return data for expandable rows
$returnDetails = collect();
foreach ($returnedSales as $sale) {
   $details = SaleReturn::with(['items.purchase'])
       ->where('sale_id', $sale->sale_id)
       ->get();
   $returnDetails[$sale->sale_id] = $details;
}
$data= [
    'returnedSales' => $returnedSales,
    'returnDetails' => $returnDetails,
    'title' => 'Current Page Returned Sales Report',
        'subTitle' => 'Showing Only Current Page Records',
    'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
];
if ($mode === 'print') {
    $data['isPrint'] = true; // helps detect print mode in the Blade
    return view('export.pdf.sales.pdf4', $data);
}
$pdf = Pdf::loadView('export.pdf.sales.pdf4',$data);
return $pdf->download('ReturnSales_currentPage_records.pdf');
}


public function exportReturnSalesAllPDF(Request $request,$mode = 'pdf')
    {
   // Group returns by sale_id and get aggregated data
   $returnedSales = DB::table('sale_returns')
   ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
   ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
   ->select([
       'sales.id as sale_id',
       'sales.voucher_no',
       'sales.created_at as sale_date',
       'sales.grand_total as original_amount',
       'customers.name as customer_name',
       DB::raw('COUNT(sale_returns.id) as return_count'),
       DB::raw('SUM(sale_returns.total_return_amount) as total_refunded'),
       DB::raw('SUM((SELECT COUNT(*) FROM sale_return_items WHERE sale_return_items.sale_return_id = sale_returns.id)) as total_items_returned'),
       DB::raw('MAX(sale_returns.created_at) as last_return_date'),
       DB::raw('GROUP_CONCAT(sale_returns.status) as return_statuses')
   ])
   ->groupBy([
       'sales.id', 
       'sales.voucher_no', 
       'sales.created_at',
       'sales.grand_total',
       'customers.name'
   ])
   ->orderBy('last_return_date', 'desc')
   ->get();

// Get detailed return data for expandable rows
$returnDetails = collect();
foreach ($returnedSales as $sale) {
   $details = SaleReturn::with(['items.purchase'])
       ->where('sale_id', $sale->sale_id)
       ->get();
   $returnDetails[$sale->sale_id] = $details;
}
$data= [
    'returnedSales' => $returnedSales,
    'returnDetails' => $returnDetails,
    'title' => 'All Return Sales Report',
    'subTitle' => 'View All Return Sales',
    'generatedOn' => Carbon::now()->format('d-M-Y h:i A')
];
if ($mode === 'print') {
    $data['isPrint'] = true; // helps detect print mode in the Blade
    return view('export.pdf.sales.pdf4', $data);
}
$pdf = Pdf::loadView('export.pdf.sales.pdf4',$data);
return $pdf->download('ReturnSales_all_records.pdf');
}
}
