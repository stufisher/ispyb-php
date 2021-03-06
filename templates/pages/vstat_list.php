        <h1><?php echo $prop ?>: <?php echo $data[0]['TITLE'] ?></h1>

        <p class="help">This page shows statistics for the currently selected proposal. Click on a particular visit to see statistics for that visit.</p>

        <!--
        <div class="plot_container border right">
            <div id="visit_pie"></div>
            <p>Breakdown of Average Visit Time</p>
        </div>

        <div class="data_collection left">
            <ul>
                <li>Number of Visits :</li>
                <li>Total Hours Allocated: </li>
                <li>Total Hours Remaining: </li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </div>

        <div class="clear"></div>

        <h1>Visit List</h1>
        -->
        <div class="page_wrap clearfix">
            <div class="pages"></div>
        </div>

        <div class="legend"></div>
        <div class="clear"></div>

        <div class="plot_container">
            <div id="visit_breakdown"></div>
        </div>

        <h1>Average For All Visits</h1>

        <div class="plot_wrap clearfix">
            <div class="plot_container left">
                <div id="visit_pie"></div>
                <p>Breakdown of Average Visit Time</p>
            </div>

            <div class="plot_container right">
                <div id="dc_hist"></div>
                <p>Average Data Collections / Hour</p>
            </div>

            <div class="plot_container right">
                <div id="dc_hist2"></div>
                <p>Average Samples Loaded / Hour</p>
            </div>
        </div>

        <!--
        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Visit</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Length</th>
                    <th>Last Data</th>
                    <th>Data</th>
                    <th>Robot</th>
                    <th>Remain</th>
                    <th>Thinking</th>

                </tr>
                </thead>

                <?php foreach (array() as $b => $d): ?>
                <tr>
                    <td><span class="sort"><?php echo $d['ID'] ?></span><a href="/vstat/visit/<?php echo $prop ?>-<?php echo $d['VISIT'] ?>"><?php echo $d['VISIT'] ?></a></td>
                    <td><span class="sort"><?php echo strtotime($d['ST']) ?></span><?php echo $d['ST'] ?></td>
                    <td><span class="sort"><?php echo strtotime($d['EN']) ?></span><?php echo $d['EN'] ?></td>
                    <td><?php echo $d['LEN'] ?></td>
                    <td><?php echo $d['LAST'] ?></td>
                    <td><?php echo $d['DCTIME'] ?></td>
                    <td><?php echo $d['R'] ?></td>
                    <td><?php echo $d['REM'] ?></td>
                    <td><?php echo $d['T'] ?></td>
                </tr>

                <?php endforeach ?>

            </table>
        </div>
        -->
