    <h1>Data Collection Summary for <?php echo $is_visit ? $visit : $prop ?></h1>

    <div class="options data_collection">
        <label>Resolution: <input type="text" name="res" value="1" /></label>
        <label>Rmerge: <input type="text" name="rmerge" value="0.6" /></label>
        <label>Completeness: <input type="text" name="comp" value="1" /></label>
        <label>I/&sigma;(I): <input type="text" name="isigi" value="0.5" /></label>
        <label>Match Spacegroup: <input type="checkbox" name="sg" value="1" /></label>
        <button name="update">Update</button>
    </div>

    <div class="search hide" title="Search the current data collections">
        <input type="text" name="search" placeholder="&#xf002;" />
    </div>

    <div class="page_wrap clearfix">
        <div class="pages"></div>
    </div>

    <div class="table summary">
        <table class="robot_actions summary reflow">
            <thead>
                <tr>
                    <th>Prefix</th>
                    <th>Sample</th>
                    <th>Date</th>
                    <th>Images</th>
                    <th>&Omega; Osc</th>
                    <th>Exposure</th>
                    <th>Transmission</th>
                    <th>Spacegroup</th>
                    <th>Unit Cell</th>
                    <th>Resolution</th>
                    <th>Rmeas</th>
                    <th>Completeness</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>