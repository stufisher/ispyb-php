        <h1>Breakdown for <?php echo $bag ?>-<?php echo $visit ?></h1>

        <p class="help">This page shows statistics for the selected visit. The plots below show each action and when they happened. Drag to zoom in on the plot. Click the refresh icon to reset the zoom level</p>

        <div class="plot_container border">
            <button name="reset" title="Reset the zoom level back to the full visit">Reset</button>
            <div id="overview"></div>
        </div>

        <div class="plot_container border" title="Drag to zoom in on a specific area">
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

        <div class="plot_container left">
            <div id="visit_pie"></div>
            <p>Breakdown of Total Visit Time</p>
        </div>

        <div class="plot_container right">
            <div id="dc_hist"></div>
            <p>Data Collection Times</p>
        </div>

        <div class="plot_container right">
            <div id="dc_hist2"></div>
            <p>Data Collection No. of Images</p>
        </div>

        <div class="clear"></div>

        <?php if (sizeof($robot)): ?>

        <h1>Robot Errors</h1>

        <div class="table">
            <table class="robot_actions robot">
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

        <div class="table tw">
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


        <?php if (sizeof($calls)): ?>

        <h1>Call Out Log</h1>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Description</th>
                    <th>In Time</th>
                    <th>Home Time</th>
                </tr>
                </thead>
                <?php foreach ($calls as $c): ?>
                <tr>
                    <td><?php echo $c->username ?></td>
                    <td><?php echo $c->logcontent ?></td>
                    <td><?php echo $c->intime ?></td>
                    <td><?php echo $c->hometime ?></td>
                </tr>
                <?php endforeach ?>

            </table>
        </div>

        <?php else: ?>
        <h1>No Call Outs</h1>
        <?php endif ?>

        <?php if (sizeof($ehcs)): ?>

        <h1>EHC Log</h1>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Posted By</th>
                </tr>
                </thead>
                <?php foreach ($ehcs as $e): ?>
                <tr class="log">
                    <td class="la"><?php echo $e->title ?></td>
                    <td><?php echo $e->posteddate ?></td>
                    <td><?php echo $e->postedby ?></td>
                </tr>
                <tr class="logcontent">
                    <td colspan="3" class="la"><?php echo $e->logcontent ?></td>
                </tr>
                <?php endforeach ?>

            </table>
        </div>

        <?php else: ?>
        <h1>No EHC Log</h1>
        <?php endif ?>

