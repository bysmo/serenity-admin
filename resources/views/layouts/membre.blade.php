<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $appNomComplet = \App\Models\AppSetting::get('app_nom', 'Gestion de la serénité financiere');
        $logoPath = \App\Models\AppSetting::get('entreprise_logo');
        $faviconUrl = null;
        
        if ($logoPath) {
            $logoFullPath = storage_path('app/public/' . $logoPath);
            $publicStorageExists = \Illuminate\Support\Facades\File::exists(public_path('storage'));
            
            if ($publicStorageExists && \Illuminate\Support\Facades\File::exists($logoFullPath)) {
                $faviconUrl = asset('storage/' . $logoPath);
            } else {
                $filename = basename($logoPath);
                $faviconUrl = route('storage.logo', ['filename' => $filename]);
            }
        }
    @endphp
    
    @if($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
    
    <title>{{ $appNomComplet }} - @yield('title', 'Mon Espace')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts - Ubuntu Light -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-dark-blue: #1e3a5f;
            --primary-blue: #2c5282;
            --light-blue: #4299e1;
            --sidebar-width: 260px;
        }
        
        * {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-dark-blue);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            color: white;
            font-weight: 300;
            margin: 0;
            font-size: 0.85rem;
            font-family: 'Ubuntu', sans-serif;
        }
        
        .sidebar-menu {
            padding: 0;
            margin: 0;
        }
        .sidebar-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu-list > li {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .sidebar-menu-list .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 300;
            font-size: 0.75rem;
            text-decoration: none;
        }
        .sidebar-menu-list .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--light-blue);
        }
        .sidebar-menu-list .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
            font-weight: 400;
        }
        .sidebar-menu-list .nav-link i {
            font-size: 0.85rem;
            width: 18px;
            text-align: center;
        }
        .sidebar-nav-toggle .sidebar-chevron {
            font-size: 0.7rem;
            margin-left: auto;
            transition: transform 0.2s ease;
        }
        .sidebar-nav-toggle:not(.collapsed) .sidebar-chevron {
            transform: rotate(180deg);
        }
        .sidebar-submenu-wrap {
            margin: 0;
            padding: 0;
        }
        .sidebar-submenu {
            list-style: none;
            padding: 0 0 0.25rem 0;
            margin: 0 0 0 1.25rem;
            border-left: 1px solid rgba(255,255,255,0.15);
            padding-left: 0.5rem;
        }
        .sidebar-submenu li {
            margin: 0;
            padding: 0;
        }
        .sidebar-submenu .nav-link {
            position: relative;
            padding: 0.35rem 0.75rem 0.35rem 1rem;
            font-size: 0.7rem;
            border-left: none !important;
        }
        .sidebar-submenu .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: white;
        }
        .sidebar-submenu .nav-link i {
            width: 16px;
            font-size: 0.75rem;
        }
        
        .top-bar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: 50px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
        }
        
        .notifications-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--primary-dark-blue);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.4rem 0.6rem;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        .notifications-btn:hover {
            background-color: rgba(30, 58, 95, 0.1);
        }
        .notifications-badge {
            position: absolute;
            top: 0.2rem;
            right: 0.3rem;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            min-width: 1.1rem;
            height: 1.1rem;
            font-size: 0.65rem;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0 0.25rem;
            font-weight: 500;
        }
        .notifications-badge:not(:empty) {
            display: flex;
        }
        .notifications-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 320px;
            max-width: 400px;
            max-height: 500px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .notifications-dropdown.show {
            display: block;
        }
        .notifications-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-dark-blue);
            color: white;
        }
        .notifications-header h6 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
        }
        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: 'Ubuntu', sans-serif;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #e7f3ff;
        }
        .notification-item.unread:hover {
            background-color: #d0e7ff;
        }
        .notification-title {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--primary-dark-blue);
            margin-bottom: 0.25rem;
        }
        .notification-message {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .notification-time {
            font-size: 0.7rem;
            color: #adb5bd;
        }
        .notifications-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.8rem;
            font-family: 'Ubuntu', sans-serif;
        }
        .notifications-footer {
            padding: 0.5rem 1rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        .notifications-footer a {
            font-size: 0.75rem;
            color: var(--primary-dark-blue);
            text-decoration: none;
            font-family: 'Ubuntu', sans-serif;
        }
        .notifications-footer a:hover {
            text-decoration: underline;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: var(--primary-dark-blue);
            font-size: 0.8rem;
            cursor: pointer;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            transition: background-color 0.2s ease;
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout-btn:hover {
            background-color: rgba(30, 58, 95, 0.1);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 50px;
            padding: 1.5rem;
            min-height: calc(100vh - 50px);
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h1 {
            color: var(--primary-dark-blue);
            font-weight: 300;
            font-size: 1.5rem;
            margin: 0;
            font-family: 'Ubuntu', sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: var(--primary-dark-blue);
            color: white;
            border-radius: 6px 6px 0 0 !important;
            padding: 0.75rem 1rem;
            font-weight: 300;
            font-size: 0.85rem;
            font-family: 'Ubuntu', sans-serif;
        }
        
        .card-body {
            padding: 1rem;
            font-size: 0.85rem;
        }
        
        .table {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.8rem;
        }
        
        .table thead th {
            background-color: var(--primary-dark-blue);
            color: white;
            font-weight: 300;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .table tbody td {
            padding: 0.5rem 0.75rem;
            vertical-align: middle;
        }
        
        /* Styles pour la pagination personnalisée */
        .pagination-custom {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        .pagination-custom .pagination {
            margin-bottom: 0;
            font-size: 0.75rem;
        }
        
        .pagination-custom .page-link {
            color: white;
            background-color: var(--primary-dark-blue);
            border-color: var(--primary-dark-blue);
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            line-height: 1.3;
        }
        
        .pagination-custom .page-link:hover {
            color: white;
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        .pagination-custom .page-item.active .page-link {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }
        
        .pagination-custom .page-item.disabled .page-link {
            background-color: rgba(30, 58, 95, 0.5);
            border-color: rgba(30, 58, 95, 0.5);
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Styles pour les boutons */
        .btn {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            line-height: 1.3;
        }
        
        .btn-sm {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            line-height: 1.2;
        }
        
        .btn-lg {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-dark-blue);
            border-color: var(--primary-dark-blue);
            font-weight: 300;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        .btn i {
            font-size: 0.8rem;
        }
        
        .btn-sm i {
            font-size: 0.75rem;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    @php
        $membre = auth('membre')->user();
        $appNom = \App\Models\AppSetting::get('nom_app', 'Serenity');
    @endphp
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-cash-coin"></i>  {{ $appNom }}</h4>
        </div>
        <nav class="sidebar-menu">
            <ul class="sidebar-menu-list">
                <li>
                    <a href="{{ route('membre.dashboard') }}" class="nav-link {{ request()->routeIs('membre.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    @php
                        $cagnettesRoutesActive = request()->routeIs('membre.cotisations*') || request()->routeIs('membre.mes-cotisations*');
                    @endphp
                    <a href="#" class="nav-link sidebar-nav-toggle {{ $cagnettesRoutesActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#cagnettesSubmenu" aria-expanded="{{ $cagnettesRoutesActive ? 'true' : 'false' }}">
                        <i class="bi bi-receipt-cutoff"></i>
                        <span>Cagnottes</span>
                        <i class="bi bi-chevron-down sidebar-chevron"></i>
                    </a>
                    <div class="collapse sidebar-submenu-wrap {{ $cagnettesRoutesActive ? 'show' : '' }}" id="cagnettesSubmenu">
                        <ul class="sidebar-submenu">
                            <li>
                                <a href="{{ route('membre.cotisations.publiques') }}" class="nav-link {{ request()->routeIs('membre.cotisations.publiques') ? 'active' : '' }}">
                                    <i class="bi bi-globe"></i>
                                    <span>Cagnottes publiques</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.cotisations.privees') }}" class="nav-link {{ request()->routeIs('membre.cotisations.privees') ? 'active' : '' }}">
                                    <i class="bi bi-lock"></i>
                                    <span>Cagnottes privées</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.cotisations.rechercher') }}" class="nav-link {{ request()->routeIs('membre.cotisations.rechercher') ? 'active' : '' }}">
                                    <i class="bi bi-search"></i>
                                    <span>Rechercher par code</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.mes-cotisations') }}" class="nav-link {{ request()->routeIs('membre.mes-cotisations*') ? 'active' : '' }}">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Mes cagnottes créées</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                  <li>
                    <a href="{{ route('membre.nano-credits') }}" class="nav-link {{ request()->routeIs('membre.nano-credits*') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>Nano Crédits</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('membre.epargne.index') }}" class="nav-link {{ request()->routeIs('membre.epargne*') ? 'active' : '' }}">
                        <i class="bi bi-piggy-bank"></i>
                        <span>Tontines</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('membre.paiements') }}" class="nav-link {{ request()->routeIs('membre.paiements') ? 'active' : '' }}">
                        <i class="bi bi-receipt"></i>
                        <span>Mes Paiements</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('membre.engagements') }}" class="nav-link {{ request()->routeIs('membre.engagements') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Mes Engagements</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('membre.remboursements') }}" class="nav-link {{ request()->routeIs('membre.remboursements*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Mes Remboursements</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('membre.kyc.index') }}" class="nav-link {{ request()->routeIs('membre.kyc*') ? 'active' : '' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Mon KYC</span>
                    </a>
                </li>
              
                <li>
                    @php
                        $garantRoutesActive = request()->routeIs('membre.garant*');
                    @endphp
                    <a href="#" class="nav-link sidebar-nav-toggle {{ $garantRoutesActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#garantSubmenu" aria-expanded="{{ $garantRoutesActive ? 'true' : 'false' }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Espace Garant</span>
                        <i class="bi bi-chevron-down sidebar-chevron"></i>
                    </a>
                    <div class="collapse sidebar-submenu-wrap {{ $garantRoutesActive ? 'show' : '' }}" id="garantSubmenu">
                        <ul class="sidebar-submenu">
                            <li>
                                <a href="{{ route('membre.garant.index') }}" class="nav-link {{ request()->routeIs('membre.garant.index') ? 'active' : '' }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Mon Tableau de bord</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.garant.sollicitations') }}" class="nav-link {{ request()->routeIs('membre.garant.sollicitations') ? 'active' : '' }}">
                                    <i class="bi bi-person-plus"></i>
                                    <span>Sollicitations</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.garant.engagements') }}" class="nav-link {{ request()->routeIs('membre.garant.engagements') ? 'active' : '' }}">
                                    <i class="bi bi-journal-check"></i>
                                    <span>Mes Engagements</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @php
                    $parrainageActif = \App\Models\ParrainageConfig::current()->actif;
                    $parrainageRoutesActive = request()->routeIs('membre.parrainage*');
                @endphp
                @if($parrainageActif)
                <li>
                    <a href="#" class="nav-link sidebar-nav-toggle {{ $parrainageRoutesActive ? '' : 'collapsed' }}"
                       data-bs-toggle="collapse" data-bs-target="#parrainageSubmenu"
                       aria-expanded="{{ $parrainageRoutesActive ? 'true' : 'false' }}">
                        <i class="bi bi-people"></i>
                        <span>Parrainage</span>
                        @php
                            $totalDispoParrainage = auth('membre')->user() ? auth('membre')->user()->totalCommissionsDisponibles() : 0;
                        @endphp
                        @if($totalDispoParrainage > 0)
                            <span class="badge bg-success ms-auto" style="font-size:0.6rem;">FCFA</span>
                        @endif
                        <i class="bi bi-chevron-down sidebar-chevron"></i>
                    </a>
                    <div class="collapse sidebar-submenu-wrap {{ $parrainageRoutesActive ? 'show' : '' }}" id="parrainageSubmenu">
                        <ul class="sidebar-submenu">
                            <li>
                                <a href="{{ route('membre.parrainage.index') }}" class="nav-link {{ request()->routeIs('membre.parrainage.index') ? 'active' : '' }}">
                                    <i class="bi bi-house"></i>
                                    <span>Tableau de bord</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.parrainage.filleuls') }}" class="nav-link {{ request()->routeIs('membre.parrainage.filleuls') ? 'active' : '' }}">
                                    <i class="bi bi-person-check"></i>
                                    <span>Mes filleuls</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('membre.parrainage.commissions') }}" class="nav-link {{ request()->routeIs('membre.parrainage.commissions') ? 'active' : '' }}">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Mes commissions</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                <li>
                    <a href="{{ route('membre.profil') }}" class="nav-link {{ request()->routeIs('membre.profil') ? 'active' : '' }}">
                        <i class="bi bi-person-circle"></i>
                        <span>Mes Infos Personnelles</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <span style="font-size: 0.85rem; color: var(--primary-dark-blue); font-weight: 300;">
                <i class="bi bi-person-circle"></i> 
                {{ $membre->nom_complet ?? 'Membre' }}
                <small class="text-muted ms-2">({{ $membre->numero ?? '' }})</small>
            </span>
        </div>
        <div class="top-bar-right">
            <button class="notifications-btn" type="button" title="Notifications" id="notificationsBtn">
                <i class="bi bi-bell"></i>
                <span class="notifications-badge" id="notificationsBadge"></span>
            </button>
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h6><i class="bi bi-bell"></i> Notifications</h6>
                    <button type="button" class="btn btn-sm text-white" id="markAllReadBtn" style="font-size: 0.7rem; padding: 0.2rem 0.5rem; background: transparent; border: 1px solid rgba(255,255,255,0.3);">
                        Tout marquer comme lu
                    </button>
                </div>
                <div class="notifications-list" id="notificationsList">
                    <div class="notifications-empty">Chargement...</div>
                </div>
                <div class="notifications-footer">
                    <a href="{{ route('membre.notifications.index') }}">Voir toutes les notifications</a>
                </div>
            </div>
            <form action="{{ route('membre.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="logout-btn" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>
    
    <!-- Toast Container (en haut à droite) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; margin-top: 50px;">
        <div id="toastContainer"></div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonction pour afficher un toast
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : type === 'warning' ? 'bg-warning' : 'bg-info';
            const icon = type === 'success' ? 'bi-check-circle' : type === 'error' ? 'bi-x-circle' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle';
            
            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif; font-size: 0.875rem;">
                            <i class="bi ${icon} me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            toast.show();
            
            // Supprimer l'élément après la fermeture
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Afficher les messages de session comme toasts
        @if(session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        
        @if(session('error'))
            showToast('{{ session('error') }}', 'error');
        @endif
        
        @if(session('warning'))
            showToast('{{ session('warning') }}', 'warning');
        @endif
        
        @if(session('info'))
            showToast('{{ session('info') }}', 'info');
        @endif

        // Gestion des notifications (cloche)
        (function() {
            const notificationsBtn = document.getElementById('notificationsBtn');
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const notificationsBadge = document.getElementById('notificationsBadge');
            const notificationsList = document.getElementById('notificationsList');
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (!notificationsBtn || !notificationsDropdown) return;

            let notifications = [];
            let unreadCount = 0;

            function loadNotifications() {
                fetch('{{ route('membre.notifications.unread') }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(response => response.json())
                    .then(data => {
                        notifications = data.notifications || [];
                        unreadCount = data.unread_count || 0;
                        updateBadge();
                        renderNotifications();
                    })
                    .catch(function() {
                        notificationsList.innerHTML = '<div class="notifications-empty">Aucune notification</div>';
                    });
            }

            function updateBadge() {
                if (unreadCount > 0) {
                    notificationsBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    notificationsBadge.style.display = 'flex';
                } else {
                    notificationsBadge.textContent = '';
                    notificationsBadge.style.display = 'none';
                }
            }

            function renderNotifications() {
                if (notifications.length === 0) {
                    notificationsList.innerHTML = '<div class="notifications-empty">Aucune notification</div>';
                    return;
                }
                let html = '';
                notifications.forEach(function(notification) {
                    const isUnread = !notification.read_at;
                    const itemClass = isUnread ? 'notification-item unread' : 'notification-item';
                    const title = (notification.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const message = (notification.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    html += '<div class="' + itemClass + '" data-id="' + notification.id + '">';
                    html += '<div class="notification-title">' + title + '</div>';
                    html += '<div class="notification-message">' + message + '</div>';
                    html += '<div class="notification-time">' + (notification.created_at || '') + '</div></div>';
                });
                notificationsList.innerHTML = html;
                notificationsList.querySelectorAll('.notification-item').forEach(function(el) {
                    el.addEventListener('click', function() {
                        const id = el.getAttribute('data-id');
                        fetch('{{ url('membre/notifications') }}/' + id + '/read', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        }).then(function() { loadNotifications(); });
                    });
                });
            }

            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fetch('{{ route('membre.notifications.read-all') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).then(function() { loadNotifications(); });
                });
            }

            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationsDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
                    notificationsDropdown.classList.remove('show');
                }
            });

            loadNotifications();
            setInterval(loadNotifications, 30000);
        })();
    </script>
    
    <script>
        (function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() { alert.remove(); }, 500);
                }, 5000);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
