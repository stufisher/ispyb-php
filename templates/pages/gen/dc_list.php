
    <div id="distl_full">
        <div class="distl"></div>
    </div>

    <p class="help">This page shows all data collections for the selected visit. If the visit is ongoing the page will automatically update as new data is collected. Auto processing results will be displayed</p>

    <?php if ($is_visit): ?>
    <div class="ra">
        <a href="/vstat/visit/<?php echo $vis ?>" title="Visit Statistics" class="vstat button"><i class="fa fa-bar-chart-o"></i> Visit Stats</a>
    </div>
    <?php endif; ?>


    <?php if ($active): ?>
    <div class="log border">
        <h1>Log</h1>
        <ul></ul>
    </div>
    <?php endif; ?>


    <div class="time_wrap clearfix">
        <div class="times" title="Click to filter by time"></div>
    </div>


    <div class="search hide" title="Search the current data collections">
        <input type="text" name="search" placeholder="&#xf002;" />
    </div>

    <div class="page_wrap clearfix">
        <div class="per_page">
            <select name="pp">
            <?php foreach (array(5,15,25,50,100,500) as $a): ?>
                <option value="<?php echo $a ?>"><?php echo $a ?></option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="pages pp" title="Click to change pages"></div>
    </div>

    <?php if ($dcid): ?>
    <h1 class="message nou"><a href="/dc/visit/<?php echo $vis ?>">View All Data Collections</a></h1>
    <?php endif; ?>

    <div class="data_collections"></div>

    <div class="page_wrap clearfix" title="Click to change pages">
        <div class="per_page">
            <select name="pp">
            <?php foreach (array(5,15,25,50,100,500) as $a): ?>
                <option value="<?php echo $a ?>"><?php echo $a ?></option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="pages pp"></div>
    </div>

