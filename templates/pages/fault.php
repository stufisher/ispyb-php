    <h1>Fault List</h1>

    <div class="filter">
        <ul>
            <li id="dc">Beamline</li>
            <li id="ed">System</li>
            <li id="fl">Component</li>
            <li id="rb">Subcomponent</li>
        </ul>
    </div>

    <div class="robot">
        <a href="/fault/new">Add Report</a> |
        <a href="/fault/stats">Stats</a>
    </div>

    <div class="clear"></div>

    <div class="search">
        <label>Search: </label><input type="text" name="search" />
    </div>

    <div class="page_wrap">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>

    <table class="robot_actions">
        <thead>
            <tr>
                <th>Title</th>
                <th>Time</th>
                <th>Beamline</th>
                <th>System</th>
                <th>Component</th>
                <th>Sub Component</th>
                <th>Resolved</th>
                <th>Beamtime Lost</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>