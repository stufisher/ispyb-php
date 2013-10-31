
    <div id="dialog" title="Job Submitted">
        <p>Your job has been submitted</p>
    </div>

    <div id="stats" title="Stats for Blend Run #">
        <table class="robot_actions">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Overall</th>
                    <th>Inner</th>
                    <th>Outer</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>

    <h1>Blended Data Sets for <?php echo $visit ?></h1>

    <div class="search">
        <label>Results from:
            <select name="user">
                <?php foreach ($users as $i => $u): ?>
                <option value="<?php echo $i ?>"<?php echo $u == phpCAS::getUser() ? ' selected="selected"' : '' ?>><?php echo $u ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="filter">
        <ul>
            <li><a href="/mc/visit/<?php echo $visit ?>">Integrate</a></li>
            <li class="current"><a href="/mc/blend/visit/<?php echo $visit ?>">Blend</a></li>
            <li><span class="jobs">0</span> job(s) running</li>
        </ul>
        <div class="clear"></div>
    </div>

    <div class="blended_wrap">
        <table class="blended_table robot_actions">
        <thead>
            <tr>
                <th>Run</th>
                <th>Files</th>
                <th>Radfrac</th>
                <th>I/&sigma;(I)</th>
                <th>Status</th>
                <th>Spacegroup</th>
                <th>Resolution</th>
                <th>Rmerge</th>
                <th>Completeness</th>
                <th>I/&sigma;(I)</th>
                <th>Multiplicity</th>
                <th>&nbsp;</th>
            </tr>
        </thead>

        <tbody></tbody>
        </table>
    </div>


    <h1>Integrated Data Collections for <?php echo $visit ?></h1>

    <div class="options data_collection">
        <div class="dc_count"><button name="clear">Clear</button> <span class="count">0</span> data collections selected</div>

        <label>Rmerge &lt;: <input type="text" name="rmerge" /></label>
        <label>Rfrac: <input type="text" name="rfrac" value="0.75" /></label>
        <label>I/sig(I): <input type="text" name="isigi" value="1.5 "/></label>
        <label>Resolution: <input type="text" name="res" /></label>
        <label>Spacegroup: <input type="text" name="sg" /></label>
        <button name="blend">Blend</button>
    </div>

    <div class="table_wrap">
        <table class="integrated robot_actions">
        <thead>
            <tr>
                <!--<th>No</th>-->
                <th>Directory</th>
                <th>Prefix</th>
                <th>&Omega; Start</th>
                <th>SG</th>
                <th>Cell</th>
                <th>Rmerge</th>
                <th>Completeness</th>
                <th>High Resolution</th>
            </tr>
        </thead>

        <tbody></tbody>
        </table>
    </div>


    <h1 class="dend_toggle">
        Cluster Analysis for Integrated Date Sets
        <button name="analyse">Analyse</button>
    </h1>

    <div class="dend_wrap">
        <div class="controls">
            Scale X-Axis <div class="slider"></div>
            <label>Additive Selection: <input type="checkbox" name="additive" /></label> 
        </div>
        <div class="dendrogram"></div>
    </div>

