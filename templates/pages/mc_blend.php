
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
        <div class="blended"></div>
    </div>

    <h1>Integrated Data Collections for <?php echo $visit ?></h1>
    <div class="options data_collection">
        <div class="dc_count"><span class="count">0</span> data collections selected</div>

        <label for="rmerge">Rmerge &lt;: </label><input type="text" name="rmerge" />
        <label for="rfrac">Rfrac: </label><input type="text" name="rfrac" value="0.75" />
        <label for="isigi">I/sig(I): </label><input type="text" name="isigi" value="1.5 "/>
        <label for="res">Resolution: </label><input type="text" name="res" />
        <button name="analyse">Blend</button>
    </div>

    <div class="table_wrap">
        <table class="integrated robot_actions">
        <thead>
            <tr>
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


