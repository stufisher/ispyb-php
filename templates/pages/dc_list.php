
    <div id="rd">
        <div class="rd_plot"></div>
        Plot of R_d vs frame difference.
    </div>

    <div id="distl_full">
        <div class="distl"></div>
    </div>

    <p class="help">This page shows all data collections for the selected visit. If the visit is ongoing the page will automatically update as new data is collected. Auto processing results will be displayed</p>

    <?php if ($is_sample): ?>
    <h1>Data Collections for <?php echo $sl ?></h1>
    <?php endif; ?>


    <?php if ($active): ?>
    <h1 class="status"><?php echo $bl ?> Beamline Status</h1>
    <div class="status">
        <div class="pvs"></div>

        <div class="webcam"><img src="" alt="webcam1" /></div>
        <div class="webcam"><img src="" alt="webcam2" /></div>
    </div>
    <?php endif ?>


    <div class="filter clearfix" title="Click to filter the current list to specified data collection types">
        <ul>
            <li id="dc">Data Collections</li>
            <li id="ed">Edge Scans</li>
            <li id="fl">MCA Spectra</li>
            <li id="rb">Robot Actions</li>
            <li id="ac">Sample Actions</li>
            <li id="flag">Favourite</li>
        </ul>

        <?php if ($is_visit): ?>
        <div class="ra">
            <a href="/vstat/bag/<?php echo $vid ?>/visit/<?php echo $vno ?>" title="Visit Statistics" class="vstat">Visit Stats</a>
            <?php if ($this->staff): ?>
                <a href="/status/bl/<?php echo $bl ?>" title="Beamline Status" class="blstat">Beamline Status</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>


    <?php if ($active): ?>
    <div class="log border">
        <h1>Log</h1>
        <ul></ul>
    </div>
    <?php endif; ?>


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

