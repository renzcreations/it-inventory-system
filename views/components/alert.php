<?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-600 text-center text-white" id="error-message">
        <div class="p-1"><?= nl2br(htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8')) ?></div>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php elseif (isset($_SESSION['success'])): ?>
    <div class="bg-green-600 text-center text-white" id="success-message">
        <div class="p-1"><?= nl2br(htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8')) ?></div>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php elseif (isset($_SESSION['warning'])): ?>
    <div class="bg-amber-600 text-center text-white" id="warning-message">
        <div class="p-1"><?= nl2br(htmlspecialchars((string) $_SESSION['warning'], ENT_QUOTES, 'UTF-8')) ?></div>
    </div>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>
<script>
    setTimeout(() => {
        const errorMsg = document.getElementById('error-message');
        if (errorMsg) errorMsg.style.display = 'none';


        const successMsg = document.getElementById('success-message');
        if (successMsg) successMsg.style.display = 'none';


        const warningMsg = document.getElementById('warning-message');
        if (warningMsg) warningMsg.style.display = 'none';
    }, 4000);
</script>
