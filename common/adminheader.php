<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["user_id"]) && $_SESSION["role"] !== "admin") {
        header("Location: ../index.php");
    exit;
}
 ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Tracker</title>
<link href="../assets/css/daisyui@5.css" rel="stylesheet" type="text/css" />
<script src="../assets/js/browser@4.js"></script>
<link href="../assets/css/themes.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="../assets/css/sweetalert2.min.css">
<script src="../assets/js/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="../assets/css/flatpickr.min.css">
<script src="../assets/js/flatpickr.min.js"></script>
<link rel="shortcut icon" href="/p_r/assets/brand/favicon.ico" type="image/x-icon">
</head>
<body>
    <nav class="navbar bg-base-100 shadow-md px-4 py-3">
        <!-- Logo/Brand -->
        <div class="flex-1">
            <a class="flex items-center gap-2 text-xl font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="hidden sm:inline">Purchase Tracker</span>
            </a>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="flex-none hidden lg:flex items-center gap-4">
            <!-- Status Counters -->
            <div class="flex items-center gap-3 bg-base-200 rounded-lg px-4 py-2">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-error"></div>
                    <span>Open</span>
                    <span id="openCount" class="badge badge-error badge-sm font-bold">0</span>
                </div>
                <div class="divider divider-horizontal m-0"></div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-warning"></div>
                    <span>In Progress</span>
                    <span id="inprogressCount" class="badge badge-warning badge-sm font-bold">0</span>
                </div>
                <div class="divider divider-horizontal m-0"></div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-success"></div>
                    <span>Closed</span>
                    <span id="closeCount" class="badge badge-success badge-sm font-bold">0</span>
                </div>
            </div>
            
            <!-- Theme Toggle -->
            <button id="themeToggle" class="btn btn-ghost btn-square">
                <span id="themeIcon" class="text-xl">🌙</span>
            </button>
            
            <!-- User Menu -->
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost px-3">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                            <span id="usernameInitial" class="text-sm font-bold">?</span>
                        </div>
                    </div>
                    <span id="username" class="ml-2 hidden md:inline">User</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <ul tabindex="0" class="dropdown-content menu menu-sm p-2 shadow bg-base-100 rounded-box w-52 mt-1">
                    <li class="menu-title">
                        <span>Account</span>
                    </li>
                    <li>
                        <a href="profile.php" class="flex items-center gap-2 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Profile Settings
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="flex items-center gap-2 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Change Password
                        </a>
                    </li>
                    <li><hr class="my-1"></li>
                    <li>
                        <a class="text-error flex items-center gap-2 py-2" href="../update/logout.php">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Mobile Menu Button -->
        <div class="flex-none lg:hidden">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </div>
                <ul tabindex="0" class="dropdown-content menu menu-sm p-2 shadow bg-base-100 rounded-box w-64 mt-1">
                    <!-- Status Counters -->
                    <li class="menu-title">
                        <span>Purchase Status</span>
                    </li>
                    <li>
                        <a class="flex justify-between py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-error"></div>
                                Open
                            </div>
                            <span id="openCountMobile" class="badge badge-error">0</span>
                        </a>
                    </li>
                    <li>
                        <a class="flex justify-between py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-warning"></div>
                                In Progress
                            </div>
                            <span id="inprogressCountMobile" class="badge badge-warning">0</span>
                        </a>
                    </li>
                    <li>
                        <a class="flex justify-between py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-success"></div>
                                Closed
                            </div>
                            <span id="closeCountMobile" class="badge badge-success">0</span>
                        </a>
                    </li>
                    
                    <li><hr class="my-1"></li>
                    
                    <!-- Theme Toggle -->
                    <li>
                        <a id="themeToggleMobile" class="flex justify-between py-3">
                            <div class="flex items-center gap-2">
                                <span id="themeIconMobile">🌙</span>
                                Theme
                            </div>
                            <span class="badge badge-outline">Toggle</span>
                        </a>
                    </li>
                    
                    <li><hr class="my-1"></li>
                    
                    <!-- User Info -->
                    <li class="menu-title">
                        <span id="usernameMobile">User</span>
                    </li>
                    <li>
                        <a href="profile.php" class="py-2">Profile Settings</a>
                    </li>
                    <li>
                        <a href="profile.php" class="py-2">Change Password</a>
                    </li>
                    <li>
                        <a class="text-error py-2" href="../update/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Demo Content -->
    <!-- <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat bg-base-200 rounded-lg px-6 py-4">
                <div class="stat-figure text-error">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="stat-title">Open Purchases</div>
                <div class="stat-value text-error">5</div>
                <div class="stat-desc">Requires attention</div>
            </div>
            
            <div class="stat bg-base-200 rounded-lg px-6 py-4">
                <div class="stat-figure text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-title">In Progress</div>
                <div class="stat-value text-warning">3</div>
                <div class="stat-desc">Being processed</div>
            </div>
            
            <div class="stat bg-base-200 rounded-lg px-6 py-4">
                <div class="stat-figure text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-title">Completed</div>
                <div class="stat-value text-success">12</div>
                <div class="stat-desc">All tasks completed</div>
            </div>
        </div>
        
        <div class="card bg-base-100 shadow-md">
            <div class="card-body">
                <h2 class="card-title">Purchase Overview</h2>
                <p>This is a demonstration of the improved navigation bar with proper styling for desktop view.</p>
                <div class="card-actions justify-end">
                    <button class="btn btn-primary">View Details</button>
                </div>
            </div>
        </div>
    </main> -->

    <script>
        // Theme management with localStorage
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            html.setAttribute('data-theme', savedTheme);
            
            // Update theme icons
            const themeIcon = document.getElementById('themeIcon');
            const themeIconMobile = document.getElementById('themeIconMobile');
            
            if (savedTheme === 'dark') {
                themeIcon.textContent = '☀️';
                themeIconMobile.textContent = '☀️';
            } else {
                themeIcon.textContent = '🌙';
                themeIconMobile.textContent = '🌙';
            }
        }

        // Theme toggle functionality
        function setupThemeToggle() {
            const themeToggle = document.getElementById('themeToggle');
            const themeToggleMobile = document.getElementById('themeToggleMobile');
            
            function toggleTheme() {
                const html = document.documentElement;
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Update theme icons
                const themeIcon = document.getElementById('themeIcon');
                const themeIconMobile = document.getElementById('themeIconMobile');
                
                if (newTheme === 'dark') {
                    themeIcon.textContent = '☀️';
                    themeIconMobile.textContent = '☀️';
                } else {
                    themeIcon.textContent = '🌙';
                    themeIconMobile.textContent = '🌙';
                }
            }
            
            themeToggle.addEventListener('click', toggleTheme);
            themeToggleMobile.addEventListener('click', toggleTheme);
        }

        // Mock data for demonstration
        const mockData = {
            data: {
                open: 5,
                inprogress: 3,
                close: 12,
                username: "John Doe"
            }
        };

        // Function to update UI with data
        function updateUI(data) {
            const d = data.data;
            
            // Update desktop counts
            document.getElementById('openCount').textContent = d.open;
            document.getElementById('inprogressCount').textContent = d.inprogress;
            document.getElementById('closeCount').textContent = d.close;
            
            // Update mobile counts
            document.getElementById('openCountMobile').textContent = d.open;
            document.getElementById('inprogressCountMobile').textContent = d.inprogress;
            document.getElementById('closeCountMobile').textContent = d.close;
            
            // Update username
            document.getElementById('username').textContent = d.username;
            document.getElementById('usernameMobile').textContent = d.username;
            document.getElementById('usernameInitial').textContent = d.username.charAt(0).toUpperCase();
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            setupThemeToggle();
            
            // For demonstration, using mock data
            // In your actual implementation, you would use:
            fetch('../fetch/status-counts.php')
                .then(res => res.json())
                .then(data => updateUI(data));
            
            // Using mock data for this example
            // setTimeout(() => updateUI(mockData), 500);
        });
    </script>
</body>
</html>