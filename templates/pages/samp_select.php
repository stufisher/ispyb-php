        <h1>Select a Visit</h1>

        <ul>
            <?php foreach($visits as $v): ?>
            <li><a href="/samples/visit/<?php echo $v['VIS'] ?>"><?php echo $v['VIS'] ?></a>: <?php echo $v['BL'] ?> - <?php echo $v['ST'] ?></li>
            <?php endforeach; ?>
        </ul>