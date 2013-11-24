        <h1>Shipments for <?php echo $prop ?></h1>

        <div class="ra"><a class="add" href="/shipment/add">Add Shipment</a></div>

        <div class="table">
            <table class="robot_actions">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Creation Date</th>
                        <th>Outgoing Contact</th>
                        <th>Return Contact</th>
                        <th>Status</th>
                        <th>Components</th>
                        <th>Comment</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo $r['SHIPPINGNAME'] ?></td>
                        <td><?php echo $r['CREATED'] ?></td>
                        <td><?php echo $r['LCOUT'] ?></td>
                        <td><?php echo $r['LCRET'] ?></td>
                        <td><?php echo $r['SHIPPINGSTATUS'] ?></td>
                        <td><?php echo $r['DCOUNT'] ?></td>
                        <td><?php echo $r['COMMENTS'] ?></td>
                        <td><a class="view small" href="/shipment/sid/<?php echo $r['SHIPPINGID'] ?>"></a>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>