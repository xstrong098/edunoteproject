</div>
        <footer class="bg-light text-center text-muted py-4">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - L'app intelligente per gli appunti</p>
                <p class="small">Versione <?php echo APP_VERSION; ?></p>
            </div>
        </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="assets/js/script.js"></script>

    <?php if (isset($extraScripts)): ?>
        <?php echo $extraScripts; ?>
    <?php endif; ?>
</body>
</html>