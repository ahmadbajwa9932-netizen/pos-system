<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;

class ExpenseController extends Controller
{
    // Page loads instantly - NO database queries
    public function index()
    {
        return view('pages.expense.expense');
    }

    // AJAX endpoint - returns data only
    public function getData(Request $request)
    {
        $page = $request->get('page', 1);
        
        $expenses = Expense::orderBy('date', 'desc')->paginate(10);
        
        // Return JSON for AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $expenses->items(),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page' => $expenses->lastPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                    'first_item' => $expenses->firstItem(),
                    'last_item' => $expenses->lastItem(),
                    'has_more_pages' => $expenses->hasMorePages(),
                    'on_first_page' => $expenses->onFirstPage(),
                ]
            ]);
        }
        
        // Fallback for non-AJAX requests
        return view('pages.expense.expense', compact('expenses'));
    }

    public function create()
    {
        return view('pages.expense.add');
    }

    // Store single expense
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'amount'   => 'required|numeric|min:0',
            'date'     => 'required|date',
        ]);

        Expense::create($request->only(['category', 'description', 'amount', 'date']));

        return redirect()->route('expenses.index')->with('success', 'Expense added successfully!');
    }

    // Store multiple expenses
    public function storeMultiple(Request $request)
    {
        $request->validate([
            'expenses' => 'required|array|min:1',
            'expenses.*.category' => 'required|string',
            'expenses.*.amount'   => 'required|numeric|min:0',
            'expenses.*.date'     => 'required|date',
        ]);

        foreach ($request->expenses as $expenseData) {
            Expense::create($expenseData);
        }

        return redirect()->route('expenses.index')->with('success', 'Multiple expenses added successfully!');
    }

    public function destroy($id)
    {
        try {
            $expense = Expense::findOrFail($id);
            $expense->delete();
            
            // Return JSON if AJAX request
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense deleted successfully!'
                ]);
            }
            
            return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully!');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again!'
                ], 500);
            }
            
            return redirect()->route('expenses.index')->with('error', 'Something went wrong. Please try again!');
        }
    }
}