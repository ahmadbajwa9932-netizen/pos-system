<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <meta name="csrf-token" content="{{ csrf_token() }}"> -->
    <title>@yield('title')</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    @stack('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
// Restore sidebar state immediately to prevent flash
(function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.documentElement.classList.add('sidebar-initially-collapsed');
    }
})();
</script>
    <style>
     /* .layout-content{
        margin-top:3%;
        margin-left:3%;
        margin-right:3%;
        margin-bottom: 3% 
    }  */
    body{
        background-color: #f8f9fa;
    }
    .sidebar-initially-collapsed .pos-sidebar-wrapper .sidebar {
    width: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}

.sidebar-initially-collapsed .main-content {
    margin-left: 0 !important;
    width: 100% !important;
}

.sidebar-initially-collapsed .navbar {
    left: 0 !important;
}

/* Remove the transition temporarily during initial load */
.sidebar-initially-collapsed .pos-sidebar-wrapper .sidebar,
.sidebar-initially-collapsed .main-content,
.sidebar-initially-collapsed .navbar {
    transition: none !important;
}
    </style>
</head>
<body>

    @include('layouts.sidebar')  <!-- Sidebar Component -->
    
    <div class="main-content">
        @include('layouts.navbar')  <!-- Navbar Component -->
        
        <div class="layout-content" id="main-content">
            @yield('content')
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).on('click', 'a.ajax-link', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');

        $('#main-content').html('<p></p>');

        $.get(url, function(data) {
            var newContent = $(data).find('#main-content').html();
            $('#main-content').html(newContent);
            window.history.pushState({}, '', url);
        });
    });
</script>

</body>
</html>
