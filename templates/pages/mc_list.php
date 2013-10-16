
    <div id="dialog" title="Job Submitted">
        <p>Your job has been submitted to the cluster</p>
    </div>

    <div id="images" title="Image Viewer">
        <iframe name="images"></iframe>
    </div>

    <h1>Data Collections for <?php echo $visit ?></h1>

    <div class="search">
        <span class="count"></span> |
        <label>Search: <input type="text" name="search" /></label> 
        <label>Directory: <select name="dir"></select></label>
    </div>

    <div class="filter">
        <ul>
            <li class="current"><a href="/mc/visit/<?php echo $visit ?>">Integrate</a></li>
            <li><a href="/mc/blend/visit/<?php echo $visit ?>">Blend</a></li>
            <li><span class="jobs">0</span> job(s) running</li>
        </ul>
        <div class="clear"></div>
    </div>

    <div class="options data_collection">
        <label>Select: <button name="all">All</button></label>
        <label>Min # Spots <input type="text" name="minspots" /></label>

        <label>Start Image # <input type="text" name="start" value="0" /></label>
        <label>End Image # <input type="text" name="end" value="0" /></label>
    </div>

    <div class="cell data_collection">
        <div class="autoproc">
            <label>Auto Processed <select name="cells"></select></label>
        </div>

        <label>Spacegroup: <input type="text" name="sg" /></label>

        <label>a <input type="text" name="a" /></label>
        <label>b <input type="text" name="b" /></label>
        <label>c <input type="text" name="c" /></label>
        <label>&alpha; <input type="text" name="alpha" /></label>
        <label>&beta; <input type="text" name="beta" /></label>
        <label>&gamma; <input type="text" name="gamma" /></label>

        <label>High Resolution <input type="text" name="res" /></label>

        <button name="integrate">Integrate</button>
    </div>

    <div class="dc_wrap">
        <div class="data_collections int"></div>
    </div>


