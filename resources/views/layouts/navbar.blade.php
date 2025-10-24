<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar Fix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }  

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #fff;
            border-bottom: 2px solid #ddd;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 1000;
            transition: left 0.3s ease-in-out;
            height: 60px;
        }

        .navbar.sidebar-collapsed {
            left: 0;
        }

        .navbar div {
            display: flex;
            align-items: center;
        }

        .navbar a, .navbar i {
            text-decoration: none;
            color: #35302d;
            font-weight: bold;
            margin-right: 15px;
            font-size: 14px;
            cursor: pointer;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .search-box input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            width: 200px;
            transition: 0.3s;
        }

        .search-box input:focus {
            border-color: #5a2c8a;
        }

        .user-icons {
            display: flex;
            gap: 15px;
            position: relative;
        }

        .user-icons i {
            font-size: 20px;
            color: #333;
            cursor: pointer;
            position: relative;
        }

        .user-icons i::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.75);
            color: white;
            font-size: 8px;
            padding: 5px;
            border-radius: 5px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
            text-transform: lowercase; 
        }

        .user-icons i:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .main-content {
            margin-top: 40px;
            padding-top: 20px;
        }

        .notification-wrapper {
            position: relative;
        }

        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            display: none;
        }

        .notification-dropdown {
            position: absolute;
            top: 30px;
            right: 0;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 999;
        }

        .notification-dropdown.show {
            visibility: visible;
            opacity: 1;
        }

        .notification-dropdown h4 {
            background: #f5f5f5;
            margin: 0;
            padding: 10px;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
        }

        .notification-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .notification-dropdown li {
            padding: 0;
            border-bottom: 1px solid #eee;
        }

        .notification-dropdown li.overdue {
            background: #ffe5e5;
        }

        .notification-dropdown li.upcoming {
            background: #e5f7ff;
        }

        .notification-dropdown li:last-child {
            border-bottom: none;
        }

        .notification-card {
            padding: 10px;
            font-size: 13px;
            background: #fff;
            border-radius: 8px;
            margin: 8px;
            border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 8px;
            width: 100%;
        }

        .notif-name {
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .overdue .notif-status {
            background: #e74c3c;
            padding: 3px;
            border-radius: 3px;
            color: white;
        }

        .upcoming .notif-status {
            background: #3498db;
            padding: 3px;
            border-radius: 3px;
            color: white;
        }

        .notif-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
        }

        .notif-details p {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }

        .notif-details p b {
            display: flex;
            justify-content: center;
            font-size: 11px;
        }

        .notif-details i {
            color: #888;
            margin-right: 4px;
        }

        .user-menu-wrapper {
            position: relative;
        }

        .user-dropdown {
            position: absolute;
            top: 30px;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 200px;
            padding: 10px 0;
            display: none;
            z-index: 999;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown h4 {
            text-align: center;
            font-size: 14px;
            margin: 0 0 8px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .user-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .user-dropdown li {
            text-align: center;
            margin: 5px 0;
        }

        .user-dropdown a,
        .user-dropdown button {
            display: block;
            width: 100%;
            padding: 8px 0;
            text-decoration: none;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            font-size: 13px;
            font-family: Arial, sans-serif;
        }

        .user-dropdown a:hover,
        .user-dropdown button:hover {
            background: #f5f5f5;
            color: #5a2c8a;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <i class="fa-solid fa-bars menu-icon" onclick="toggleSidebar()"></i>
        <a href="#">Home</a>
        <a href="#">Profile</a>
        <a href="#">Dashboard</a>
    </div>
    
    <div class="search-box">
        <input type="text" placeholder="Search for a page...">
    </div>
    
    <div class="user-icons">
        <div class="notification-wrapper">
            <i class="fa-solid fa-bell" id="notificationIcon" data-tooltip="Notifications"></i>
            <span class="notification-count" id="notificationCount">0</span>
            <div class="notification-dropdown" id="notificationDropdown">
                <h4>Notifications</h4>
                <ul id="notificationList"></ul>
            </div>
        </div>
        
        <div class="user-menu-wrapper">
            <i class="fa-solid fa-user" id="userIcon" data-tooltip="User"></i>
            
            <!-- <div class="user-dropdown" id="userDropdown"> -->
                <!-- <h4>👋 zohaib</h4>
                <ul>
                    <li><a href="#">Profile</a></li>
                    <li><a href="#">Reset Password</a></li>
                    <li>
                        <button type="button" onclick="alert('Logout clicked')">Logout</button>
                    </li>
                </ul> -->
            <!-- </div> -->
        </div>
        
        <i class="fa-solid fa-cog" data-tooltip="Settings"></i>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector(".pos-sidebar-wrapper .sidebar");
    const mainContent = document.querySelector(".main-content");
    const navbar = document.querySelector(".navbar");
    
    document.documentElement.classList.remove('sidebar-initially-collapsed');
    
    if (sidebar) {
        sidebar.style.transition = 'width 0.3s ease-in-out';
        sidebar.classList.toggle("collapsed");
    }
    if (mainContent) {
        mainContent.style.transition = 'margin-left 0.3s ease-in-out, width 0.3s ease-in-out';
        mainContent.classList.toggle("sidebar-collapsed");
    }
    if (navbar) {
        navbar.style.transition = 'left 0.3s ease-in-out';
        navbar.classList.toggle("sidebar-collapsed");
    }
    
    const isCollapsed = sidebar ? sidebar.classList.contains("collapsed") : false;
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

function restoreSidebarState() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed) {
        const sidebar = document.querySelector(".pos-sidebar-wrapper .sidebar");
        const mainContent = document.querySelector(".main-content");
        const navbar = document.querySelector(".navbar");
        
        if (sidebar) sidebar.classList.add("collapsed");
        if (mainContent) mainContent.classList.add("sidebar-collapsed");
        if (navbar) navbar.classList.add("sidebar-collapsed");
    }
    
    setTimeout(() => {
        document.documentElement.classList.remove('sidebar-initially-collapsed');
        
        const sidebar = document.querySelector(".pos-sidebar-wrapper .sidebar");
        const mainContent = document.querySelector(".main-content");
        const navbar = document.querySelector(".navbar");
        
        if (sidebar) sidebar.style.transition = 'width 0.3s ease-in-out';
        if (mainContent) mainContent.style.transition = 'margin-left 0.3s ease-in-out, width 0.3s ease-in-out';
        if (navbar) navbar.style.transition = 'left 0.3s ease-in-out';
    }, 100);
}

document.addEventListener('DOMContentLoaded', function () {
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    const userIcon = document.getElementById('userIcon');
    const userDropdown = document.getElementById('userDropdown');

    // Check if elements exist before adding listeners
    if (!userDropdown) {
        console.error('User dropdown element not found!');
        return;
    }

    // Toggle notification dropdown
    notificationIcon.addEventListener('click', function (event) {
        event.stopPropagation();
        const isNotifOpen = notificationDropdown.classList.contains('show');
        
        // Close user dropdown
        userDropdown.classList.remove('show');
        
        // Toggle notification
        if (isNotifOpen) {
            notificationDropdown.classList.remove('show');
        } else {
            notificationDropdown.classList.add('show');
            fetchNotifications();
        }
    });

    // Toggle user dropdown
    userIcon.addEventListener('click', function (event) {
        event.stopPropagation();
        const isUserOpen = userDropdown.classList.contains('show');
        
        // Close notification dropdown
        notificationDropdown.classList.remove('show');
        
        // Toggle user dropdown
        if (isUserOpen) {
            userDropdown.classList.remove('show');
        } else {
            userDropdown.classList.add('show');
        }
    });

    // Close both dropdowns when clicking anywhere else
    document.addEventListener('click', function (event) {
        if (!userDropdown.contains(event.target) && !userIcon.contains(event.target)) {
            userDropdown.classList.remove('show');
        }
        if (!notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    // Fetch notifications function
    function fetchNotifications() {
        fetch('/api/credit-sales-notifications')
            .then(response => response.json())
            .then(data => {
                notificationList.innerHTML = '';
                let count = data.length;

                if (count > 0) {
                    notificationCount.style.display = 'inline-block';
                    notificationCount.textContent = count;
                } else {
                    notificationCount.style.display = 'none';
                }

                data.forEach(item => {
                    let li = document.createElement('li');
                    let days = item.days_difference;

                    let statusText = '';
                    if (days < 0) {
                        statusText = `Overdue by ${Math.abs(days)} day(s)`;
                        li.classList.add('overdue');
                    } else if (days === 0) {
                        statusText = `Due Today`;
                        li.classList.add('overdue');
                    } else {
                        statusText = `Due in ${days} day(s)`;
                        li.classList.add('upcoming');
                    }

                    li.innerHTML = `
                        <div class="notification-card">
                            <div class="notif-header">
                                <span class="notif-name"><i class="fa-solid fa-user"></i> ${item.customer_name}</span>
                                <span class="notif-status">${statusText}</span>
                            </div>
                            <div class="notif-details">
                                <p>
                                    <b><i class="fa-solid fa-city"></i>City</b>
                                    <b><i class="fa-solid fa-phone"></i>Contact</b>
                                    <b><i class="fa-solid fa-coins"></i>Amount</b>
                                    <b><i class="fa-solid fa-calendar-day"></i>Due Date</b>
                                </p>
                                <p>
                                    <span>${item.city || 'N/A'}</span>
                                    <span>${item.contact || 'N/A'}</span>
                                    <span>Rs ${parseFloat(item.remaining_balance).toLocaleString()}</span>
                                    <span>${item.due_date}</span>
                                </p>
                            </div>
                        </div>
                    `;
                    notificationList.appendChild(li);
                });
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Initial fetch and periodic updates
    fetchNotifications();
    setInterval(fetchNotifications, 30000);
});

restoreSidebarState();
</script>

</body>
</html>