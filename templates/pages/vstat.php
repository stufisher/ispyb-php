        <h1>Visit Statistics</h1>

        <div class="plot_container border">
            <div class="pie_holder">
                <div id="pie1"></div>
                <p>mx - <?php echo $c['mx'] ?> visits</p>
            </div>
            <div class="pie_holder">
                <div id="pie2"></div>
                <p>in - <?php echo $c['in'] ?> visits</p>
            </div>
            <div class="pie_holder">
                <div id="pie3"></div>
                <p>sw - <?php echo $c['sw'] ?> visits</p>
            </div>
            <div class="clear"></div>
        </div>

        <div class="pages"></div>
        <div class="legend"></div>
        <div class="clear"></div>

        <div class="plot_container border">
            <div id="visit_breakdown"></div>
        </div>

        <div class="table">
            <table class="robot_actions">
                <thead>
                <tr>
                    <th>BAG</th>
                    <th>Last Visit</th>
                    <th>Num Visits</th>
                    <th>Avg Visit Length</th>
                    <th>Startup</th>
                    <th>Data</th>
                    <th>Energy Scans</th>
                    <th>Robot</th>
                    <th>Thinking</th>
                    <th>Remain</th>

                </tr>
                </thead>

                <?php foreach ($data as $b => $d): ?>
                <tr>
                    <td>
                        <a href="/vstat/bag/<?php echo $b ?>"><?php echo $b ?></a>
                    </td>
                    <td><span class="sort"><?php echo strtotime($d['LAST']) ?></span><?php echo $d['LAST'] ?></td>
                    <td><?php echo $d['NUM_VIS'] ?></td>
                    <td><?php echo $d['AVGLEN'] ?></td>
                    <td><?php echo $d['AVGSUP'] ?></td>
                    <td><?php echo $d['AVGDC'] ?></td>
                    <td><?php echo $d['ED'] ?></td>
                    <td><?php echo $d['R'] ?></td>
                    <td><?php echo $d['T'] ?></td>
                    <td><?php echo $d['AVGREM'] ?></td>
                </tr>

                <?php endforeach ?>

            </table>
        </div>
