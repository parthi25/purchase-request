            </main>
                </div> 
                <!-- Right Drawer Sidebar -->
                <div class="drawer-side z-50">
                    <label for="right-drawer-toggle" aria-label="close sidebar" class="drawer-overlay"></label>
                    <div class="menu p-4 w-96 min-h-full bg-base-200 text-base-content flex flex-col">
                        <div class="flex justify-between items-center mb-4 border-b border-base-300 pb-2">
                            <h3 class="text-lg font-bold" id="right-drawer-title">Details</h3>
                            <label for="right-drawer-toggle" class="btn btn-sm btn-circle btn-ghost">✕</label>
                        </div>
                        <div id="right-drawer-content" class="flex-1 overflow-y-auto">
                            <!-- Dynamic content loads here -->
                            <div class="flex justify-center items-center h-full text-base-content/50">
                                No content loaded
                            </div>
                        </div>
                    </div>
                </div>
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

