    <h1 class="center nou">Today is <?php echo date('l j F Y') ?></h1>

    <h1>Last Visits</h1>
    <div class="data_collection">
        <ul class="latest clearfix">
            <?php echo $visit_listl ?>
        </ul>
    </div>

    <h1>Next Visits</h1>
    <div class="data_collection">
        <ul class="latest clearfix">
        <?php echo $visit_listn ?>
        </ul>
    </div>