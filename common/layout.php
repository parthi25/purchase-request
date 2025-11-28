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
<html lang="en" data-theme="corporate">
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
    <!-- Favicon -->
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
                        <?php
                        // Render dynamic menu items from database
                        $previousGroup = '';
                        foreach ($menuGroups as $groupName => $groupMenus) {
                            // Add menu group title if not 'main' and different from previous
                            if ($groupName !== 'main' && $previousGroup !== $groupName) {
                                echo '<li class="menu-title"><span>' . htmlspecialchars(ucwords(str_replace('_', ' ', $groupName))) . '</span></li>';
                            }
                            
                            foreach ($groupMenus as $menuItem) {
                                $isActive = ($currentPage === $menuItem['menu_item_url']);
                                $icon = !empty($menuItem['menu_item_icon']) ? $menuItem['menu_item_icon'] : '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>';
                                
                                echo '<li>';
                                echo '<a href="' . htmlspecialchars($menuItem['menu_item_url']) . '" class="' . ($isActive ? 'active bg-primary text-primary-content' : '') . '">';
                                echo $icon;
                                echo htmlspecialchars($menuItem['menu_item_label']);
                                echo '</a>';
                                echo '</li>';
                            }
                            
                            $previousGroup = $groupName;
                        }
                        
                        // Fallback: If no menu items found, show basic menu
                        if (empty($menuGroups)) {
                            // Log for debugging
                            error_log("No menu items found for role: " . htmlspecialchars($role ?? 'unknown'));
                            echo '<li><a href="dashboard.php">Dashboard</a></li>';
                            echo '<li><a href="profile.php">Profile</a></li>';
                        }
                        ?>
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
                                <span><?php echo htmlspecialchars($username); ?></span><span class="text-blue-500"><?php echo htmlspecialchars($role); ?></span>
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

            <!-- Page Loader (Skeleton) -->
            <div id="pageLoader" class="flex-1 p-4 lg:p-6">
                <div class="space-y-6 animate-pulse">
                    <!-- Header Skeleton -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="skeleton h-10 w-48"></div>
                        <div class="skeleton h-10 w-32"></div>
                    </div>
                    
                    <!-- Filter/Controls Skeleton -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="flex flex-wrap gap-4">
                                <div class="skeleton h-12 w-48"></div>
                                <div class="skeleton h-12 w-64"></div>
                                <div class="skeleton h-12 w-32"></div>
                                <div class="skeleton h-12 w-24"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card/Stats Skeletons -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="skeleton h-6 w-32 mb-2"></div>
                                <div class="skeleton h-8 w-24 mb-2"></div>
                                <div class="skeleton h-4 w-20"></div>
                            </div>
                        </div>
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="skeleton h-6 w-32 mb-2"></div>
                                <div class="skeleton h-8 w-24 mb-2"></div>
                                <div class="skeleton h-4 w-20"></div>
                            </div>
                        </div>
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="skeleton h-6 w-32 mb-2"></div>
                                <div class="skeleton h-8 w-24 mb-2"></div>
                                <div class="skeleton h-4 w-20"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table/Content Skeleton -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body">
                            <div class="skeleton h-8 w-64 mb-4"></div>
                            <div class="space-y-3">
                                <div class="skeleton h-12 w-full"></div>
                                <div class="skeleton h-12 w-full"></div>
                                <div class="skeleton h-12 w-full"></div>
                                <div class="skeleton h-12 w-full"></div>
                                <div class="skeleton h-12 w-full"></div>
                                <div class="skeleton h-12 w-full"></div>
                            </div>
                            <div class="flex justify-center gap-2 mt-4">
                                <div class="skeleton h-10 w-10"></div>
                                <div class="skeleton h-10 w-10"></div>
                                <div class="skeleton h-10 w-10"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content (Hidden initially) -->
            <main id="pageContent" class="flex-1 p-4 lg:p-6 hidden">

