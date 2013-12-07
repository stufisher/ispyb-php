        <h1>Shipments for <?php echo $prop ?></h1>

        <p class="help">This page shows a list of shipments associated with the currently selected proposal</p>

        <p class="help">In order to register your samples you need to create a shipment. Shipments contain dewars, dewar contain containers, and containers individual samples. These can be created sequentially by viewing a particular shipment</p>

        <div class="ra"><a class="add" href="/shipment/add" title="Create a new shipment">Add Shipment</a></div>

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
                        <td>
                            <a class="view" href="/shipment/sid/<?php echo $r['SHIPPINGID'] ?>" title="View Shipment">View Shipment</a>
                            <?php if ($r['LCOUT'] && $r['LCRET']): ?>
                            <a class="label" href="/pdf/sid/<?php echo $r['SHIPPINGID'] ?>" title="Print Shipment Labels">Print Shipment Label</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>