
    <h1>Visits for <?php echo $months[$c_month] ?> <?php echo $c_year ?></h1>

    <div class="calendar">
    <ul class="links">
        <li><a href="/dc/mon/<?php echo $months[$c_month] ?>/year/<?php echo ($c_year-1) ?>"><?php echo ($c_year-1) ?></a></li>

        <?php if ($c_month == 1): ?>
            <li><a href="/dc/mon/<?php echo $prev_mon ?>/year/<?php echo ($c_year-1) ?>"><?php echo $prev_mon ?></a></li>
        <?php else: ?>
            <li><a href="/dc/mon/<?php echo $prev_mon ?>"><?php echo $prev_mon ?></a></li>
        <?php endif ?>

        <?php if ($c_month == 12): ?>
            <li><a href="/dc/mon/<?php echo $next_mon ?>/year/<?php echo ($c_year+1) ?>"><?php echo $next_mon ?></a></li>
        <?php else: ?>
            <li><a href="/dc/mon/<?php echo $next_mon ?>"><?php echo $next_mon ?></a></li>
        <?php endif ?>

        <li><a href="/dc/mon/<?php echo $months[$c_month] ?>/year/<?php echo ($c_year+1) ?>"><?php echo ($c_year+1) ?></a></li>
    </ul>

    <ul>
    <?php foreach($days as $i => $d): ?>
        <li class="head<?php echo $i < 6 ? '' : ' wend' ?>"><?php echo $d ?></li>
    <?php endforeach ?>

    <?php for($i = 0; $i < ($dim+$first+$rem); $i++): ?>
        <?php if ($i < $first || $i >= $dim+$first): ?>
        <li class="noday"></li>
        <?php else: ?>
        <li<?php echo ($i - $first +1) == date('j') && $c_year == date('Y') && $c_month == date('n') ? ' class="today"' : ''?>>
            <?php echo $i - $first +1 ?>

            <?php if (array_key_exists($i-$first+1, $vbd)): ?>
                <ul>
                <?php foreach ($vbd[($i-$first+1)] as $t => $vi): ?>
                    <li><?php echo $t ?>
                        <ul>
                        <?php foreach ($vi as $v): ?>
                        <li><?php echo $v['BL'] ?> - <a href="/dc/visit/<?php echo $v['VIS'] ?>"><?php echo $v['VIS'] ?></a></li>
                        <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
        <?php endif; ?>

    <?php endfor; ?>

    </ul>

    <div class="clear"></div>
    </div>
