<div data-role="page" id="visits">

    <div data-role="header" data-theme="b">
        <a href="/samples/bl" data-icon="home" data-iconpos="notext" data-transition="fade">Home</a>
        <h1><?php echo $bl ?> - <?php echo $title ?></h1>
    </div>

    <div data-role="content">
        <div data-role="content">
            <ul data-role="listview">
                <?php foreach ($visits as $v): ?>
                <li><a data-ajax="false" vis="<?php echo $v['VIS'] ?>" class="visit" href="/samples/bl/visit/<?php echo $v['VIS'] ?>"><?php echo $v['VIS'] ?> - <?php echo $v['ST'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</div>
