<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collapsible Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .pos-sidebar-wrapper * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .pos-sidebar-wrapper .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #1b2a41;
            padding-top: 0;
            position: fixed;
            top: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #314b78 transparent;
            transition: width 0.3s ease-in-out;
            display: block;
            z-index: 1001;
        }

        .pos-sidebar-wrapper .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .pos-sidebar-wrapper .sidebar::-webkit-scrollbar-thumb {
            background-color: #314b78;
            border-radius: 10px;
        }

        .pos-sidebar-wrapper .sidebar h2 {
            color: #00aaff;
            text-align: center;
            font-size: 18px;
            margin: 25px 0 25px 0;
        }

        .pos-sidebar-wrapper .menu-item {
            list-style: none;
            padding: 12px 15px;
            color: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease-in-out;
        }

        .pos-sidebar-wrapper .menu-item i {
            color: white;
        }

        .pos-sidebar-wrapper .menu-item:hover {
            background-color: #243b55;
        }

        .pos-sidebar-wrapper .submenu {
            max-height: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            background-color: #16223a;
        }

        .pos-sidebar-wrapper .submenu li {
            margin-left: 10px;
        }

        .pos-sidebar-wrapper .submenu li:first-child {
            margin-top: 5px;
        }

        .pos-sidebar-wrapper .submenu li:last-child {
            margin-bottom: 25px;
        }

        .pos-sidebar-wrapper .submenu a {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .pos-sidebar-wrapper .submenu a:hover {
            background-color: #314b78;
        }

        .pos-sidebar-wrapper .icon {
            font-size: 14px;
            background-color: #1e51aa;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .pos-sidebar-wrapper .rotate {
            transform: rotate(180deg);
        }

        .pos-sidebar-wrapper .sidebar.collapsed {
            width: 0;
            padding: 0;
            overflow: hidden;
            transition: width 0.3s ease-in-out;
        }

        .pos-sidebar-wrapper .sidebar.collapsed h2 {
            display: none;
        }

        .pos-sidebar-wrapper .sidebar.collapsed .menu-item {
            justify-content: center;
            padding: 12px;
        }

        .pos-sidebar-wrapper .sidebar.collapsed .menu-item span {
            display: none;
        }

        .pos-sidebar-wrapper .sidebar.collapsed .submenu {
            display: none;
        }

         .main-content {
            margin-left: 250px;
            width: calc(100% - 230px);
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
        }

         .main-content.sidebar-collapsed {
            margin-left: 0;
            width: 100%;
        }

        .pos-sidebar-wrapper .without-menu-item {
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
<div class="pos-sidebar-wrapper">
    <div class="sidebar">
        <h2 style="margin-bottom: 11px">POS System</h2>
        <hr>
        <ul>
        <a class="without-menu-item" href="{{route('revenue.index')}}">
            <li class="menu-item"><span><i class="fa-solid fa-gauge"></i> Dashboard</span></li>
    </a>
            <li class="menu-item"><span><i class="fa fa-home"></i> Home</span></li>
            <a class="without-menu-item" href="{{route('sales.create')}}">
                <li class="menu-item"><span><i class="fa fa-print"></i> Make a sale</span></li>
            </a>

            <li class="menu-item" onclick="toggleMenu('produce-menu', this)">
                <span><i class="fa fa-shopping-cart"></i> Purchase</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="produce-menu">
                <li><a href="{{route('purchase.add')}}">Add</a></li>
                <li><a href="{{route('supplier.index')}}">Supply</a></li>
                <li><a href="{{route('purchase.lowInventory')}}">Low Inventory</a></li>
                <li><a href="{{route('purchase.index')}}">To See</a></li>
            </ul>

            <li class="menu-item" onclick="toggleMenu('sales-menu', this)">
                <span><i class="fa fa-money-bill"></i> Sales</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="sales-menu">
                <li><a href="{{route('sales.daily')}}">Daily Sale</a></li>
                <li><a href="{{route('sales.general')}}">General Sale</a></li>
                <li><a href="{{route('sales.credit')}}">Credit Sale</a></li>
                <li><a href="{{route('sales.returns.index')}}">Returned Sale</a></li>
                <!-- <li><a href="{{route('revenue.index')}}">Revenue</a></li> -->
            </ul>

            <li class="menu-item" onclick="toggleMenu('category-menu', this)">
                <span><i class="fa fa-book"></i> Categories</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="category-menu">
                <li><a href="{{route('category.add')}}">Add</a></li>
                <li><a href="{{route('category.index')}}">To See</a></li>
            </ul>

            <li class="menu-item" onclick="toggleMenu('expense-menu', this)">
                <span><i class="fa fa-file-invoice-dollar"></i> Expense</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="expense-menu">
                <li><a href="{{route('expenses.create')}}">Add</a></li>
                <li><a href="{{route('expenses.index')}}">To See</a></li>
            </ul>

            <li class="menu-item" onclick="toggleMenu('report-menu', this)">
                <span><i class="fa fa-chart-bar"></i> Reports</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="report-menu">
                <li><a href="{{route('reports.sales')}}">Sales Report</a></li>
                <li><a href="{{route('reports.financial.index')}}">Financial Report</a></li>
                <li><a href="{{route('reports.purchase.index')}}">Purchase Report</a></li>
            </ul>
            
            <li class="menu-item" onclick="toggleMenu('customer-menu', this)">
                <span><i class="fa fa-users"></i> Customers</span>
                <span class="icon">+</span>
            </li>
            <ul class="submenu" id="customer-menu">
                <li><a href="{{route('customers.index')}}">Customers</a></li>
                <li><a href="{{route('customers.credit.index')}}">Credit Customers</a></li>
            </ul>

            <li class="menu-item"><span><i class="fa fa-user"></i> Users</span></li>
            <a class="without-menu-item" href="{{route('show.delete.all')}}">
            <li class="menu-item"><span><i class="fa fa-database"></i> Backup</span></li>
            </a>
        </ul>
    </div>
</div>

<script>
    function toggleMenu(menuId, element) {
        let menu = document.getElementById(menuId);
        let icon = element.querySelector(".icon");

        if (menu.style.maxHeight) {
            menu.style.maxHeight = null;
            menu.style.padding = "0";
            icon.innerText = "+";
            icon.classList.remove("rotate");
        } else {
            menu.style.maxHeight = menu.scrollHeight + "px";
            menu.style.padding = "10px 0";
            icon.innerText = "-";
            icon.classList.add("rotate");
        }
    }
</script>
</body>
</html>
