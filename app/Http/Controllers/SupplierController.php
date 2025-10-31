<?php

namespace App\Http\Controllers;
use App\Models\Supplier;
use App\Models\Purchase;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return view('pages.purchase.supply');
    }

    // NEW METHOD - Add after index()
public function getData(Request $request)
{
    $page = $request->get('page', 1);
    
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

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'pagination' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
                'first_item' => $suppliers->firstItem(),
                'last_item' => $suppliers->lastItem(),
                'has_more_pages' => $suppliers->hasMorePages(),
                'on_first_page' => $suppliers->onFirstPage(),
            ]
        ]);
    }
}
    
public function show($id)
{
    $supplier = Supplier::findOrFail($id);
    return view('pages.purchase.supplier_products', compact('supplier'));
}

public function getSupplierPurchases(Request $request, $id)
{
    $supplier = Supplier::findOrFail($id);
    $page = $request->get('page', 1);
    
    $purchases = Purchase::where('supplier_id', $supplier->id)
        ->orderBy('purchase_date', 'desc')
        ->simplePaginate(10);
    
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'supplier' => $supplier,
            'data' => $purchases->items(),
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'per_page' => $purchases->perPage(),
                'first_item' => ($purchases->currentPage() - 1) * $purchases->perPage() + 1,
                'last_item' => ($purchases->currentPage() - 1) * $purchases->perPage() + count($purchases->items()),
                'has_more_pages' => $purchases->hasMorePages(),
                'on_first_page' => $purchases->onFirstPage(),
            ]
        ]);
    }
}



public function edit($id)
{
    $supplier = Supplier::findOrFail($id);
    return view('pages.purchase.edit_supplier', compact('supplier'));
}

public function update(Request $request, $id)
{
    // Validate incoming data
    $validated = $request->validate([
        'supplier_name' => 'required|string|max:255',
        'company' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'contact_info' => 'nullable|string|max:255',
    ]);
    try{
        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            'name' => $validated['supplier_name'],
            'company' => $validated['company'],
            'address' => $validated['address'],
            'contact_info' => $validated['contact_info'],
        ]);
        return redirect()->route('supplier.index')->with('success', 'Supplier updated successfully!');
    }catch(\Exception $e){
        return redirect()->route('supplier.index')->with('error', 'Something went wrong. Please try again!');
    }
}


public function destroy(Request $request, $id)
{
    try {
        $supplier = Supplier::findOrFail($id);

        // Check which delete option was chosen
        if ($request->delete_option === 'with_purchases') {
            // Delete supplier + related purchases
            $supplier->purchases()->delete();
            $supplier->delete();
            $message = 'Supplier and all related purchases deleted successfully!';
        } else {
            // Delete only supplier
            $supplier->delete();
            $message = 'Supplier deleted successfully (purchases kept).';
        }

        return redirect()->route('supplier.index')->with('success', $message);

    } catch (\Exception $e) {
        return redirect()->route('supplier.index')->with('error', 'Something went wrong. Please try again!');
    }
}


}
