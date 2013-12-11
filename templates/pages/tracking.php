
    <div class="header">
        <h2>Dewar Tracking</h2>
    </div>

    <div class="page">
    <?php if ($error): ?>
        <p><?php echo $error ?></p>

    <?php elseif ($submit): ?>
        <p>Dewar Location Recorded</p>
        <meta http-equiv="refresh" content="2;url=/tracking" />

    <?php else: ?>
        <h1 class="title">Dewar</h1>
        <input type="text" />

        <div class="footer">
            <button class="reset">Reset</button>
        </div>

    <?php endif; ?>

    </div>