</div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Bootstrap and UI Fixes -->
        <script>
        // Fix dropdown links to ensure they work properly
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure dropdown links work properly
            const dropdownLinks = document.querySelectorAll('.dropdown-menu a');
            dropdownLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Allow the link to work normally
                    // Don't prevent default behavior
                    console.log('Dropdown link clicked:', this.href);
                });
            });
            
            // Fix any Bootstrap dropdown issues
            const dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    // Ensure dropdown works
                    console.log('Dropdown toggle clicked');
                });
            });
        });
        </script>
    </body>
</html>
