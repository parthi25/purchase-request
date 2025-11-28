            </main>
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

