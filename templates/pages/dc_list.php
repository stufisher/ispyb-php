
    <div id="rd">
        <div class="rd_plot"></div>
        Plot of R_d vs frame difference.<br/>Increasing R_d indicates radiation damage
    </div>

    <p class="help">This page shows all data collections for the selected visit. If the visit is ongoing the page will automatically update as new data is collected. Auto processing results will be displayed</p>

    <?php if ($active): ?>
    <h1 class="status">Beamline Status</h1>
    <div class="status">
        <div class="pvs"></div>

        <div class="webcam"><img src="" alt="webcam1" /></div>
        <div class="webcam"><img src="" alt="webcam2" /></div>
    </div>
    <?php endif ?>

    <div class="filter" title="Click to filter the current list to specified data collection types">
        <ul>
            <li id="dc">Data Collections</li>
            <li id="ed">Edge Scans</li>
            <li id="fl">Fluorescence Spectra</li>
            <li id="rb">Robot Actions</li>
            <li id="ac">Sample Actions</li>
            <li id="flag">Flagged</li>
        </ul>
    </div>

    <?php if ($this->staff): ?>
    <div class="robot">
        <a href="/robot/visit/<?php echo $vis ?>">Robot Stats</a> | <a href="/vstat/bag/<?php echo $vid ?>/visit/<?php echo $vno ?>">Visit Stats</a> | <a href="/status/bl/<?php echo $bl ?>">Beamline Status</a>
    </div>
    <?php endif; ?>

    <div class="clear"></div>


    <div class="log">
        <h1>Log</h1>
        <ul></ul>
    </div>

    <div class="search" title="Search the current data collections">
        <label>Search: </label><input type="text" name="search" />
    </div>

    <div class="page_wrap" title="Click to change pages">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>

    <?php if ($dcid): ?>
    <h1 class="message"><a href="/dc/visit/<?php echo $vis ?>">View All Data Collections</a></h1>
    <?php endif; ?>

    <div class="data_collections"></div>

    <div class="page_wrap" title="Click to change pages">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>

    <div class="clear"></div>

