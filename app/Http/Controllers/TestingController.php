<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestingController extends Controller
{
public function index(){
    return view('pages.backup.backup');
}

public function deleteAll()
{
    // Disable foreign key checks (important for truncate)
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // Get all tables
    $tables = DB::select('SHOW TABLES');
    $dbName = env('DB_DATABASE');
    $column = "Tables_in_{$dbName}";

    foreach ($tables as $table) {
        $tableName = $table->$column;

        if (in_array($tableName, ['migrations', 'users'])) {
            continue; // skip migrations and users
        }


        // Special case: categories table → delete everything except 'Misc'
        if ($tableName === 'categories') {
            DB::table('categories')->where('name', '!=', 'Misc')->delete();
            continue;
        }

        // Truncate other tables
        DB::table($tableName)->truncate();
    }

    // Enable foreign key checks back
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    return redirect()->back()->with('success', 'All records deleted successfully except the Misc category!');
}
}
