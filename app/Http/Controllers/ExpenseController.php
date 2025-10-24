<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::orderBy('date', 'desc')->paginate(10);
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

    public function destroy($id){
        try{
        $expense=Expense::findOrFail($id);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('expenses.index')->with('error', 'Something went wrong. Please try again!');
        }
    }
}
