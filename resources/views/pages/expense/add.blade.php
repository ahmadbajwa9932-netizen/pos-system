@extends('layouts.app')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/expense/create-form.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/sub_container.css') }}">
<link rel="stylesheet" href="{{ asset('css/components/popup.css') }}">
@endpush

@section('content')
<div class="container">
<div class="container-child main-text">
        <h1>Manage Expenses</h1>
    </div>
    <div class="container-child sub-text">
        <p>Add your business expenses here</p>
    </div>

    <div class="sub-container">

        <!-- Mode Selection -->
        <div class="entry-mode">
            <label><strong>Entry Mode:</strong></label>
            <select id="entryMode" onchange="toggleExpenseForm()">
                <option value="single">Single Entry</option>
                <option value="multiple">Multiple Entries</option>
            </select>
        </div>

        <div class="expense-form-wrapper">
            <!-- ✅ Single Expense Entry Form (unchanged styling) -->
            <div id="singleExpenseForm">
                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    <div class="category-amount">
                    <div class="input-group-select">
                        <span class="input-group-text">Category</span>
                        <select name="category">
                            <option value="">--select--</option>
                            <option value="Rent">Rent</option>
                            <option value="Bills">Bills</option>
                            <option value="Salaries">Salaries</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                                        <div class="input-group">
                                            <span class="input-group-text">Amount</span>
                                            <input type="number" name="amount" placeholder="Enter amount">
                                        </div>
                                        </div>
                    <div class="input-group">
                        <span class="input-group-text">Description</span>
                        <input type="text" name="description" placeholder="e.g. Electricity Bill">
                    </div>

                    <div class="input-group">
                        <span class="input-group-text">Date</span>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}">
                    </div>

                    <button type="submit" class="btn-primary">Save Expense</button>
                </form>
            </div>

            <!-- ✅ Multiple Expense Entry Form (table style) -->
            <div id="multipleExpenseForm" style="display:none;">
                <form action="{{ route('expenses.storeMultiple') }}" method="POST">
                    @csrf
                    <table id="expenseTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="expenses[0][category]" required>
                                        <option value="Rent">Rent</option>
                                        <option value="Bills">Bills</option>
                                        <option value="Salaries">Salaries</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </td>
                                <td><input type="text" name="expenses[0][description]"></td>
                                <td><input type="number" name="expenses[0][amount]" required></td>
                                <td><input type="date" name="expenses[0][date]" value="{{ date('Y-m-d') }}" required></td>
                                <td><button type="button" class="btn-danger" onclick="removeRow(this)">X</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn-success" onclick="addRow()">+ Add Row</button>
                    <button type="submit" class="btn-primary">Save All Expenses</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleExpenseForm() {
        let mode = document.getElementById("entryMode").value;
        document.getElementById("singleExpenseForm").style.display = (mode === "single") ? "block" : "none";
        document.getElementById("multipleExpenseForm").style.display = (mode === "multiple") ? "block" : "none";
    }

    let rowIndex = 1;
    function addRow() {
        let table = document.getElementById("expenseTable").getElementsByTagName("tbody")[0];
        let newRow = table.insertRow();
        newRow.innerHTML = `
            <td>
                <select name="expenses[${rowIndex}][category]" required>
                    <option value="Rent">Rent</option>
                    <option value="Bills">Bills</option>
                    <option value="Salaries">Salaries</option>
                    <option value="Other">Other</option>
                </select>
            </td>
            <td><input type="text" name="expenses[${rowIndex}][description]"></td>
            <td><input type="number" name="expenses[${rowIndex}][amount]" required></td>
            <td><input type="date" name="expenses[${rowIndex}][date]" value="{{ date('Y-m-d') }}" required></td>
            <td><button type="button" class="btn-danger" onclick="removeRow(this)">X</button></td>
        `;
        rowIndex++;
    }

    function removeRow(button) {
        button.closest("tr").remove();
    }
</script>
@endsection
