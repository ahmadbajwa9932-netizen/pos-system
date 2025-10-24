<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TestingController;

// ==============================================
// PUBLIC ROUTES (Guest only - redirects if authenticated)
// ==============================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup.post');
    Route::get('/password/reset', [AuthController::class, 'showReset'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showNewPasswordForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Logout (must be authenticated)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ==============================================
// PROTECTED ROUTES (Requires authentication)
// ==============================================
Route::middleware('auth')->group(function () {
    
    // Redirect root to sales
    Route::get('/', function () {
        return redirect()->route('sales.create');
    });

    // Purchase Routes
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/add', [PurchaseController::class, 'create'])->name('add');
        Route::post('/store', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PurchaseController::class, 'edit'])->name('update');
        Route::post('/edit/{id}', [PurchaseController::class, 'update'])->name('update.store');
        Route::get('/delete/{id}', [PurchaseController::class, 'destroy'])->name('delete');
        Route::get('/detail/{id}', [PurchaseController::class, 'show'])->name('detail');
        Route::post('/restock', [PurchaseController::class, 'restock'])->name('restock');
        Route::get('/low-inventory', [PurchaseController::class, 'lowInventory'])->name('lowInventory');
        Route::get('/purchase/pdf/current', [ExportController::class, 'exportPurchaseCurrentPagePDF'])->name('pdf.current');
        Route::get('/purchase/pdf/all', [ExportController::class, 'exportPurchaseAllPDF'])->name('pdf.all');
       // Print routes (same function, just send 'print' mode)
Route::get('/purchase/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportPurchaseCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/purchase/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportPurchaseAllPDF($request, 'print');
})->name('print.all');
    });

    // Supplier Routes
    Route::prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/show/{id}', [SupplierController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::post('/{id}', [SupplierController::class, 'update'])->name('update.post');
        Route::get('/{id}/delete', [SupplierController::class, 'destroy'])->name('delete');
        Route::get('/pdf/current', [ExportController::class, 'exportSupplierCurrentPagePDF'])->name('pdf.current');
        Route::get('/pdf/all', [ExportController::class, 'exportSupplierAllPDF'])->name('pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportSupplierCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportSupplierAllPDF($request, 'print');
})->name('print.all');
        Route::get('/{id}/purchases/pdf/current', [ExportController::class, 'exportSupplierPurchasesCurrentPagePDF'])->name('purchases.pdf.current');
        Route::get('/{id}/purchases/pdf/all', [ExportController::class, 'exportSupplierPurchasesAllPDF'])->name('purchases.pdf.all');
                // Print routes (same function, just send 'print' mode)
Route::get('{id}/purchases/print/current', function (Request $request,$id) {
    return app(ExportController::class)
        ->exportSupplierPurchasesCurrentPagePDF($request, 'print',$id);
})->name('purchases.print.current');

Route::get('{id}/purchases/print/all', function (Request $request,$id) {
    return app(ExportController::class)
        ->exportSupplierPurchasesAllPDF($request, 'print',$id);
})->name('purchases.print.all');
    });
    

    // Category Routes
    Route::prefix('category')->name('category.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
        Route::get('/add', [CategoryController::class, 'create'])->name('add');
        Route::post('/store', [CategoryController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::get('/delete/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}', [CategoryController::class, 'update'])->name('update.post');
        Route::post('/toggle-status/{id}', [CategoryController::class, 'toggleStatus'])->name('toggleStatus');
        Route::get('/pdf/current', [ExportController::class, 'exportCategoryCurrentPagePDF'])->name('pdf.current');
        Route::get('/pdf/all', [ExportController::class, 'exportCategoryAllPDF'])->name('pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportCategoryCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportCategoryAllPDF($request, 'print');
})->name('print.all');
        Route::get('/{id}/products/pdf/current', [ExportController::class, 'exportCategoryPurchasesCurrentPagePDF'])->name('purchases.pdf.current');
        Route::get('/{id}/products/pdf/all', [ExportController::class, 'exportCategoryPurchasesAllPDF'])->name('purchases.pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/{id}/products/print/current', function (Request $request,$id) {
    return app(ExportController::class)
        ->exportCategoryPurchasesCurrentPagePDF($request, 'print',$id);
})->name('purchases.print.current');

Route::get('/{id}/products/print/all', function (Request $request,$id) {
    return app(ExportController::class)
        ->exportCategoryPurchasesAllPDF($request, 'print',$id);
})->name('purchases.print.all');
    });

    // Sales Routes
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/next-voucher-no', [SalesController::class, 'getNextVoucherNo']);
        Route::get('/create', [SalesController::class, 'create'])->name('create');
        Route::post('/store', [SalesController::class, 'store'])->name('store');
        Route::get('/daily', [SalesController::class, 'dailySales'])->name('daily');
        Route::get('/daily/pdf/current', [ExportController::class, 'exportDailySalesCurrentPagePDF'])->name('daily.pdf.current');
        Route::get('/daily/pdf/all', [ExportController::class, 'exportDailySalesAllPDF'])->name('daily.pdf.all');
         // Print routes (same function, just send 'print' mode)
