        <h1>Breakdown for <?php echo $bag ?>-<?php echo $visit ?></h1>

        <div class="plot_container border">
            <button name="reset"></button>
            <div id="overview"></div>
        </div>

        <div class="plot_container border">
            <div id="avg_time"></div>
        </div>

        <div class="data_collection">
        <ul class="full">
            <li>Started: <?php echo $info['ST'] ?></li>
            <li>Ended: <?php echo $info['EN'] ?></li>
            <li>Beamline: <?php echo $info['BL'] ?></li>
            <li>Length: <?php echo $info['LEN'] ?> hours</li>

            <li>Last Data Collection: <?php echo $last ?></li>

            <li>Number of Data Collections: <?php echo $info['DC_TOT'] ?></li>
            <li>Number of Energy Scans: <?php echo $info['E_TOT'] ?></li>
            <li>Number of Robot Actions: <?php echo $info['R_TOT'] ?></li>
        </ul>

        <div class="clear"></div>

        </div>

        <div class="plot_container border left">
            <div id="visit_pie"></div>
            <p>Breakdown of Total Visit Time</p>
        </div>

        <div class="plot_container border right">
            <div id="dc_hist"></div>
            <p>Data Collection Times</p>
        </div>

        <div class="plot_container border right">
            <div id="dc_hist2"></div>
            <p>Data Collection No. of Images</p>
        </div>

        <div class="clear"></div>

        <?php if (sizeof($robot)): ?>

        <h1>Robot Errors</h1>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Start</th>
                    <th>Action</th>
                    <th>Time</th>
                    <th>Puck</th>
                    <th>Sample</th>
                    <th>Barcode</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
                </thead>

                <?php foreach ($robot as $r): ?>
                <tr>
                    <td><span class="sort"><?php echo strtotime($r['ST']) ?></span><?php echo $r['ST'] ?></td>
                    <td><?php echo $r['ACTIONTYPE'] ?></td>
                    <td><?php echo round($r['TIME'], 1) ?></td>
                    <td><?php echo $r['DEWARLOCATION'] ?></td>
                    <td><?php echo $r['CONTAINERLOCATION'] ?></td>
                    <td><?php echo $r['SAMPLEBARCODE'] ?></td>
                    <td><?php echo $r['STATUS'] ?></td>
                    <td><?php echo $r['MESSAGE'] ?></td>
                </tr>
                <?php endforeach ?>

            </table>
        </div>

        <?php else: ?>
        <h1>No Robot Errors</h1>
        <?php endif ?>

        <?php if (sizeof($fault)): ?>

        <h1>Faults</h1>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Time</th>
                    <th>System</th>
                    <th>Component</th>
                    <th>Sub Component</th>
                    <th>Resolved</th>
                    <th>Beamtime Lost</th>
                </tr>
                </thead>

                <?php foreach ($fault as $f): ?>
                <tr>
                    <td><a href="/fault/fid/<?php echo $f['FAULTID'] ?>"><?php echo $f['TITLE'] ?></a></td>
                    <td><?php echo $f['STARTTIME'] ?></td>
                    <td><?php echo $f['SYSTEM'] ?></td>
                    <td><?php echo $f['COMPONENT'] ?></td>
                    <td><?php echo $f['SUBCOMPONENT'] ?></td>
                    <td><?php echo $f['RESOLVED'] ? ($f['RESOLVED'] == 2 ? 'Partial' : 'Yes') : 'No' ?></td>
                    <td><?php echo $f['BEAMTIMELOST'] ? ('Yes ('.$f['LOST'].'h)') : 'No' ?></td>
                </tr>
                <?php endforeach ?>

            </table>
        </div>

        <?php else: ?>
        <h1>No Faults Reported</h1>
        <?php endif ?>
