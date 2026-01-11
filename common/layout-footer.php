                </main>
            </div> 
            <!-- End Page Content Wrapper -->
        </div> 
        <!-- End Drawer Content -->

        <!-- Sidebar -->
        <div class="drawer-side z-50 overflow-visible">
            <label for="my-drawer-4" aria-label="close sidebar" class="drawer-overlay"></label>
            <!-- Sidebar Content -->
            <div id="sidebarContent" class="flex min-h-full flex-col items-start bg-base-200 transition-all duration-300 ease-in-out w-20 overflow-x-hidden hover:w-64 lg:hover:w-64 group/sidebar border-r border-base-300">
                <!-- Sidebar Header -->
                <div class="w-full p-2 border-b border-base-300 flex items-center justify-center min-h-[4rem]">
                     <img src="../assets/brand/jtlogo.png" alt="Purchase Tracker" class="h-8 w-auto object-contain block group-hover/sidebar:block">
                     <!-- Optional: Text Logo can be added here if needed, hidden by default -->
                     <!-- <span class="hidden group-[.sidebar-expanded]:block font-bold text-xl ml-2">PT</span> -->
                </div>
                
                <!-- Sidebar Menu -->
                <ul class="menu w-full grow gap-1 p-2">
                    <?php
                    // Render dynamic menu items from database
                    $previousGroup = '';
                    if (isset($menuGroups) && is_array($menuGroups)) {
                        foreach ($menuGroups as $groupName => $groupMenus) {
                            foreach ($groupMenus as $menuItem) {
                                $isActive = ($currentPage === $menuItem['menu_item_url']);
                                $icon = !empty($menuItem['menu_item_icon']) ? $menuItem['menu_item_icon'] : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linejoin="round" stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor" class="my-1.5 inline-block size-5"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>';
                                
                                echo '<li>';
                                // Tooltip shows by default (collapsed), hidden when expanded
                                echo '<a href="' . htmlspecialchars($menuItem['menu_item_url']) . '" class="flex items-center gap-4 ' . ($isActive ? 'active' : '') . ' tooltip tooltip-right z-50 group-hover/sidebar:tooltip-none sidebar-expanded:tooltip-none" data-tip="' . htmlspecialchars($menuItem['menu_item_label']) . '">';
                                echo '<span class="flex-none">' . $icon . '</span>';
                                // Text: Visibility controlled by CSS - shown when sidebar is expanded or on hover
                                echo '<span class="opacity-0 w-0 overflow-hidden group-hover/sidebar:opacity-100 group-hover/sidebar:w-auto transition-all duration-200 delay-150 whitespace-nowrap font-medium">' . htmlspecialchars($menuItem['menu_item_label']) . '</span>';
                                echo '</a>';
                                echo '</li>';
                            }
                        }
                    }
                    
                    // Fallback
                    if (empty($menuGroups)) {
                        echo '<li><a href="dashboard.php" class="flex items-center gap-4 tooltip tooltip-right z-50 group-hover/sidebar:tooltip-none sidebar-expanded:tooltip-none" data-tip="Dashboard"><span class="flex-none"><svg xmlns="http://www.w3.org/2000/svg" class="my-1.5 inline-block size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg></span><span class="opacity-0 w-0 overflow-hidden group-hover/sidebar:opacity-100 group-hover/sidebar:w-auto transition-all duration-200 delay-150 whitespace-nowrap font-medium">Dashboard</span></a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Non-Critical Scripts - Load at End of Body -->
    
    <!-- Theme Toggle - Inline for Immediate Execution -->
    <script>
        (function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const html = document.documentElement;
            
            // Get saved theme or default to dark
            const savedTheme = localStorage.getItem('theme') || 'black';
            html.setAttribute('data-theme', savedTheme);
            if (themeIcon) {
                themeIcon.textContent = savedTheme === 'black' ? '🌙' : '☀️';
            }
            
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const currentTheme = html.getAttribute('data-theme');
                    const newTheme = currentTheme === 'black' ? 'corporate' : 'black';
                    html.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    if (themeIcon) {
                        themeIcon.textContent = newTheme === 'black' ? '🌙' : '☀️';
                    }
                });
            }
        })();
    </script>

    <!-- Page Loader Script -->
    <script>
        (function() {
            const pageLoader = document.getElementById('pageLoader');
            const pageContent = document.getElementById('pageContent');
            
            // Function to hide loader and show content
            function hideLoader() {
                if (pageLoader) {
                    pageLoader.classList.add('hidden');
                }
                if (pageContent) {
                    pageContent.classList.remove('hidden');
                }
            }
            
            // Check if page is already loaded
            if (document.readyState === 'complete') {
                // Page is already loaded, wait a bit for any async operations
                setTimeout(hideLoader, 300);
            } else {
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        // Wait for window load to ensure all resources are loaded
                        window.addEventListener('load', function() {
                            // Additional small delay to ensure all scripts have executed
                            setTimeout(hideLoader, 200);
                        });
                    });
                } else {
                    // DOM is already ready, wait for window load
                    window.addEventListener('load', function() {
                        setTimeout(hideLoader, 200);
                    });
                }
            }
            
            // Fallback: Hide loader after maximum wait time (3 seconds)
            setTimeout(function() {
                if (pageLoader && !pageLoader.classList.contains('hidden')) {
                    console.warn('Page loader timeout - showing content anyway');
                    hideLoader();
                }
            }, 3000);
            
            // Allow manual trigger to hide loader (for pages with async content)
            window.hidePageLoader = hideLoader;
        })();
    </script>
</body>
</html>

