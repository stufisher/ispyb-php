    <?php if ($this->staff): ?>

    <div class="filter">
        <ul>
            <li id="dc">Data Collections</li>
            <li id="ed">Edge Scans</li>
            <li id="fl">Fluorescence Spectra</li>
            <li id="rb">Robot Actions</li>
        </ul>
    </div>

    <div class="robot">
        <a href="/robot/visit/<?php echo $vis ?>">Robot Stats</a> | <a href="/vstat/bag/<?php echo $vid ?>/visit/<?php echo $vno ?>">Visit Stats</a>
    </div>
    <?php endif; ?>

    <div class="clear"></div>


    <div class="log">
        <h1>Log</h1>
        <ul></ul>
    </div>

    <div class="search">
        <label>Search: </label><input type="text" name="search" />
    </div>

    <div class="page_wrap">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>


    <div class="data_collections"></div>

    <div class="page_wrap">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>

    <div class="clear"></div>

