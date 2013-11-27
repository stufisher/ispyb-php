        <h1>Select a Visit</h1>

        <p class="help">Select a visit for which you want to assign samples to the beamline sample changer</p>

        <ul class="visits clearfix">
            <?php foreach($visits as $v): ?>
            <li><a href="/samples/visit/<?php echo $v['VIS'] ?>"><?php echo $v['VIS'] ?></a>: <?php echo $v['BL'] ?> - <?php echo $v['ST'] ?></li>
            <?php endforeach; ?>
        </ul>