        <h1><?php echo $bag ?>: <?php echo $data[0]['TITLE'] ?></h1>

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

        <div class="pages"></div>
        <div class="legend"></div>
        <div class="clear"></div>

        <div class="plot_container border">
            <div id="visit_breakdown"></div>
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
                    <td><span class="sort"><?php echo $d['ID'] ?></span><a href="/vstat/bag/<?php echo $bag ?>/visit/<?php echo $d['VISIT'] ?>"><?php echo $d['VISIT'] ?></a></td>
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
