
    <div id="dialog" title="Job Submitted">
        <p>You job has been submitted</p>
    </div>

    <h1>Blended Data Sets for <?php echo $visit ?></h1>

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

        <label for="rmerge">Rmerge &lt;: </label><input type="text" name="rmerge" />
        <label for="rfrac">Rfrac: </label><input type="text" name="rfrac" value="0.75" />
        <label for="isigi">I/sig(I): </label><input type="text" name="isigi" value="1.5 "/>
        <label for="res">Resolution: </label><input type="text" name="res" />
        <label for="res">Spacegroup: </label><input type="text" name="sg" />
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
            <label for="additive">Additive Selection:</label> <input type="checkbox" name="additive" />
        </div>
        <div class="dendrogram"></div>
    </div>

