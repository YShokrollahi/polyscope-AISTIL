<!-- Footer -->
<footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> AI-Polyscope. All rights reserved.</p>
        </div>
    </footer>

    <!-- Core JS Files -->
    <script src="assets/js/dashboard.js"></script>
    
    <!-- Page-specific JS -->
    <?php if ($view === 'slides'): ?>
    <script src="assets/js/slide-manager.js"></script>
    <?php elseif ($view === 'multizoom'): ?>
    <script src="assets/js/multizoom-manager.js"></script>
    <?php endif; ?>
    
    <!-- Optional: Additional scripts based on page -->
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>