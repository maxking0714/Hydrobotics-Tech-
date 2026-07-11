    <section class="footer">
        <div class="footer-content">
            <p>HYDROBOTICS was founded in 2024 to bring smart contracting and logistics solutions to modern businesses.</p>
            <div class="social-links">
                <a href="feed.php">Hydrobotics Social</a>
                <a href="https://www.twitter.com/hydrobotics" target="_blank" rel="noopener noreferrer">Twitter</a>
                <a href="https://www.linkedin.com/company/hydrobotics" target="_blank" rel="noopener noreferrer">LinkedIn</a>
                <a href="https://www.instagram.com/hydrobotics" target="_blank" rel="noopener noreferrer">Instagram</a>
            </div>
        </div>
    </section>

    <script>
        var toggleButton = document.getElementById('menu-toggle');
        var floatingMenu = document.getElementById('floating-menu');
        if (toggleButton && floatingMenu) {
            toggleButton.addEventListener('click', function() {
                floatingMenu.style.display = floatingMenu.style.display === 'flex' ? 'none' : 'flex';
            });
        }
    </script>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
