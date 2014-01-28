
    <h1 class="calendar_header">Visits for <?php echo $months[$c_month] ?> <?php echo $c_year ?> <?php echo $has_prop ? ('for '.$pr) : '' ?></h1>

    <p class="help">Schedule of visits for the currently selected proposal</p>

    <div class="calendar">
    <ul class="links clearfix">
        <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $months[$c_month] ?>/year/<?php echo ($c_year-1) ?>"><?php echo ($c_year-1) ?></a></li>

        <?php if ($c_month == 1): ?>
            <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $prev_mon ?>/year/<?php echo ($c_year-1) ?>"><?php echo $prev_mon ?></a></li>
        <?php else: ?>
            <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $prev_mon ?>"><?php echo $prev_mon ?></a></li>
        <?php endif ?>

        <?php if ($c_month == 12): ?>
            <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $next_mon ?>/year/<?php echo ($c_year+1) ?>"><?php echo $next_mon ?></a></li>
        <?php else: ?>
            <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $next_mon ?>"><?php echo $next_mon ?></a></li>
        <?php endif ?>

        <li><a href="/cal/<?php echo $has_prop ? 'proposal/' : '' ?>mon/<?php echo $months[$c_month] ?>/year/<?php echo ($c_year+1) ?>"><?php echo ($c_year+1) ?></a></li>
    </ul>

    <ul class="calendar_days">
    <?php for($i = 0; $i < ($dim+$first+$rem); $i++): ?>
        <?php if (!($i < $first || $i >= $dim+$first)): ?>
        <li day="<?php echo $i - $first +1 ?>" class="<?php echo ($i - $first +1) == date('j') && $c_year == date('Y') && $c_month == date('n') ? 'today' : ''?> <?php echo array_key_exists($i-$first+1, $vbd) ? 'event' : 'no_event' ?> <?php echo (time() > strtotime('23:59 '.($i - $first +1).'-'.$c_month.'-'.$c_year)) ? 'past' : '' ?>">
            <span class="day"><?php echo date('D', strtotime(($i - $first +1).'-'.$c_month.'-'.$c_year)) ?></span>
            <span class="number"><?php echo $i - $first +1 ?></span>
        </li>
        <?php endif; ?>
    <?php endfor; ?>
    </ul>

    <ul class="calendar_main clearfix">
    <?php foreach($days as $i => $d): ?>
        <li class="head<?php echo $i < 6 ? '' : ' wend' ?>"><?php echo $d ?></li>
    <?php endforeach ?>

    <?php for($i = 0; $i < ($dim+$first+$rem); $i++): ?>
        <?php if ($i < $first || $i >= $dim+$first): ?>
        <li class="noday"></li>
        <?php else: ?>
        <li day="<?php echo $i - $first +1 ?>" class="<?php echo ($i - $first +1) == date('j') && $c_year == date('Y') && $c_month == date('n') ? 'today' : ''?> <?php echo array_key_exists($i-$first+1, $vbd) ? '': 'no_event' ?>">
            <span class="full"><?php echo date('l', strtotime(($i - $first +1).'-'.$c_month.'-'.$c_year)) ?> </span>
            <?php echo $i - $first +1 ?>
            <span class="full"><?php echo date('F', strtotime(($i - $first +1).'-'.$c_month.'-'.$c_year)) ?> </span>

            <?php if (array_key_exists($i-$first+1, $vbd)): ?>
                <ul>
                <?php foreach ($vbd[($i-$first+1)] as $t => $vi): ?>
                    <li><span class="time"><?php echo $t ?></span>
                        <ul>
                        <?php foreach ($vi as $v): ?>
                        <li class="<?php echo (time() > strtotime('23:59 '.($i - $first +1).'-'.$c_month.'-'.$c_year)) ? 'past' : '' ?>">
                            <?php echo $v['BL'] ?> - <a href="/dc/visit/<?php echo $v['VIS'] ?>"><?php echo $v['VIS'] ?></a>
                            <?php if ($v['LC']): ?>
                              <span class="short"><?php echo $v['LC'] ?></span>
                            <?php endif;?>

                            <?php if ($v['TY']): ?>
                              <span class="type">[<?php echo $v['TY'] ?>]</span>
                            <?php endif;?>

                            <div class="details">
                            <?php if ($v['LCF']): ?>
                              LC: <?php echo $v['LCF'] ?>
                            <?php endif;?>
                            <?php if ($v['OCF']): ?>
                              OC: <?php echo $v['OCF'] ?>
                            <?php endif;?>
                            </div>

                        </li>
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
    </div>
