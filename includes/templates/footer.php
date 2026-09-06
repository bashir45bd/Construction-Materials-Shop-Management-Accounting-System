<?php
/**
 * Master Footer Template
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);
?>
  </div><!-- /.app-content -->
</main><!-- /.app-main -->
</div><!-- /.app-layout -->

<!-- Toast Notification Floating Container -->
<div id="toast-container"></div>

<!-- Core ERP Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js?v=2.0"></script>
<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
  <?php foreach ($extraScripts as $script): ?>
    <script src="<?= e($script) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
