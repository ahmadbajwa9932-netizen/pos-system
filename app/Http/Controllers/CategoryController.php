<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Purchase;


class CategoryController extends Controller
{
    public function create()
    {
        return view('pages.category.add');
    }

    public function index()
{
    $categories = Category::withCount('purchases')->orderBy('created_at', 'desc')->paginate(10); 
    return view('pages.category.category', compact('categories'));
}


    public function store(Request $request)
{
    $request->validate([
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string',
    ]);

    try {
        if (Category::where('name', $request->name)->exists()) {
            return redirect()->back()->with('error', 'Category name already exists!');
        }else{
            Category::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
            return redirect()->back()->with('success', 'Category created successfully!');
        }
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Something went wrong. Please try again!');
    }
}

public function toggleStatus($id)
{
    $category = Category::findOrFail($id);
    // Prevent toggling for the default Misc category
    if ($category->name === 'Misc') {
        return redirect()->back()->with('error', 'The default category cannot be deactivated.');
    }
    $category->status = !$category->status;
    $category->save();
    return redirect()->back()->with('success', 'Category status updated successfully!');
}

public function edit($id)
{
    $category = Category::findOrFail($id);
    return view('pages.category.update', compact('category'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string',
    ]);

    try{
      $category = Category::findOrFail($id);
      if ($category->name === 'Misc') {
        return redirect()->route('category.index')->with('error', 'The default category cannot be edited.');
    }    
        $category->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        return redirect()->route('category.index')->with('success', 'Category updated successfully!');
    }catch (\Exception $e) {
        return redirect()->route('category.index')->with('error', 'Something went wrong. Please try again.');
    }
}

public function show($id){
    $category = Category::findOrFail($id);
    $purchases = Purchase::where('category_id', $category->id)
        ->orderBy('purchase_date', 'desc')
        ->paginate(10);
    return view('pages.category.category_products', compact('category', 'purchases'));
}
public function destroy(Request $request, $id)
{
    try {
        $category = Category::findOrFail($id);

        // Check which delete option was chosen
        if ($request->delete_option === 'with_purchases') {
            // Delete category + related purchases
            $category->purchases()->delete();
            $category->delete();
            $message = 'category and all related purchases deleted successfully!';
        } else {
            // Delete only category
            $category->delete();
            $message = 'category deleted successfully (purchases kept).';
        }

        return redirect()->route('category.index')->with('success', $message);

    } catch (\Exception $e) {
        return redirect()->route('category.index')->with('error', 'Something went wrong. Please try again!');
    }
}

}
