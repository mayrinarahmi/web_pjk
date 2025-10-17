<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SILAPAT - BPKPAD Banjarmasin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/silapat-favicon.png') }}" />
    <!-- <link rel="icon" type="image/x-icon" href="{{ asset('sneat-template/assets/img/favicon/favicon.ico') }}" /> -->

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/css/demo.css') }}" />

    <style>
        .auth-logo {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #566a7f;
        }
        
        .city-logo {
            height: 80px;
            margin-right: 20px;
        }
        
        .pkpad-logo {
            height: 60px;
        }
        
        .auth-card {
            max-width: 450px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .auth-background {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f9;
            padding: 2rem 0;
        }
        
        .btn-login {
            background-color: #696cff;
            color: white;
            font-weight: 500;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease-in-out;
            width: 100%;
        }
        
        .btn-login:hover {
            background-color: #5f62e6;
            box-shadow: 0 0.125rem 0.25rem rgba(105, 108, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="auth-background">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('sneat-template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/bootstrap.js') }}"></script>
    
    <!-- Page Scripts -->
    @stack('scripts')
</body>
</html>