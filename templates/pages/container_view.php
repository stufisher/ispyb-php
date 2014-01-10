    <h1>Container: <?php echo $cont['NAME'] ?></h1>

    <p class="help">This page shows the contents of the selected container. Samples can be added and edited by clicking the pencil icon, and removed by clicking the x</p>

    <div class="form">
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
        <table class="robot_actions samples tw">
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