Route::get('/daily/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportDailySalesCurrentPagePDF($request, 'print');
})->name('daily.print.current');

Route::get('/daily/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportDailySalesAllPDF($request, 'print');
})->name('daily.print.all');
        Route::get('/general', [SalesController::class, 'generalSales'])->name('general');
        Route::get('/general/pdf/current', [ExportController::class, 'exportGeneralSalesCurrentPagePDF'])->name('general.pdf.current');
        Route::get('/general/pdf/all', [ExportController::class, 'exportGeneralSalesAllPDF'])->name('general.pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/general/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportGeneralSalesCurrentPagePDF($request, 'print');
})->name('general.print.current');

Route::get('/general/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportGeneralSalesAllPDF($request, 'print');
})->name('general.print.all');
        Route::get('/{voucher_no}/details', [SalesController::class, 'showDetails'])->name('details');
        Route::get('/credit', [SalesController::class, 'creditSales'])->name('credit');
        Route::delete('/credit/{saleId}/return', [SalesController::class, 'returnCreditSale'])->name('credit.return');
        Route::delete('/credit/{saleId}/delete', [SalesController::class, 'deleteCreditSale'])->name('credit.delete');
        Route::delete('/credit/{saleId}/item/{saleItemId}/delete', [SalesController::class, 'deleteCreditSaleItem'])->name('credit.item.delete');
        Route::patch('/credit/{saleId}/item/{saleItemId}/reduce', [SalesController::class, 'reduceCreditSaleItemQuantity'])->name('credit.item.reduce');
        Route::put('/{id}/mark-paid', [SalesController::class, 'markAsPaid'])->name('markPaid');
        Route::post('/add-payment', [SalesController::class, 'addPayment'])->name('addPayment');
        Route::get('/credit/pdf/current', [ExportController::class, 'exportCreditSalesCurrentPagePDF'])->name('credit.pdf.current');
        Route::get('/credit/pdf/all', [ExportController::class, 'exportCreditSalesAllPDF'])->name('credit.pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/credit/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportCreditSalesCurrentPagePDF($request, 'print');
})->name('credit.print.current');

Route::get('/credit/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportCreditSalesAllPDF($request, 'print');
})->name('credit.print.all');
        
        // Sale Returns
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [SaleReturnController::class, 'index'])->name('index');
            Route::get('/partial-screen/{saleId}', [SaleReturnController::class, 'showPartialReturn'])->name('partial.screen');
            Route::get('/create/{saleId}', [SaleReturnController::class, 'create'])->name('create');
            Route::post('/full/{saleId}', [SaleReturnController::class, 'processFullReturn'])->name('full');
            Route::post('/partial/{saleId}', [SaleReturnController::class, 'processPartialReturn'])->name('partial');
            Route::post('/{saleId}', [SaleReturnController::class, 'store'])->name('store');
            Route::get('/details/{saleId}', [SaleReturnController::class, 'showDetails'])->name('details');
            Route::get('/pdf/current', [ExportController::class, 'exportReturnSalesCurrentPagePDF'])->name('pdf.current');
        Route::get('/pdf/all', [ExportController::class, 'exportReturnSalesAllPDF'])->name('pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportReturnSalesCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportReturnSalesAllPDF($request, 'print');
})->name('print.all');
        });
    });
// Get customer balance for receipt page
Route::get('/customer-balance/{customerId}', [SalesController::class, 'getCustomerBalance'])
    ->name('customer.balance');
    // Product & Stock Routes
    Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
    Route::get('/pos/product-history/{purchaseId}', [ProductController::class, 'getProductHistory'])->name('pos.product.history');
    Route::get('/check-stock/{id}', [SalesController::class, 'checkStock'])->name('check.stock');

    // Customer Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/credit', [CustomerController::class, 'creditCustomerIndex'])->name('credit.index');
        Route::get('/credit/{id}/delete', [CustomerController::class, 'destroyCreditCustomer'])->name('credit.destroy');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/purchases', [CustomerController::class, 'showPurchases'])->name('purchases');
        Route::get('/{id}/delete', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/credit/{id}/purchases', [CustomerController::class, 'showCreditPurchases'])->name('credit.purchases');
        Route::get('/pdf/current', [ExportController::class, 'exportCustomerCurrentPagePDF'])->name('pdf.current');
        Route::get('/pdf/all', [ExportController::class, 'exportCustomerAllPDF'])->name('pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportCustomerCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportCustomerAllPDF($request, 'print');
})->name('print.all');
        Route::get('/credit/pdf/current', [ExportController::class, 'exportCreditCustomerCurrentPagePDF'])->name('credit.pdf.current');
        Route::get('/credit/pdf/all', [ExportController::class, 'exportCreditCustomerAllPDF'])->name('credit.pdf.all');
    // Print routes (same function, just send 'print' mode)
