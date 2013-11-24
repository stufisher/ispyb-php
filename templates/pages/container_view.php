    <h1>Container: <?php echo $cont['NAME'] ?></h1>


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
        <table class="robot_actions samples">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Protein Acronym</th>
                    <th>Name</th>
                    <th>Spacegroup</th>
                    <th>Comment</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>