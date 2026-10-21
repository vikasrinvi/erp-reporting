<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'ERP System')</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    @stack('styles')

    <style>
        body {
            background: #f5f7fb;
            color: #1f2937;
        }

        .navbar-erp {
            background: linear-gradient(135deg, #172033, #273b63);
            min-height: 68px;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: .3px;
        }

        .navbar-brand small {
            display: block;
            font-size: 10px;
            font-weight: 400;
            opacity: .7;
            letter-spacing: 1px;
        }

        .navbar-erp .nav-link {
            color: rgba(255,255,255,.82);
            padding: 10px 14px;
            border-radius: 8px;
        }

        .navbar-erp .nav-link:hover,
        .navbar-erp .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
        }

        .navbar-erp .dropdown-menu {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }

        .navbar-erp .dropdown-item {
            padding: 9px 15px;
        }

        .navbar-erp .dropdown-item:hover {
            background: #f1f5f9;
        }

        .erp-user {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .erp-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
        }

        .erp-main {
            min-height: calc(100vh - 68px);
        }

        .alert {
            border: 0;
            border-radius: 10px;
        }

        .table {
            vertical-align: middle;
        }

        .pagination {
            margin-bottom: 0;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-erp sticky-top">
    <div class="container-fluid px-4">

        <a class="navbar-brand" href="{{ route('dashboard') }}">
            ERP System
            <small>Enterprise Resource Planning</small>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#erpNavbar"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="erpNavbar">

            @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">

                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}"
                        >
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                        >
                            <i class="bi bi-file-earmark-bar-graph me-1"></i>
                            Reports
                        </a>

                        <ul class="dropdown-menu">
                            
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('reports.library') }}"
                                >
                                    <i class="bi bi-collection me-2"></i>
                                    Report Library
                                </a>
                            </li>
                            <li>
                            <a
                                href="{{ route('report-queries.index') }}"
                                class="dropdown-item"
                            >
                                <i class="bi bi-database-gear me-2"></i>
                                Saved Queries
                            </a>
                            </li>

                            <li>
                                <a
                                    class="dropdown-item"
                                    href="{{ route('reports.asv') }}"
                                >
                                    <i class="bi bi-bar-chart-line me-2"></i>
                                    ASV Item Issue
                                </a>
                            </li>

                        </ul>
                    </li>

                    <!-- <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('inventory') ? 'active' : '' }}"
                            href="{{ route('inventory') }}"
                        >
                            <i class="bi bi-box-seam me-1"></i>
                            Inventory
                        </a>
                    </li> -->

                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('machine-maintenance.index') ? 'active' : '' }}"
                            href="{{ route('machine-maintenance.index') }}"
                        >
                            <i class="bi bi-database me-1"></i>
                            Machine Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('maintenance-tickets.index') ? 'active' : '' }}"
                            href="{{ route('maintenance-tickets.index') }}"
                        >
                            <i class="bi bi-tools me-1"></i>
                            Maintenance Tickets
                        </a>
                    </li>

                </ul>

                <ul class="navbar-nav">

                    <li class="nav-item dropdown">

                        <a
                            class="nav-link dropdown-toggle erp-user"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                        >

                            <span class="erp-avatar">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>

                            <span class="d-none d-lg-inline">
                                {{ auth()->user()->name ?? 'User' }}
                            </span>

                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <h6 class="dropdown-header">
                                    {{ auth()->user()->email ?? '' }}
                                </h6>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-person me-2"></i>
                                    Profile
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-gear me-2"></i>
                                    Settings
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <button
                                        type="submit"
                                        class="dropdown-item text-danger"
                                    >
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Logout
                                    </button>
                                </form>
                            </li>

                        </ul>

                    </li>

                </ul>

            @else

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="{{ route('login') }}"
                        >
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            Login
                        </a>
                    </li>
                </ul>

            @endauth

        </div>
    </div>
</nav>


<main class="erp-main">

    @if(session('success'))
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container-fluid px-4 pt-3">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @yield('content')

</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

@stack('scripts')

</body>
</html>