Route::get('credit/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportCreditCustomerCurrentPagePDF($request, 'print');
})->name('credit.print.current');

Route::get('credit/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportCreditCustomerAllPDF($request, 'print');
})->name('credit.print.all');
    });

    // Credit Sales
    Route::get('/credit-sales/details/{voucher_no}', [SalesController::class, 'creditShowDetails'])->name('credit-sales.details');
    Route::get('/api/credit-sales-notifications', [SalesController::class, 'getCreditSalesNotifications']);

    // Revenue Routes
    Route::prefix('revenue')->name('revenue.')->group(function () {
        Route::get('/', [RevenueController::class, 'index'])->name('index');
        Route::get('/export/pdf', [RevenueController::class, 'exportPDF'])->name('export.pdf');
        Route::get('/export/excel', [RevenueController::class, 'exportExcel'])->name('export.excel');
    });

    // Expense Routes
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/store', [ExpenseController::class, 'store'])->name('store');
        Route::post('/store-multiple', [ExpenseController::class, 'storeMultiple'])->name('storeMultiple');
        Route::get('/delete/{id}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::get('/pdf/current', [ExportController::class, 'exportExpenseCurrentPagePDF'])->name('pdf.current');
        Route::get('/pdf/all', [ExportController::class, 'exportExpenseAllPDF'])->name('pdf.all');
        // Print routes (same function, just send 'print' mode)
Route::get('/print/current', function (Request $request) {
    return app(ExportController::class)
        ->exportExpenseCurrentPagePDF($request, 'print');
})->name('print.current');

Route::get('/print/all', function (Request $request) {
    return app(ExportController::class)
        ->exportExpenseAllPDF($request, 'print');
})->name('print.all');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        // Sales Reports
        Route::get('/sales', [SalesReportController::class, 'index'])->name('sales');
        Route::get('/sales/date-range', [SalesReportController::class, 'dateRangeSales'])->name('sales.date-range');
        Route::get('/sales/products', [SalesReportController::class, 'productWiseSales'])->name('sales.products');
        Route::get('/sales/categories', [SalesReportController::class, 'categoryWiseSales'])->name('sales.categories');
        Route::get('/sales/transactions', [SalesReportController::class, 'transactionLog'])->name('sales.transactions');
        Route::get('/sales/tax', [SalesReportController::class, 'salesTaxReport'])->name('sales.tax');
        Route::get('/sales/time-analysis', [SalesReportController::class, 'timeBasedAnalysis'])->name('sales.time-analysis');
        // Route::post('/sales/export-pdf', [SalesReportController::class, 'exportPDF'])->name('sales.export-pdf');
        Route::post('/sales/export-excel', [SalesReportController::class, 'exportExcel'])->name('sales.export-excel');
        Route::post('/sales/export-pdf-full', [SalesReportController::class, 'exportSalesReportPDF'])->name('sales.export.pdf.full');
        
        // Financial Reports
        Route::prefix('financial')->name('financial.')->group(function () {
            Route::get('/', [FinancialReportController::class, 'index'])->name('index');
            Route::get('/summary', [FinancialReportController::class, 'getFinancialSummary'])->name('summary');
            Route::get('/top-products', [FinancialReportController::class, 'getTopProducts'])->name('top-products');
            Route::get('/category-performance', [FinancialReportController::class, 'getCategoryPerformance'])->name('category-performance');
            Route::get('/export-pdf', [FinancialReportController::class, 'exportPDF'])->name('export-pdf');
        });

        // purchase and supplier report routes
        Route::prefix('purchase')->group(function () {
            // Main report page
            Route::get('/', [PurchaseReportController::class, 'index'])->name('purchase.index');
            
            // API endpoints for data
            Route::get('/summary', [PurchaseReportController::class, 'getSummary']);
            Route::get('/purchases', [PurchaseReportController::class, 'getPurchases']);
            Route::get('/supplier-summary', [PurchaseReportController::class, 'getSupplierSummary']);
            Route::get('/product-summary', [PurchaseReportController::class, 'getProductSummary']);
            Route::get('/category-analysis', [PurchaseReportController::class, 'getCategoryAnalysis']);
            Route::get('/monthly-trend', [PurchaseReportController::class, 'getMonthlyTrend']);
            Route::get('/price-trend', [PurchaseReportController::class, 'getPriceTrend']);
            
            // Filter dropdown data
            Route::get('/suppliers-list', [PurchaseReportController::class, 'getSuppliers']);
            Route::get('/categories-list', [PurchaseReportController::class, 'getCategories']);
            
            // Export routes (optional - implement later)
            // Route::post('/export-pdf', [PurchaseReportController::class, 'exportPDF']);
            // Route::post('/export-excel', [PurchaseReportController::class, 'exportExcel']);
        });

    });

    // Testing/Backup Routes (Admin only)
    Route::get('/show/delete-all', [TestingController::class, 'index'])->name('show.delete.all');
    Route::delete('/delete-all', [TestingController::class, 'deleteAll'])->name('delete.all');
});