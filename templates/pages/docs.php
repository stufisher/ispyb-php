    <h1>Tutorials</h1>

    <div class="filter clearfix">
        <ul>
            <?php foreach ($pn as $i => $p): ?>
                <li <?php if ($i == $tut) echo 'class="current"' ?>><a href="/docs/tut/<?php echo ($i+1) ?>"><?php echo $p ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php echo $content ?>