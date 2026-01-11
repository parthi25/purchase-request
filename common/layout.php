<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? ''; // Use role_code, not role_name
$roleName = $_SESSION['role_name'] ?? 'User'; // Keep role_name for display
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch menu items from database for current role
require_once __DIR__ . '/../config/db.php';
$menuItems = [];
$menuGroups = [];

try {
    // Only query if we have a valid role
    if (!empty($role)) {
        $menuQuery = "SELECT * FROM role_menu_settings 
                      WHERE role = ? AND is_active = 1 AND is_visible = 1 
                      ORDER BY menu_group ASC, menu_order ASC, menu_item_label ASC";
        $menuStmt = $conn->prepare($menuQuery);
        if ($menuStmt) {
            $menuStmt->bind_param("s", $role);
            $menuStmt->execute();
            $menuResult = $menuStmt->get_result();
        
            while ($menuRow = $menuResult->fetch_assoc()) {
                $menuGroup = $menuRow['menu_group'] ?? 'main';
                if (!isset($menuGroups[$menuGroup])) {
                    $menuGroups[$menuGroup] = [];
                }
                $menuGroups[$menuGroup][] = $menuRow;
            }
            
            $menuResult->free();
            $menuStmt->close();
        }
    } else {
        error_log("Warning: No role found in session for user ID: " . $userid);
    }
} catch (Exception $e) {
    error_log("Error loading menu items: " . $e->getMessage());
    // Fallback to empty menu if database query fails
    $menuGroups = [];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="black">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PR Tracker</title>
    
    <!-- Resource Hints for Performance -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    
    <!-- Critical CSS - Load First -->
    <link href="../assets/css/daisyui@5.css" rel="stylesheet" type="text/css" />
    <link href="../assets/css/themes.css" rel="stylesheet" type="text/css" />
    
    <!-- Non-Critical CSS -->
    <link rel="stylesheet" href="../assets/css/font-awesome-6.0.0.min.css">
    <link rel="stylesheet" href="../assets/css/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/css/select2.min.css">
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/select2.min.js"></script>
        <script src="../assets/js/browser@4.js"></script>
    <script src="../common/js/notifications.js"></script>
    <script src="../assets/js/flatpickr.min.js"></script>
    <script src="../common/js/status-badges.js"></script>
    <script src="../common/js/page-loader.js"></script>
    <script src="../common/js/drawer.js"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/brand/favicon.ico" type="image/x-icon">
    
    <!-- Select2 Theme Overrides -->
    <style>
        /* Select2 overrides for black theme */
        [data-theme="black"] .select2-container--default .select2-selection--multiple {
            background-color: black !important;
            border-color: hsl(var(--bc) / 0.2) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: hsl(var(--p)) !important;
            border-color: hsl(var(--p)) !important;
            color: hsl(var(--pc)) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: hsl(var(--pc)) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            background-color: black !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
            background-color: black !important;
            color: hsl(var(--bc)) !important;
        }
        
        /* Results dropdown - black background */
        [data-theme="black"] .select2-container--default .select2-results > .select2-results__options {
            background-color: black !important;
            color: white !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-results__option {
            background-color: black !important;
            color: white !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-results__option--highlighted {
            background-color: hsl(var(--p)) !important;
            color: hsl(var(--pc)) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: hsl(var(--p) / 0.5) !important;
            color: hsl(var(--pc)) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-dropdown {
            background-color: black !important;
            border-color: hsl(var(--bc) / 0.2) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: hsl(var(--b1)) !important;
            color: hsl(var(--bc)) !important;
            border-color: hsl(var(--bc) / 0.2) !important;
        }
        
        /* Single select styling for black theme */
        [data-theme="black"] .select2-container--default .select2-selection--single {
            background-color: black !important;
            border-color: hsl(var(--bc) / 0.2) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: hsl(var(--bc)) !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--single .select2-selection__arrow {
            background-color:   black !important;
        }
        
        [data-theme="black"] .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: hsl(var(--bc)) transparent transparent transparent !important;
        }
        
        /* Sidebar width and text visibility - Mobile: Expand when drawer is open */
        #my-drawer-4:checked ~ .drawer-side #sidebarContent {
            width: 16rem !important; /* w-64 = 256px */
        }
        
        #my-drawer-4:checked ~ .drawer-side #sidebarContent .menu a > span:not(.flex-none) {
            opacity: 1 !important;
            width: auto !important;
            overflow: visible !important;
        }
        
        /* Sidebar text visibility - Desktop: Show text only when sidebar is expanded */
        @media (min-width: 1024px) {
            /* Default: Hide all text spans (except icons) */
            .drawer-side #sidebarContent .menu a > span:not(.flex-none) {
                opacity: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
            }
            
            /* When sidebar is expanded (has sidebar-expanded class), show text */
            .drawer-side #sidebarContent.sidebar-expanded .menu a > span:not(.flex-none),
            .drawer-side #sidebarContent.w-64.sidebar-expanded .menu a > span:not(.flex-none) {
                opacity: 1 !important;
                width: auto !important;
                overflow: visible !important;
            }
            
            /* When sidebar is collapsed (w-20 and not expanded), hide text - MUST be important */
            .drawer-side #sidebarContent.w-20 .menu a > span:not(.flex-none),
            .drawer-side #sidebarContent:not(.sidebar-expanded):not(.w-64) .menu a > span:not(.flex-none) {
                opacity: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
            }
            
            /* On hover, show text even when collapsed (but only temporarily) */
            .drawer-side #sidebarContent.group\/sidebar:hover:not(.sidebar-expanded) .menu a > span:not(.flex-none) {
                opacity: 1 !important;
                width: auto !important;
                overflow: visible !important;
            }
        }
        
        /* ============================================
           GLOBAL MOBILE RESPONSIVE STYLES
           ============================================ */
        
        /* Mobile: Fix search inputs and form controls */
        @media (max-width: 768px) {
            /* Make fixed-width inputs responsive */
            input[class*="w-64"],
            input[class*="w-48"],
            .input.w-64,
            .input.w-48 {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            /* Form controls should stack on mobile */
            .form-control {
                width: 100% !important;
                min-width: 100% !important;
            }
            
            /* Flex containers should stack */
            .flex.flex-wrap {
                flex-direction: column;
            }
            
            /* Buttons in forms should be full width on mobile */
            form .btn,
            .form-control .btn {
                width: 100%;
            }
            
            /* Table actions should stack */
            .table td .flex.gap-2,
            .table th .flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            /* Card headers and actions */
            .card-body > .flex.justify-between,
            .card-body > .flex.flex-wrap {
                flex-direction: column;
                gap: 1rem;
            }
            
            /* Modal improvements */
            .modal-box {
                margin: 0.5rem !important;
                max-width: calc(100% - 1rem) !important;
                width: calc(100% - 1rem) !important;
            }
            
            /* Dropdown menus */
            .dropdown-content {
                max-width: calc(100vw - 2rem);
            }
        }
        
        /* Small mobile devices */
        @media (max-width: 640px) {
            /* Reduce padding on mobile */
            .card-body {
                padding: 1rem !important;
            }
            
            /* Smaller text on very small screens */
            h1 {
                font-size: 1.5rem !important;
            }
            
            h2 {
                font-size: 1.25rem !important;
            }
            
            /* Table improvements */
            .table {
                font-size: 0.875rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.25rem !important;
            }
            
            /* Button groups */
            .join,
            .btn-group {
                width: 100%;
            }
            
            /* Input groups */
            .input-group {
                width: 100%;
            }
            
            /* Form controls - remove min-width constraints on mobile */
            .form-control[class*="min-w"],
            .form-control.min-w-\[150px\],
            .form-control.min-w-\[200px\] {
                min-width: 100% !important;
                width: 100% !important;
            }
            
            /* Form button groups should stack */
            form .flex.gap-2 {
                flex-direction: column;
            }
            
            form .btn {
                width: 100%;
            }
        }
        
        /* Tablet and up - ensure proper spacing */
        @media (min-width: 768px) and (max-width: 1023px) {
            .card-body {
                padding: 1.5rem;
            }
        }
        
        /* Ensure tables are scrollable on mobile */
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }
        
        /* Fix for modals on mobile */
        @media (max-width: 768px) {
            .modal.modal-middle .modal-box {
                max-height: 90vh;
                margin: 1rem;
            }
            
            .modal-backdrop {
                background-color: rgba(0, 0, 0, 0.5);
            }
        }
        
        /* Ensure viewport doesn't cause horizontal scroll */
        body {
            overflow-x: hidden;
        }
        
        /* Fix for negative margins on mobile */
        @media (max-width: 768px) {
            .-mx-4 {
                margin-left: -0.5rem !important;
                margin-right: -0.5rem !important;
            }
            
            .px-4 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
        }
        
        /* Filter section improvements for mobile */
        @media (max-width: 768px) {
            /* Filter buttons and view toggle in single row on mobile */
            .flex.flex-wrap.items-center.gap-2 {
                width: 100%;
            }
            
            /* Ensure Apply, Reset, Table, Card buttons are in a row */
            .flex.flex-wrap.items-center.gap-2 > div.flex.items-center.gap-2,
            .flex.flex-wrap.items-center.gap-2 > div.join {
                flex: 1 1 auto;
                min-width: 0;
            }
            
            /* Filter buttons - equal width on mobile */
            .flex.items-center.gap-2 button,
            .join button {
                flex: 1 1 auto;
                min-width: 0;
            }
        }
        
        /* Very small screens - stack filter and view toggle */
        @media (max-width: 480px) {
            .flex.flex-wrap.items-center.gap-2 {
                flex-direction: column;
                align-items: stretch;
            }
            
            .flex.flex-wrap.items-center.gap-2 > div {
                width: 100%;
            }
        }
    </style>
