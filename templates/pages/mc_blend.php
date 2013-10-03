
    <h1>Blend Runs for <?php echo $visit ?></h1>
    <div class="blended"></div>


    <h1>Integrated Data Collections for <?php echo $visit ?></h1>
    <div class="data_collection">
        <label for="rmerge">Rmerge &lt;: </label><input type="text" name="rmerge" />
        <label for="rfrac">Rfrac: </label><input type="text" name="rfrac" value="0.75" />
        <label for="isigi">I/sig(I): </label><input type="text" name="isigi" value="1.5 "/>
        <label for="res">Resolution: </label><input type="text" name="res" />
        <button name="analyse">Blend</button>
    </div>

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


