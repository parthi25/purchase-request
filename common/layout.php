<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'] ?? 'PO_Head';
$username = $_SESSION['username'] ?? 'User';
$userid = $_SESSION['user_id'] ?? 0;

// Role-based home URLs
$roleUrls = [
    'admin' => './admin.php',
    'buyer' => './buyer.php',
    'B_Head' => './buyer-head.php',
    'PO_Head' => './po-head.php',
    'PO_Team' => './po-head.php',
    'PO_Team_Member' => './po-member.php'
];
$homeUrl = $roleUrls[$role] ?? './po-head.php';

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PR Tracker</title>
    <link href="../assets/css/daisyui@5.css" rel="stylesheet" type="text/css" />
    <script src="../assets/js/browser@4.js"></script>
    <link href="../assets/css/themes.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="../assets/css/font-awesome-6.0.0.min.css">
    <link rel="stylesheet" href="../assets/css/sweetalert2.min.css">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../assets/css/flatpickr.min.css">
    <script src="../assets/js/flatpickr.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    <link rel="stylesheet" href="../assets/css/select2.min.css">
    <script src="../assets/js/select2.min.js"></script>
    <link rel="shortcut icon" href="../assets/brand/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="drawer lg:drawer-open">
        <input id="drawer-toggle" type="checkbox" class="drawer-toggle" />
        
        <!-- Sidebar -->
        <div class="drawer-side">
            <label for="drawer-toggle" class="drawer-overlay"></label>
            <aside class="w-64 min-h-full bg-base-200">
                <div class="p-4">
                    <h2 class="text-2xl font-bold mb-6">Purchase Tracker</h2>
                    <ul class="menu p-0 w-full">
                        <li>
                            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $homeUrl; ?>" class="<?php echo in_array($currentPage, ['admin.php', 'buyer.php', 'buyer-head.php', 'po-head.php', 'po-member.php']) ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Home
                            </a>
                        </li>
                        <?php if ($role === 'admin' || $role === 'PO_Head' || $role === 'PO_Team'): ?>
                        <li>
                            <a href="product-stock.php" class="<?php echo $currentPage === 'product-stock.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Product Stock
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="analytics.php" class="<?php echo $currentPage === 'analytics.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Analytics
                            </a>
                        </li>
                        <?php if (in_array($role, ['super_admin', 'master'])): ?>
                        <li>
                            <a href="superadmin.php" class="<?php echo $currentPage === 'superadmin.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Status Flow Management
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($role, ['super_admin', 'master'])): ?>
                        <li class="menu-title">
                            <span>Master Management</span>
                        </li>
                        <li>
                            <a href="user-management.php" class="<?php echo $currentPage === 'user-management.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                User Management
                            </a>
                        </li>
                        <li>
                            <a href="category-master.php" class="<?php echo $currentPage === 'category-master.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                Category Master
                            </a>
                        </li>
                        <li>
                            <a href="category-assignment.php" class="<?php echo $currentPage === 'category-assignment.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Category Assignment
                            </a>
                        </li>
                        <li>
                            <a href="buyer-mapping.php" class="<?php echo $currentPage === 'buyer-mapping.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Buyer Mapping
                            </a>
                        </li>
                        <li>
                            <a href="supplier-master.php" class="<?php echo $currentPage === 'supplier-master.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Supplier Master
                            </a>
                        </li>
                        <li>
                            <a href="purchase-type-master.php" class="<?php echo $currentPage === 'purchase-type-master.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Purchase Type Master
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active bg-primary text-primary-content' : ''; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

        <!-- Main Content -->
        <div class="drawer-content flex flex-col">
            <!-- Top Navbar -->
            <div class="navbar bg-base-200 shadow-sm sticky top-0 z-50">
                <div class="flex-none lg:hidden">
                    <label for="drawer-toggle" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </label>
                </div>
                
                <div class="flex-1">
                    <a class="btn btn-ghost text-xl">Purchase Tracker</a>
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
                                <span><?php echo htmlspecialchars($username); ?></span>
                            </li>
                            <li class="menu-title">
                                <span class="text-xs"><?php echo htmlspecialchars($role); ?></span>
                            </li>
                            <li><hr class="my-1"></li>
                            <li>
                                <a href="profile.php">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Profile Settings
                                </a>
                            </li>
                            <li><hr class="my-1"></li>
                            <li>
                                <a href="../update/logout.php">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-4 lg:p-6">
                <?php
                // Theme toggle script
                ?>
                <script>
                    // Theme toggle functionality
                    (function() {
                        const themeToggle = document.getElementById('themeToggle');
                        const themeIcon = document.getElementById('themeIcon');
                        const html = document.documentElement;
                        
                        // Get saved theme or default to dark
                        const savedTheme = localStorage.getItem('theme') || 'black';
                        html.setAttribute('data-theme', savedTheme);
                        themeIcon.textContent = savedTheme === 'black' ? '🌙' : '☀️';
                        
                        themeToggle.addEventListener('click', () => {
                            const currentTheme = html.getAttribute('data-theme');
                            const newTheme = currentTheme === 'black' ? 'corporate' : 'black';
                            html.setAttribute('data-theme', newTheme);
                            localStorage.setItem('theme', newTheme);
                            themeIcon.textContent = newTheme === 'black' ? '🌙' : '☀️';
                        });
                    })();
                </script>

