
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
