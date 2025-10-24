<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Category;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'category']) // eager load both
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pages.purchase.purchase', compact('purchases'));
    }

    public function create()
    {
        $activeCategories = Category::where('status', true)->get();
        return view('pages.purchase.add', compact('activeCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'   => 'required|string|max:255',
            'purchased_price'=> 'required|numeric',
            'sold_price'     => 'required|numeric',
            'category_id'    => 'nullable|exists:categories,id',
            'quantity'       => 'required|integer',
            'unit'           => 'required',
            'purchase_date'  => 'required|date',
            'supplier_name'  => 'nullable|string|max:255',
            'company'        => 'nullable|string|max:255',
            'address'        => 'nullable|string|max:255',
            'contact_info'   => 'nullable|string|max:255',
        ]);

        try {
            $supplier = Supplier::firstOrCreate([
                'name'         => $validated['supplier_name'],
                'company'      => $validated['company'],
                'address'      => $validated['address'],
                'contact_info' => $validated['contact_info'],
            ]);

            Purchase::create([
                'product_name'  => $validated['product_name'],
                'purchased_price'=> $validated['purchased_price'],
                'sold_price'         => $validated['sold_price'],
                'quantity'      => $validated['quantity'],
                'unit'          => $validated['unit'],
                'purchase_date' => $validated['purchase_date'],
                'supplier_id'   => $supplier->id,
                'category_id' => $validated['category_id'] ?? Category::where('name', 'Misc')->value('id'),
            ]);

            return redirect()->back()->with('success', 'Purchase added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again!');
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with(['supplier', 'category'])->findOrFail($id);
        return view('pages.purchase.see_detail', compact('purchase'));
    }

    public function edit($id)
    {
        $purchase = Purchase::with(['supplier', 'category'])->findOrFail($id);
        $activeCategories = Category::where('status', true)->get();
        return view('pages.purchase.update', compact('purchase', 'activeCategories'));
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'product_name'   => 'required|string|max:255',
        'purchased_price'=> 'required|numeric',
        'sold_price'          => 'required|numeric',
        'category_id'    => 'nullable|integer|exists:categories,id',
        'quantity'       => 'required|numeric',
        'unit'           => 'required',
        'purchase_date'  => 'required|date',
        'supplier_name'  => 'nullable|string|max:255',
        'company'        => 'nullable|string|max:255',
        'address'        => 'nullable|string|max:255',
        'contact_info'   => 'nullable|string|max:255',
    ]);
try{

    $purchase = Purchase::findOrFail($id);

    // Update supplier info if exists
    if ($purchase->supplier) {
        $purchase->supplier->update([
            'name'         => $validated['supplier_name'],
            'company'      => $validated['company'],
            'address'      => $validated['address'],
            'contact_info' => $validated['contact_info'],
        ]);
    }

    // Store the old product name before updating
    $oldName = $purchase->product_name;

    // Calculate total stock based on old product name (before renaming)
    $totalStock = Purchase::where('product_name', $oldName);

    // Update **all rows** with old product name to the new data
    Purchase::where('product_name', $oldName)
        ->update([
            'product_name'  => $validated['product_name'],
            'purchased_price'=> $validated['purchased_price'],
            'sold_price'         => $validated['sold_price'],
            'category_id'   => $validated['category_id'],
            'quantity'      => $validated['quantity'],
            'unit'          => $validated['unit'],
            'purchase_date' => $validated['purchase_date'],
        ]);
    return redirect()->route('purchase.index')->with('success', 'Product updated successfully.');
}catch(\Exception $e){
    return redirect()->route('purchase.index')->with('error', 'Something went wrong. Please try again!');
}
}



public function destroy($id)
{
    try {
        $purchase = Purchase::findOrFail($id);
        $supplier = $purchase->supplier;
        $category = $purchase->category;

        // ✅ Delete only the purchase itself (not sale items or return items)
        $purchase->delete();

        // ✅ If supplier has no more purchases, delete the supplier
        if ($supplier && $supplier->purchases()->count() === 0) {
            $supplier->delete();
        }

        // ✅ If category has no more purchases, delete the category
        if ($category && $category->purchases()->count() === 0) {
            $category->delete();
        }

        return redirect()->route('purchase.index')->with('success', 'Purchase deleted successfully!');
    } catch (\Exception $e) {
        return redirect()->route('purchase.index')->with('error', 'Something went wrong. Please try again!');
    }
}


    public function lowInventory(Request $request)
{
    $query = Purchase::with('supplier', 'category')
        ->select('id', 'product_name', 'quantity', 'sold_quantity', 'supplier_id', 'category_id', 'created_at')
        ->selectRaw('(quantity - sold_quantity) as remaining')
        ->whereRaw('(quantity - sold_quantity) <= 5') // Filter for low inventory
        ->orderByRaw('(quantity - sold_quantity) ASC') // Always sort by remaining first
        ->orderBy('created_at', 'desc'); // Secondary sort by latest

    $purchases = $query->paginate(10);

    return view('pages.purchase.lowInventory', compact('purchases'));
}

    

    

public function restock(Request $request)
{
    $request->validate([
        'purchase_id' => 'required|exists:purchases,id',
        'quantity' => 'required|integer|min:1',
        'purchase_date' => 'required|date'
    ]);

    try {
        $purchase = Purchase::findOrFail($request->purchase_id);
        $purchase->quantity += $request->quantity;
        $purchase->purchase_date = $request->purchase_date; // Update date
        $purchase->save();

        return redirect()->back()->with('success', 'Stock updated successfully!');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Something went wrong. Please try again!');
    }
}

}
