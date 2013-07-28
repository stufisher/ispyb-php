
        <h1>Visit <?php echo $visit ?></h1>

        <div class="plot_container">
            <div id="avg_time"></div>
        </div>


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

                <?php foreach ($rows as $r): ?>
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