</head>
<body class="h-screen overflow-hidden">
    <!-- Drawer / Layout Wrapper -->
    <div class="drawer lg:drawer-open h-full">
        <input id="my-drawer-4" type="checkbox" class="drawer-toggle" />
        
        <!-- Drawer Content -->
        <div class="drawer-content flex flex-col h-full min-h-0">
            <!-- Navbar -->
            <nav class="navbar w-full bg-base-200 z-40 shadow-sm border-b border-base-300 sticky top-0">
                <div class="flex-none">
                    <label for="my-drawer-4" aria-label="open sidebar" class="btn btn-square btn-ghost lg:hidden">
                        <!-- Mobile sidebar toggle icon - Hamburger menu -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                    <button type="button" aria-label="toggle sidebar" class="btn btn-square btn-ghost hidden lg:inline-flex" id="desktop-sidebar-toggle">
                        <!-- Desktop sidebar toggle icon - Hamburger menu -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 px-4">
                    <span class="text-lg font-bold">Purchase Tracker</span>
                </div>
                
                <div class="flex-none gap-2">
                     <!-- Theme Toggle -->
                    <button id="themeToggle" class="btn btn-ghost btn-square">
                        <span id="themeIcon" class="text-xl">🌙</span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full bg-primary text-primary-content">
                                <span class="text-sm font-bold"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                            </div>
                        </div>
                        <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                            <li class="menu-title">
                                <span><?php echo htmlspecialchars($username); ?></span><span class="text-blue-500"><?php echo htmlspecialchars($roleName); ?></span>
                            </li>
                            <li><hr class="my-1"></li>
                            <li>
                                <a href="profile.php">Profile Settings</a>
                            </li>
                            <li><hr class="my-1"></li>
                            <li>
                                <a href="../update/logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content Wrapper -->
            <!-- Note: h-full and overflow-y-auto are important for scrolling content independently -->
            <div class="flex-1 overflow-y-auto p-4 lg:p-6 scroll-smooth min-h-0">
                <!-- Loader -->
                <div id="pageLoader" class="w-full">
                    <div class="space-y-6 animate-pulse">
                        <div class="flex justify-between items-center mb-6">
                            <div class="skeleton h-10 w-48"></div>
                            <div class="skeleton h-10 w-32"></div>
                        </div>
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="flex flex-wrap gap-4">
                                    <div class="skeleton h-12 w-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content (Hidden Initially) -->
                <main id="pageContent" class="hidden">

