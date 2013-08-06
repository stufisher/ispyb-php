
        <h1><?php echo $title ?></h1>

        <div class="plot_container border">
            <div id="avg_time"></div>
        </div>


        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Start</th>
                    <th>Visit</th>
                    <th>Beamline</th>
                    <th>Actions</th>
                    <th>Success</th>
                    <th>Avg Time</th>
                    <th>Errors</th>
                    <th>Critical</th>
                    <th>Warnings</th>
                    <th>Epics Fails</th>
                    <th>Command Not Send</th>
                </tr>
                </thead>

                <?php foreach ($visits as $v => $dat): ?>
                <?php if ($dat['st'] == 'Total'): ?>
                <tfoot>
                <?php endif ?>
                <tr>
                    <td><span class="sort"><?php echo strtotime($dat['st']) ?></span><?php echo $dat['st'] ?></td>
                    <td>
                        <a href="/robot/visit/<?php echo $v ?>"><?php echo $v ?></a>
                    </td>

                    <?php foreach (array('bl', 'tot', 'SUCCESS', 'avgt', 'ERROR', 'CRITICAL', 'WARNING', 'EPICSFAIL', 'COMMANDNOTSENT') as $k): ?>
                    <td><?php echo $dat[$k] ?></td>
                    <?php endforeach ?>
                </tr>

                <?php if ($dat['st'] == 'Total'): ?>
                </tfoot>
                <?php endif ?>

                <?php endforeach ?>

            </table>
        </div>

        <h1>Recent Errors</h1>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>Start</th>
                    <th>Beamline</th>
                    <th>Visit</th>
                    <th>Action</th>
                    <th>Time</th>
                    <th>Puck</th>
                    <th>Sample</th>
                    <th>Barcode</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
                </thead>

                <?php foreach ($errors as $r): ?>
                <tr>
                    <td><span class="sort"><?php echo strtotime($r['ST']) ?></span><?php echo $r['ST'] ?></td>
                    <td><?php echo $r['BL'] ?></td>
                    <td><a href="/robot/visit/<?php echo $r['VIS'] ?>"><?php echo $r['VIS'] ?></a></td>
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
