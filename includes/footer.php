<?php
/**
 * ClubHub - Shared Footer Component
 * Closes semantic <main>, provides semantic <footer>, and finishes the document.
 */

if (!isset($base_url)) {
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_FILENAME'] ?? '');
    $base_url = preg_match('#(^|/)pages/#i', $script_path) ? '../' : './';
}
?>
</main>

<footer class="site-footer">
    <hr class="footer-divider">
    <p>&copy; <?php echo date('Y'); ?> ClubHub - College Web Development Lab Project.</p>
    <p>All rights reserved. Designed for college club operations, venue booking, and event management.</p>
</footer>

<!-- Phase 3: Client-side Interactive JavaScript -->
<script src="<?php echo e($base_url); ?>assets/js/main.js" defer></script>
<!-- Phase 8: AJAX Live Features & Asynchronous UI Engine -->
<script src="<?php echo e($base_url); ?>assets/js/ajax.js" defer></script>

</body>
</html>
