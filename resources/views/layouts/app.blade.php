<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $appNomComplet = $appNom ?? \App\Models\AppSetting::get('app_nom', 'Gestion des cagnottes');
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
    
    <title>Serenity - @yield('title')</title>
    
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
            padding: 0.5rem 0;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 300;
            font-size: 0.75rem;
        }
        
        .sidebar-menu .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--light-blue);
        }
        
        .sidebar-menu .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
            font-weight: 400;
        }
        
        .sidebar-menu .nav-link i {
            font-size: 0.85rem;
            width: 18px;
            text-align: center;
        }
        
        .sidebar-menu .nav-link.has-submenu {
            justify-content: space-between;
        }
        
        .sidebar-menu .nav-link.has-submenu::after {
            content: '\f282';
            font-family: 'bootstrap-icons';
            font-size: 0.75rem;
            transition: transform 0.3s ease;
        }
        
        .sidebar-menu .nav-link.has-submenu[aria-expanded="true"]::after {
            transform: rotate(90deg);
        }
        
        .sidebar-submenu {
            background-color: rgba(0,0,0,0.2);
            padding: 0;
            list-style: none;
        }
        
        .sidebar-submenu .nav-link {
            padding: 0.4rem 1.25rem 0.4rem 2.5rem;
            font-size: 0.7rem;
        }
        
        .sidebar-submenu .nav-link.active {
            background-color: rgba(255,255,255,0.2);
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
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--primary-dark-blue);
            font-weight: 300;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.25rem;
            font-weight: 500;
        }
        
        .notifications-badge:empty {
            display: none;
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
            background: var(--primary-dark-blue);
            color: white;
            border: none;
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .logout-btn:hover {
            background: var(--primary-blue);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 50px;
            min-height: calc(100vh - 50px);
            padding: 0.75rem;
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        .page-header {
            background: white;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 0.75rem;
        }
        
        .page-header h1 {
            color: var(--primary-dark-blue);
            font-weight: 400;
            margin: 0;
            font-size: 1rem;
            font-family: 'Ubuntu', sans-serif;
            line-height: 1.3;
        }
        
        .page-header h1 i {
            font-size: 0.9rem;
        }
        
        .card {
            border: none;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
        }
        
        .card-header {
            background-color: var(--primary-dark-blue);
            color: white;
            border-radius: 4px 4px 0 0 !important;
            padding: 0.4rem 0.6rem;
            font-weight: 400;
            font-size: 0.75rem;
            font-family: 'Ubuntu', sans-serif;
            line-height: 1.3;
        }
        
        .card-header i {
            font-size: 0.75rem;
        }
        
        .card-header .btn {
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
        }
        
        .card-header .btn i {
            font-size: 0.65rem;
        }
        
        .card-body {
            padding: 0.75rem;
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.8rem;
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
        
        .table {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.875rem;
        }
        
        .table thead {
            background-color: #f8f9fa;
        }
        
        .table thead th {
            color: var(--primary-dark-blue);
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            border-bottom: 1px solid var(--primary-dark-blue);
            font-size: 0.75rem;
            padding: 0.35rem 0.5rem;
        }
        
        .table tbody td {
            padding: 0.35rem 0.5rem;
            font-weight: 300;
            font-size: 0.8rem;
            font-family: 'Ubuntu', sans-serif;
            line-height: 1.3;
        }
        
        .table tbody td strong {
            font-weight: 300;
        }
        
        .table tbody tr {
            height: auto;
        }
        
        /* En-tête fixe pour les tableaux */
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .table-responsive table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-responsive table thead th {
            background-color: #fff !important;
            border-bottom: 2px solid #dee2e6;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 15px;
        }
        
        /* Style des cellules du tableau pour ressembler à Colorlib */
        .table-responsive table tbody td {
            border-bottom: 1px solid #f0f0f0;
            padding: 12px 15px;
        }
        
        .table-responsive table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* S'assurer que les en-têtes des tableaux spécifiques gardent leur style */
        .table-responsive table.table-caisses thead th,
        .table-responsive table.table-transfert thead th,
        .table-responsive table.table-approvisionnement thead th,
        .table-responsive table.table-sortie thead th,
        .table-responsive table.table-mouvements thead th,
        .table-responsive table.table-historique thead th,
        .table-responsive table.table-cotisations thead th,
        .table-responsive table.table-engagements thead th,
        .table-responsive table.table-paiements thead th,
        .table-responsive table.table-paiements-engagement thead th {
            background-color: #fff !important;
        }
        
        .row.g-2 {
            margin-left: -0.25rem;
            margin-right: -0.25rem;
        }
        
        .row.g-2 > * {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
        
        .form-label {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.75rem;
            margin-bottom: 0.35rem;
        }
        
        .form-control, .form-select {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.8rem;
            padding: 0.4rem 0.6rem;
        }
        
        .form-control-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1.5;
            height: calc(0.25rem * 2 + 0.75rem * 1.5 + 2px);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.65rem;
            line-height: 1.5;
            height: calc(0.25rem * 2 + 0.75rem * 1.5 + 2px);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn,
        .btn-primary,
        .btn-secondary,
        .btn-success,
        .btn-danger,
        .btn-warning,
        .btn-info,
        .btn-light,
        .btn-dark,
        .btn-outline-primary,
        .btn-outline-secondary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-warning,
        .btn-outline-info,
        .btn-outline-light,
        .btn-outline-dark {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            line-height: 1.3;
        }
        
        .btn-sm {
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
            line-height: 1.2;
        }
        
        .btn-lg {
            font-size: 0.75rem;
            padding: 0.4rem 0.75rem;
        }
        
        .badge {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
            line-height: 1.2;
        }
        
        .alert {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .alert i {
            font-size: 0.8rem;
        }
        
        .mb-4 {
            margin-bottom: 0.75rem !important;
        }
        
        .mb-3 {
            margin-bottom: 0.5rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.4rem !important;
        }
        
        dt {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 400;
            font-size: 0.75rem;
        }
        
        dd {
            font-family: 'Ubuntu', sans-serif;
            font-weight: 300;
            font-size: 0.8rem;
        }
        
        .text-center {
            padding: 0.5rem 0 !important;
        }
        
        .text-center i {
            font-size: 1.25rem !important;
        }
        
        .text-center p {
            font-size: 0.75rem !important;
            margin-top: 0.4rem !important;
        }
        
        .btn i {
            font-size: 0.7rem;
        }
        
        .btn-sm i {
            font-size: 0.65rem;
        }
        
        .btn-group-sm .btn {
            padding: 0.2rem 0.35rem;
            font-size: 0.65rem;
        }
        
        .btn-group-sm .btn i {
            font-size: 0.65rem;
        }
        
        .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .row > * {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .table-responsive {
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .top-bar {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Styles pour les toasts */
        .toast-container {
            z-index: 9999;
        }
        
        .toast {
            min-width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .toast-body {
            font-weight: 300;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.875rem;
        }
        
        /* Styles pour le modal de confirmation */
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .modal-content {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <span style="font-size: 0.85rem; color: var(--primary-dark-blue); font-weight: 300;">
                <i class="bi bi-person-circle"></i> 
                {{ auth()->user()->name ?? 'Utilisateur' }}
            </span>
        </div>
        <div class="top-bar-right" style="position: relative;">
            @include('components.audit-gadget')
            
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
                    <a href="{{ route('notifications.index') }}">Voir toutes les notifications</a>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="logout-btn" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-leaf"></i> {{ $appNom ?? 'Serenity' }}</h4>
        </div>
        <nav class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Tableau de bord</span>
            </a>
            
            <!-- Menu Caisses avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('caisses.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#caissesSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('caisses.*') ? 'true' : 'false' }}" 
                   aria-controls="caissesSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-cash-coin"></i>
                        <span>Caisses</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('caisses.*') ? 'show' : '' }}" id="caissesSubmenu">
                    <ul class="sidebar-submenu">
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.view'))
                        <li>
                            <a href="{{ route('caisses.index') }}" class="nav-link {{ request()->routeIs('caisses.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Liste des caisses</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.transfert'))
                        <!--li>
                            <a href="{{ route('caisses.transfert') }}" class="nav-link {{ request()->routeIs('caisses.transfert*') ? 'active' : '' }}">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Transfert inter caisse</span>
                            </a>
                        </li-->
                        @endif
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.approvisionner'))
                        <!--li>
                            <a href="{{ route('caisses.approvisionnement') }}" class="nav-link {{ request()->routeIs('caisses.approvisionnement*') ? 'active' : '' }}">
                                <i class="bi bi-plus-square"></i>
                                <span>Approvisionnement</span>
                            </a>
                        </li-->
                        @endif
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.sortie'))
                        <!--li>
                            <a href="{{ route('caisses.sortie') }}" class="nav-link {{ request()->routeIs('caisses.sortie*') ? 'active' : '' }}">
                                <i class="bi bi-dash-square"></i>
                                <span>Sortie de caisses</span>
                            </a>
                        </li-->
                        @endif
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('caisses.journal'))
                        <li>
                            <a href="{{ route('caisses.historique') }}" class="nav-link {{ request()->routeIs('caisses.historique') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Historique</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('caisses.journal') }}" class="nav-link {{ request()->routeIs('caisses.journal') || request()->routeIs('caisses.mouvements') ? 'active' : '' }}">
                                <i class="bi bi-journal-text"></i>
                                <span>Journal / Balance</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            @endif
            <!-- Menu Membres avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('membres.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('membres.*') || request()->routeIs('segments.*') || request()->routeIs('kyc.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#membresSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('membres.*') || request()->routeIs('segments.*') || request()->routeIs('kyc.*') ? 'true' : 'false' }}" 
                   aria-controls="membresSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-people"></i>
                        <span>Membres</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('membres.*') || request()->routeIs('segments.*') || request()->routeIs('kyc.*') ? 'show' : '' }}" id="membresSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('membres.index') }}" class="nav-link {{ request()->routeIs('membres.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Liste des membres</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('segments.index') }}" class="nav-link {{ request()->routeIs('segments.*') ? 'active' : '' }}">
                                <i class="bi bi-tags"></i>
                                <span>Gestion des segments</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('kyc.index') }}" class="nav-link {{ request()->routeIs('kyc.*') ? 'active' : '' }}">
                                <i class="bi bi-shield-check"></i>
                                <span>KYC</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
            
            <!-- Menu Cagnottes avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('cotisations.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('cotisations.*') || request()->routeIs('tags.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#cotisationsSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('cotisations.*') || request()->routeIs('tags.*') ? 'true' : 'false' }}" 
                   aria-controls="cotisationsSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-piggy-bank-fill"></i>
                        <span>Cagnottes</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('cotisations.*') || request()->routeIs('tags.*') ? 'show' : '' }}" id="cotisationsSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('cotisations.index') }}" class="nav-link {{ request()->routeIs('cotisations.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Liste des cagnottes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('tags.index') }}" class="nav-link {{ request()->routeIs('tags.*') ? 'active' : '' }}">
                                <i class="bi bi-tags"></i>
                                <span>Gestion des tags</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif

              <!-- Menu Demandes d'adhésion -->
            @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('cotisation-adhesions.index') }}" class="nav-link {{ request()->routeIs('cotisation-adhesions.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Demandes d'adhésion aux cagnottes privées</span>
                @php
                    $nbAdhesionsEnAttente = \App\Models\CotisationAdhesion::where('statut', 'en_attente')->whereHas('cotisation', fn($q) => $q->whereNull('created_by_membre_id'))->count();
                @endphp
                @if($nbAdhesionsEnAttente > 0)
                    <span class="badge bg-danger ms-auto" style="font-size: 0.65rem;">{{ $nbAdhesionsEnAttente }}</span>
                @endif
            </a>
            @endif

            <!-- Menu Tontines avec sous-menus -->
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('epargne-plans.*') ? 'active' : '' }}"
                   data-bs-toggle="collapse"
                   href="#epargneSubmenu"
                   role="button"
                   aria-expanded="{{ request()->routeIs('epargne-plans.*') ? 'true' : 'false' }}"
                   aria-controls="epargneSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-piggy-bank"></i>
                        <span>Tontines</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('epargne-plans.*') ? 'show' : '' }}" id="epargneSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('epargne-plans.index') }}" class="nav-link {{ request()->routeIs('epargne-plans.*') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Plans de tontine</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Menu Nano crédit avec sous-menus -->
            <div>
                <a class="nav-link has-submenu {{ request()->is('nano-credits*') || request()->is('nano-credit-paliers*') ? 'active' : '' }}"
                   data-bs-toggle="collapse"
                   href="#nanoCreditSubmenu"
                   role="button"
                   aria-expanded="{{ request()->is('nano-credits*') || request()->is('nano-credit-paliers*') ? 'true' : 'false' }}"
                   aria-controls="nanoCreditSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-phone"></i>
                        <span>Nano crédit</span>
                    </div>
                </a>
                <div class="collapse {{ request()->is('nano-credits*') || request()->is('nano-credit-paliers*') ? 'show' : '' }}" id="nanoCreditSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('admin.nano-credits.dashboard') }}" class="nav-link {{ request()->routeIs('admin.nano-credits.dashboard') ? 'active' : '' }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Tableau de bord</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('nano-credits.impayes') }}" class="nav-link {{ request()->routeIs('nano-credits.impayes') ? 'active' : '' }}">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Impayés</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('nano-credits.garants.index') }}" class="nav-link {{ request()->routeIs('nano-credits.garants.index') ? 'active' : '' }}">
                                <i class="bi bi-shield-shaded"></i>
                                <span>Garants</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('nano-credits.garants.retraits.index') }}" class="nav-link {{ request()->routeIs('nano-credits.garants.retraits.index') ? 'active' : '' }}">
                                <i class="bi bi-cash-stack"></i>
                                <span>Retraits Gains</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('nano-credit-paliers.index') }}" class="nav-link {{ request()->is('nano-credit-paliers*') ? 'active' : '' }}">
                                <i class="bi bi-ladder"></i>
                                <span>Paliers</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('nano-credits.index') }}" class="nav-link {{ request()->routeIs('nano-credits.index') || request()->routeIs('nano-credits.show') ? 'active' : '' }}">
                                <i class="bi bi-inbox"></i>
                                <span>Demandes</span>
                                @php
                                    $nbDemandesEnAttente = \App\Models\NanoCredit::where('statut', 'demande_en_attente')->count();
                                @endphp
                                @if($nbDemandesEnAttente > 0)
                                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">{{ $nbDemandesEnAttente }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Menu Engagements avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('engagements.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('engagements.*') || request()->routeIs('engagement-tags.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#engagementsSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('engagements.*') || request()->routeIs('engagement-tags.*') ? 'true' : 'false' }}" 
                   aria-controls="engagementsSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Engagements</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('engagements.*') || request()->routeIs('engagement-tags.*') ? 'show' : '' }}" id="engagementsSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('engagements.index') }}" class="nav-link {{ request()->routeIs('engagements.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Liste des engagements</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('engagement-tags.index') }}" class="nav-link {{ request()->routeIs('engagement-tags.*') ? 'active' : '' }}">
                                <i class="bi bi-tags"></i>
                                <span>Gestion des tags</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif

              <!-- Menu Paiements avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('paiements.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('paiements.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#paiementsSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('paiements.*') ? 'true' : 'false' }}" 
                   aria-controls="paiementsSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-cash-coin"></i>
                        <span>Paiements</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('paiements.*') ? 'show' : '' }}" id="paiementsSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('paiements.index') }}" class="nav-link {{ request()->routeIs('paiements.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Paiements des cagnottes</span>
                            </a>
                        </li>
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('paiements.engagement'))
                        <li>
                            <a href="{{ route('paiements.engagement.index') }}" class="nav-link {{ request()->routeIs('paiements.engagement.*') ? 'active' : '' }}">
                                <i class="bi bi-clipboard-check"></i>
                                <span>Paiement engagement</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            @endif
            
            <!-- Menu Remboursements -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('remboursements.view'))
            <a href="{{ route('remboursements.index') }}" class="nav-link {{ request()->routeIs('remboursements.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span>Remboursements</span>
                @php
                    $nbEnAttente = \App\Models\Remboursement::where('statut', 'en_attente')->count();
                @endphp
                @if($nbEnAttente > 0)
                    <span class="badge bg-danger ms-auto" style="font-size: 0.65rem;">{{ $nbEnAttente }}</span>
                @endif
            </a>
            @endif
            
          

            <!-- Menu Demandes de versement -->
            @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('cotisation-versement-demandes.index') }}" class="nav-link {{ request()->routeIs('cotisation-versement-demandes.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>Demandes de versement</span>
                @php
                    $nbVersementEnAttente = \App\Models\CotisationVersementDemande::where('statut', 'en_attente')->count();
                @endphp
                @if($nbVersementEnAttente > 0)
                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">{{ $nbVersementEnAttente }}</span>
                @endif
            </a>
            @endif
            
            <!-- Menu Annonces -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('annonces.view'))
            <div>
                <a href="{{ route('annonces.index') }}" class="nav-link {{ request()->routeIs('annonces.*') ? 'active' : '' }}">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-megaphone"></i>
                        <span>Annonces</span>
                    </div>
                </a>
            </div>
            @endif
            
            <!-- Menu Traitement de fin de mois avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('fin-mois.process') || auth()->user()->hasPermission('fin-mois.journal'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('fin-mois.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#finMoisSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('fin-mois.*') ? 'true' : 'false' }}" 
                   aria-controls="finMoisSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-calendar-month"></i>
                        <span>Traitement de fin de mois</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('fin-mois.*') ? 'show' : '' }}" id="finMoisSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('fin-mois.index') }}" class="nav-link {{ request()->routeIs('fin-mois.index') || request()->routeIs('fin-mois.process') || request()->routeIs('fin-mois.preview') ? 'active' : '' }}">
                                <i class="bi bi-send"></i>
                                <span>Lancer le traitement</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('fin-mois.journal') }}" class="nav-link {{ request()->routeIs('fin-mois.journal') ? 'active' : '' }}">
                                <i class="bi bi-journal-text"></i>
                                <span>Journal</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
            
            <!-- Menu Rapports avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('rapports.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('rapports.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#rapportsSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('rapports.*') ? 'true' : 'false' }}" 
                   aria-controls="rapportsSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-graph-up"></i>
                        <span>Rapports</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('rapports.*') ? 'show' : '' }}" id="rapportsSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('rapports.caisse') }}" class="nav-link {{ request()->routeIs('rapports.caisse') ? 'active' : '' }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>Par caisse</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('rapports.cotisation') }}" class="nav-link {{ request()->routeIs('rapports.cotisation') ? 'active' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Par cagnotte</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('rapports.membre') }}" class="nav-link {{ request()->routeIs('rapports.membre') ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>Par membre</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif
            
          
            
            <!-- Menu Campagnes d'Emails -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('campagnes.create'))
            <a href="{{ route('campagnes.index') }}" class="nav-link {{ request()->routeIs('campagnes.*') ? 'active' : '' }}">
                <i class="bi bi-envelope-paper"></i>
                <span>Campagnes d'Emails</span>
            </a>
            @endif
            
            <!-- Menu Historique des Emails -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('email-logs.view'))
            <a href="{{ route('email-logs.index') }}" class="nav-link {{ request()->routeIs('email-logs.*') ? 'active' : '' }}">
                <i class="bi bi-envelope-check"></i>
                <span>Historique des Emails</span>
            </a>
            @endif
            


            <!-- Menu Parrainage -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('parrainage.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->is('parrainage*') ? 'active' : '' }}"
                   data-bs-toggle="collapse"
                   href="#parrainageSubmenu"
                   role="button"
                   aria-expanded="{{ request()->is('parrainage*') ? 'true' : 'false' }}"
                   aria-controls="parrainageSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-people"></i>
                        <span>Parrainage</span>
                        @php
                            $nbReclamations = \App\Models\ParrainageCommission::where('statut', 'reclame')->count();
                        @endphp
                        @if($nbReclamations > 0)
                            <span class="badge bg-warning text-dark ms-auto" style="font-size:0.65rem;">{{ $nbReclamations }}</span>
                        @endif
                    </div>
                </a>
                <div class="collapse {{ request()->is('parrainage*') ? 'show' : '' }}" id="parrainageSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('parrainage.admin.config') }}" class="nav-link {{ request()->routeIs('parrainage.admin.config') ? 'active' : '' }}">
                                <i class="bi bi-gear"></i><span>Configuration</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('parrainage.admin.commissions') }}" class="nav-link {{ request()->routeIs('parrainage.admin.commissions*') ? 'active' : '' }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>Commissions</span>
                                @if($nbReclamations > 0)
                                    <span class="badge bg-warning text-dark ms-auto" style="font-size:0.65rem;">{{ $nbReclamations }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('parrainage.admin.parrains') }}" class="nav-link {{ request()->routeIs('parrainage.admin.parrains') ? 'active' : '' }}">
                                <i class="bi bi-person-lines-fill"></i><span>Parrains</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif


            <!-- Menu Utilisateurs avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('users.view'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('users.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#usersSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('users.*') ? 'true' : 'false' }}" 
                   aria-controls="usersSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-person-badge"></i>
                        <span>Utilisateurs</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('users.*') ? 'show' : '' }}" id="usersSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span>Liste des utilisateurs</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            @endif

            <!-- Menu Rôles et Permissions -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.roles'))
            <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span>Rôles et Permissions</span>
            </a>
            @endif
            
            <!-- Menu Journal d'Audit -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.audit'))
            <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Journal d'Audit</span>
            </a>
            @endif

            <!-- ═══ Menu Sécurité & Intégrité (Merkle Ledger) ═══ -->
            @if(auth()->user()->hasRole('admin'))
            <div>
                @php
                    try {
                        $lastScan = \App\Models\AuditChecksumLog::orderBy('created_at', 'desc')->first();
                        $hasAlert = $lastScan && !$lastScan->is_valid;
                    } catch (\Throwable $e) {
                        $lastScan = null;
                        $hasAlert = false;
                    }
                    $securityActive = request()->routeIs('logs.security*') || request()->routeIs('audit.integrity*');
                @endphp
                <a class="nav-link has-submenu {{ $securityActive ? 'active' : '' }}"
                   data-bs-toggle="collapse"
                   href="#securiteSubmenu"
                   role="button"
                   aria-expanded="{{ $securityActive ? 'true' : 'false' }}"
                   aria-controls="securiteSubmenu"
                   style="{{ $hasAlert ? 'color: #ff9f9f !important;' : '' }}">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-shield-lock{{ $hasAlert ? '-fill' : '' }}" style="{{ $hasAlert ? 'color: #ff9f9f;' : '' }}"></i>
                        <span>Sécurité & Intégrité</span>
                        @if($hasAlert)
                            <span class="badge ms-auto" style="background-color: #dc3545; font-size: 0.6rem; padding: 0.15rem 0.35rem; animation: pulse-badge 1.5s infinite;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </span>
                        @endif
                    </div>
                </a>
                <div class="collapse {{ $securityActive ? 'show' : '' }}" id="securiteSubmenu">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="{{ route('logs.security') }}" class="nav-link {{ request()->routeIs('logs.security') ? 'active' : '' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Historique des scans</span>
                                @if($hasAlert)
                                    <span class="badge bg-danger ms-auto" style="font-size: 0.6rem;">!</span>
                                @endif
                            </a>
                        </li>
                        @if(\Illuminate\Support\Facades\Route::has('audit.integrity.ledger'))
                        <li>
                            <a href="{{ route('audit.integrity.ledger') }}" class="nav-link {{ request()->routeIs('audit.integrity.ledger') ? 'active' : '' }}">
                                <i class="bi bi-link-45deg"></i>
                                <span>Chaîne Merkle</span>
                            </a>
                        </li>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('audit.integrity.changes'))
                        <li>
                            <a href="{{ route('audit.integrity.changes') }}" class="nav-link {{ request()->routeIs('audit.integrity.changes') ? 'active' : '' }}">
                                <i class="bi bi-person-lock"></i>
                                <span>Modifications traçées</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('logs.security.scan') }}" class="d-block">
                                @csrf
                                <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start" style="cursor: pointer; color: rgba(255,255,255,0.7);" onclick="return confirm('Lancer un scan d\'intégrité complet maintenant ?')">
                                    <i class="bi bi-radar"></i>
                                    <span>Lancer un scan</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            @endif

            <!-- Menu Backup/Restauration -->

            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.backup'))
            <a href="{{ route('backups.index') }}" class="nav-link {{ request()->routeIs('backups.*') ? 'active' : '' }}">
                <i class="bi bi-database"></i>
                <span>Backup/Restauration</span>
            </a>
            @endif
            
            <!-- Menu Paramètres Généraux -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.general'))
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i>
                <span>Paramètres Généraux</span>
            </a>
            @endif
            
            <!-- Menu Paramètres avec sous-menus -->
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.smtp') || auth()->user()->hasPermission('settings.templates') || auth()->user()->hasPermission('settings.paydunya'))
            <div>
                <a class="nav-link has-submenu {{ request()->routeIs('smtp.*') || request()->routeIs('email-templates.*') || request()->routeIs('payment-methods.*') || request()->routeIs('sms-gateways.*') ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#parametresSubmenu" 
                   role="button" 
                   aria-expanded="{{ request()->routeIs('smtp.*') || request()->routeIs('email-templates.*') || request()->routeIs('payment-methods.*') || request()->routeIs('sms-gateways.*') ? 'true' : 'false' }}" 
                   aria-controls="parametresSubmenu">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-gear"></i>
                        <span>Paramètres</span>
                    </div>
                </a>
                <div class="collapse {{ request()->routeIs('smtp.*') || request()->routeIs('email-templates.*') || request()->routeIs('payment-methods.*') || request()->routeIs('sms-gateways.*') ? 'show' : '' }}" id="parametresSubmenu">
                    <ul class="sidebar-submenu">
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.smtp'))
                        <li>
                            <a href="{{ route('smtp.index') }}" class="nav-link {{ request()->routeIs('smtp.*') ? 'active' : '' }}">
                                <i class="bi bi-envelope"></i>
                                <span>SMTP</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.templates'))
                        <li>
                            <a href="{{ route('email-templates.index') }}" class="nav-link {{ request()->routeIs('email-templates.*') ? 'active' : '' }}">
                                <i class="bi bi-file-text"></i>
                                <span>Template</span>
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('sms-gateways.index') }}" class="nav-link {{ request()->routeIs('sms-gateways.*') ? 'active' : '' }}">
                                <i class="bi bi-chat-dots"></i>
                                <span>SMS</span>
                            </a>
                        </li>
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.paydunya'))
                        <li>
                            <a href="{{ route('payment-methods.index') }}" class="nav-link {{ request()->routeIs('payment-methods.*') ? 'active' : '' }}">
                                <i class="bi bi-credit-card"></i>
                                <span>Moyens de paiements</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            @endif
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>
    
    <!-- Toast Container (en haut à droite) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="toastContainer"></div>
    </div>
    
    <!-- Modal de confirmation Bootstrap -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--primary-dark-blue); color: white;">
                    <h5 class="modal-title" id="confirmModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-exclamation-triangle"></i> Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <p id="confirmModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Annuler</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmModalButton" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'alerte Bootstrap -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="alertModalHeader" style="background: var(--primary-dark-blue); color: white;">
                    <h5 class="modal-title" id="alertModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-info-circle" id="alertModalIcon"></i> <span id="alertModalTitle">Information</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <p id="alertModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">OK</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'alerte Bootstrap -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="alertModalHeader" style="background: var(--primary-dark-blue); color: white;">
                    <h5 class="modal-title" id="alertModalLabel" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                        <i class="bi bi-info-circle" id="alertModalIcon"></i> <span id="alertModalTitle">Information</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">
                    <p id="alertModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal" style="font-weight: 300; font-family: 'Ubuntu', sans-serif;">OK</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour les confirmations et toasts -->
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
        
        // Fonction pour afficher une alerte via modal Bootstrap
        function showAlert(message, type = 'info', title = null) {
            const modal = new bootstrap.Modal(document.getElementById('alertModal'));
            const modalHeader = document.getElementById('alertModalHeader');
            const modalTitle = document.getElementById('alertModalTitle');
            const modalIcon = document.getElementById('alertModalIcon');
            const modalMessage = document.getElementById('alertModalMessage');
            
            // Définir le type d'alerte
            let bgColor = 'var(--primary-dark-blue)';
            let iconClass = 'bi-info-circle';
            let defaultTitle = 'Information';
            
            if (type === 'error' || type === 'danger') {
                bgColor = '#dc3545';
                iconClass = 'bi-x-circle';
                defaultTitle = 'Erreur';
            } else if (type === 'warning') {
                bgColor = '#ffc107';
                iconClass = 'bi-exclamation-triangle';
                defaultTitle = 'Attention';
            } else if (type === 'success') {
                bgColor = '#198754';
                iconClass = 'bi-check-circle';
                defaultTitle = 'Succès';
            }
            
            modalHeader.style.background = bgColor;
            modalIcon.className = 'bi ' + iconClass;
            modalTitle.textContent = title || defaultTitle;
            modalMessage.textContent = message;
            
            modal.show();
        }
        
        // Fonction pour afficher une confirmation via modal
        function confirmAction(message, callback) {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmModalMessage').textContent = message;
            
            const confirmButton = document.getElementById('confirmModalButton');
            // Supprimer les anciens event listeners
            const newConfirmButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
            
            newConfirmButton.addEventListener('click', function() {
                modal.hide();
                if (callback) callback();
            });
            
            modal.show();
        }
        
        // Intercepter les formulaires avec la classe delete-form
        document.addEventListener('DOMContentLoaded', function() {
            // Formulaires avec classe delete-form
            const deleteForms = document.querySelectorAll('form.delete-form');
            deleteForms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const message = form.getAttribute('data-message') || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                    confirmAction(message, function() {
                        form.submit();
                    });
                });
            });
            
            // Boutons avec classe delete-button
            const deleteButtons = document.querySelectorAll('button.delete-button');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const message = button.getAttribute('data-message') || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                    const form = button.closest('form');
                    if (form) {
                        confirmAction(message, function() {
                            form.submit();
                        });
                    }
                });
            });
            
            // Intercepter les formulaires avec onsubmit="return confirm(...)" (pour compatibilité)
            const forms = document.querySelectorAll('form[onsubmit*="confirm"]');
            forms.forEach(function(form) {
                const originalOnsubmit = form.getAttribute('onsubmit');
                if (originalOnsubmit && originalOnsubmit.includes('confirm')) {
                    form.removeAttribute('onsubmit');
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const message = originalOnsubmit.match(/'([^']+)'/)[1] || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                        confirmAction(message, function() {
                            form.submit();
                        });
                    });
                }
            });
            
            // Intercepter les boutons avec onclick="return confirm(...)" (pour compatibilité)
            const buttons = document.querySelectorAll('button[onclick*="confirm"]');
            buttons.forEach(function(button) {
                const originalOnclick = button.getAttribute('onclick');
                if (originalOnclick && originalOnclick.includes('confirm')) {
                    button.removeAttribute('onclick');
                    const form = button.closest('form');
                    if (form) {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const message = originalOnclick.match(/'([^']+)'/)[1] || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                            confirmAction(message, function() {
                                form.submit();
                            });
                        });
                    }
                }
            });
        });
        
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
        
        // Gestion des notifications
        (function() {
            const notificationsBtn = document.getElementById('notificationsBtn');
            const notificationsDropdown = document.getElementById('notificationsDropdown');
            const notificationsBadge = document.getElementById('notificationsBadge');
            const notificationsList = document.getElementById('notificationsList');
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            
            let notifications = [];
            let unreadCount = 0;
            
            // Charger les notifications
            function loadNotifications() {
                fetch('{{ route('notifications.unread') }}')
                    .then(response => response.json())
                    .then(data => {
                        notifications = data.notifications || [];
                        unreadCount = data.unread_count || 0;
                        updateBadge();
                        renderNotifications();
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des notifications:', error);
                    });
            }
            
            // Mettre à jour le badge
            function updateBadge() {
                if (unreadCount > 0) {
                    notificationsBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    notificationsBadge.style.display = 'flex';
                } else {
                    notificationsBadge.textContent = '';
                    notificationsBadge.style.display = 'none';
                }
            }
            
            // Afficher les notifications
            function renderNotifications() {
                if (notifications.length === 0) {
                    notificationsList.innerHTML = '<div class="notifications-empty">Aucune notification</div>';
                    return;
                }
                
                let html = '';
                notifications.forEach(notification => {
                    const isUnread = !notification.read_at;
                    const itemClass = isUnread ? 'notification-item unread' : 'notification-item';
                    html += `
                        <div class="${itemClass}" data-id="${notification.id}" onclick="markNotificationAsRead('${notification.id}')">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${notification.created_at}</div>
                        </div>
                    `;
                });
                notificationsList.innerHTML = html;
            }
            
            // Marquer une notification comme lue
            window.markNotificationAsRead = function(id) {
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger les notifications
                        loadNotifications();
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du marquage de la notification:', error);
                });
            };
            
            // Marquer toutes les notifications comme lues
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fetch('{{ route('notifications.read-all') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadNotifications();
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors du marquage de toutes les notifications:', error);
                    });
                });
            }
            
            // Toggle dropdown
            if (notificationsBtn) {
                notificationsBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationsDropdown.classList.toggle('show');
                });
            }
            
            // Fermer le dropdown si on clique ailleurs
            document.addEventListener('click', function(e) {
                if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
                    notificationsDropdown.classList.remove('show');
                }
            });
            
            // Charger les notifications au chargement de la page
            loadNotifications();
            
            // Recharger les notifications toutes les 30 secondes
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
