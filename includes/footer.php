    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fa-solid fa-paper-plane"></i> Planora
            </div>
            <p>Designed with <i class="fa-solid fa-heart" style="color: var(--primary-color);"></i> for travelers around the world.</p>
            <p>&copy; <?php echo date('Y'); ?> Planora. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileToggle = document.getElementById('mobile-toggle');
        const navMenu = document.getElementById('nav-menu');

        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
