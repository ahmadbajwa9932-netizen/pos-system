<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'POS Auth')</title>
    <link rel="stylesheet" href="{{ asset('css/auth/auth.css') }}">
</head>
<body>
    <header class="auth-header">
        <div class="auth-header-inner">
            <div class="brand">
                <span class="brand-title">POS System</span>
                <small class="brand-sub">Professional Point of Sale</small>
            </div>
        </div>
    </header>

    <main class="auth-main">
        @yield('content')
    </main>

    <footer class="auth-footer">
        <small>© {{ date('Y') }} POS System</small>
    </footer>

    <script>
        // optional: simple script for toggling password visibility, etc.
        document.addEventListener('click', function(e){
            if (e.target.matches('.toggle-pass')) {
                const input = document.querySelector(e.target.dataset.target);
                if (input) {
                    input.type = input.type === 'password' ? 'text' : 'password';
                    e.target.textContent = input.type === 'password' ? 'Show' : 'Hide';
                }
            }
        });
    </script>
</body>
</html>
