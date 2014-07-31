
    <p class="help">This page shows all data collections for the selected visit. If the visit is ongoing the page will automatically update as new data is collected. Auto processing results will be displayed</p>

    <?php if ($is_sample): ?>
    <h1>Data Collections for <?php echo $sl ?></h1>
    <?php endif; ?>


    <?php if ($active): ?>
    <h1 class="status"><?php echo $bl ?> Webcams &amp; Beamline Status</h1>
    <div class="status">
        <div class="pvs"></div>

        <div class="webcam"><img src="" alt="webcam1" /></div>
        <div class="webcam"><img src="" alt="webcam2" /></div>
    </div>
    <?php endif ?>




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

