            </main>
        </div>
    </div>
    
    <!-- Non-Critical Scripts - Load at End of Body -->
    <script src="../assets/js/browser@4.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../common/js/notifications.js"></script>
    <script src="../assets/js/flatpickr.min.js"></script>
    <script src="../assets/js/select2.min.js"></script>
    
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
</body>
</html>

