    <h1>Shipment: <?php echo $ship['SHIPPINGNAME'] ?></h1>


    <div class="form">
        <ul>

            <li>
                <span class="label">Created</span>
                <span class="created"><?php echo $ship['CREATED'] ?></span>
            </li>

            <li>
                <span class="label">Outgoing Lab Contact</span>
                <span class="lcout"><?php echo $ship['LCOUT'] ?></span>
            </li>

            <li>
                <span class="label">Return Lab Contact</span>
                <span class="lcret"><?php echo $ship['LCRET'] ?></span>
            </li>

            <li>
                <span class="label">Courier</span>
                <span class="courier"><?php echo $ship['DELIVERYAGENT_AGENTNAME'] ?></span>
            </li>

            <li>
                <span class="label">Courier Account No.</span>
                <span class="courierac"><?php echo $ship['DELIVERYAGENT_AGENTCODE'] ?></span>
            </li>

            <li>
                <span class="label">Shipping Date</span>
                <span class="shippingdate"><?php echo $ship['DELIVERYAGENT_SHIPPINGDATE'] ?></span>
            </li>

            <li>
                <span class="label">Delivery Date</span>
                <span class="deliverydate"><?php echo $ship['DELIVERYAGENT_DELIVERYDATE'] ?></span>
            </li>

            <li>
                <span class="label">Comments</span>
                <span class="comment text"><?php echo $ship['COMMENTS'] ?></span>
            </li>

        </ul>
        <div class="clear"></div>
    </div>

    <div class="ra"><button id="add_dewar">Add Dewar</button></div>

    <div class="table dewars">
        <table class="robot_actions dewars">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Barcode</th>
                    <th>Tracking # to synchrotron</th>
                    <th>Tracking # from synchrotron</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Containers</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>


    <h1>Details: <span class="dewar_name"></span></h1>

    <div class="border left">
        <ul class="containers"></ul>
    </div>


    <div class="right">
        <table class="robot_actions history">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Location</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>

    </div>

    <div class="clear"></div>