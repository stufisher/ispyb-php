    <h1>Container: <?php echo $cont['NAME'] ?></h1>

    <p class="help">This page shows the contents of the selected container. Samples can be added and edited by clicking the pencil icon, and removed by clicking the x</p>

    <?php if ($cont['CONTAINERSTATUS'] == 'processing'): ?>
    <p class="message alert">This container is currently assigned and in use on a beamline sample changer. Unassign it to make it editable</p>
    <?php endif; ?>

    <div class="form clearfix puck_wrap">
        <div class="puck" title="Click to jump to a position in the puck">
            <canvas></canvas>
        </div>

        <ul>
            <li>
                <span class="label">Shipment</span>
                <span><a href="/shipment/sid/<?php echo $cont['SHIPPINGID'] ?>"><?php echo $cont['SHIPMENT'] ?></a></span>
            </li>

            <li>
                <span class="label">Dewar</span>
                <span><?php echo $cont['DEWAR'] ?></span>
            </li>
        </ul>
    </div>

    <div class="table sample">
        <table class="robot_actions samples reflow view">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Protein Acronym</th>
                    <th>Name</th>
                    <th>Spacegroup</th>
                    <th>Barcode</th>
                    <th>Comment</th>
                    <th>Data</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>