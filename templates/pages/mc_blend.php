
    <div id="dialog" title="Job Submitted">
        <p>Your job has been submitted</p>
    </div>

    <div id="stats" title="Stats for Blend Run #">
        <div class="table">
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
    </div>

    <p class="help">This page allows you to &quot;Blend&quot; integrated data sets together</p>

    <h1>Blended Data Sets for <?php echo $visit ?></h1>

    <p class="help">This table lists successful blend runs, you can view scaling statistics and download the relevent mtz files</p>

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
            <li title="Reintegrate data sets"><a href="/mc/visit/<?php echo $visit ?>">Integrate</a></li>
            <li title="Blend integrated data sets together" class="current"><a href="/mc/blend/visit/<?php echo $visit ?>">Blend</a></li>
            <li><span class="jobs">0</span> job(s) running</li>
        </ul>
        <div class="clear"></div>
    </div>

    <div class="blended_wrap table">
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

    <p class="help">This table list successful integrations with XIA2. Select which of these data sets to blend by clicking on the relevent row in the table. Then press &quot;Blend&quot;</p>

    <div class="options data_collection">
        <div class="dc_count"><button name="clear">Clear</button> <span class="count">0</span> data collections selected</div>

        <label>Rmerge &lt;: <input type="text" name="rmerge" title="Select all data sets with an Rmerge lower than this value" /></label>
        <label>Rfrac: <input type="text" name="rfrac" value="0.75" title="Set the blend parameter Rfrac, smaller values will include more data" /></label>
        <label>I/sig(I): <input type="text" name="isigi" value="1.5" title="Set the blend parameter IsigI to select which reflections to blend" /></label>
        <label>Resolution: <input type="text" name="res" /></label>
        <label>Spacegroup: <input type="text" name="sg" /></label>
        <button name="blend" title="Click to blend the selected data sets">Blend</button>
    </div>

    <div class="table_wrap table">
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
        <button name="analyse" title="Run blend to anaylse all integrated data sets and generate a dendrogram">Analyse</button>
    </h1>

    <p class="help">Dendrogram for all integrated data sets, drag over the dendrogram to select data sets to blend together</p>

    <div class="dend_wrap">
        <div class="controls">
            Scale X-Axis &nbsp; <div class="slider" title="Change the scale of the dendrogram"></div>
            <label title="Check this box so that consecutive selections are added together">Additive Selection: <input type="checkbox" name="additive" /></label>
        </div>
        <div class="dendrogram"></div>
    </div>

