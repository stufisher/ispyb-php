    <h1>Shipment: <span class="title"><?php echo $ship['SHIPPINGNAME'] ?></span></h1>

    <p class="help">This page shows details and contents of the selected shipment. Most parameters can be edited by simply clicking on them.</p>
    <p class="help excl">Shipments need to have an outgoing and return home lab contact before shipment labels can be printed</p>

    <?php if ($ship['LCOUT'] && $ship['LCRET']): ?>
    <div class="ra">
        <?php if ($ship['SHIPPINGSTATUS'] == 'opened'): ?>
            <button name="send">Send to DLS</button>
        <?php endif; ?>

        <a href="/pdf/container/sid/<?php echo $ship['SHIPPINGID'] ?>" class="label" title="Print Shipment Contents">Print Shipment Contents</a>
        <a href="/pdf/sid/<?php echo $ship['SHIPPINGID'] ?>" class="label" title="Print Shipment Labels">Print Shipment Labels</a>
    </div>
    <?php endif; ?>

    <div class="form">
        <ul>

            <li>
                <span class="label">Created</span>
                <span class="created"><?php echo $ship['CREATED'] ?></span>
            </li>

            <li>
                <span class="label">Status</span>
                <span class="stat"><?php echo $ship['SHIPPINGSTATUS'] ?></span>
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
                <span class="label">Safety Level</span>
                <span class="safety"><?php echo $ship['SAFETYLEVEL'] ?></span>
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

            <li class="clearfix reorder">
                <div class="comment text"><?php echo $ship['COMMENTS'] ?></div>
                <span class="label">Comments</span>
            </li>

        </ul>
        <div class="clear"></div>
    </div>


    <h1>Shipment Contents</h1>

    <p class="help">Select a dewar by clicking on the row in the table below. Dewar details are then shown below. Click the + icon to add a container to the selected dewar</p>

    <div class="ra"><button id="add_dewar" title="Add a dewar to this shipment">Add Dewar</button></div>

    <div class="table dewars">
        <table class="robot_actions dewars reflow">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Barcode</th>
                    <th>Facility Code</th>
                    <th>First Experiment</th>
                    <th>Tracking # to</th>
                    <th>Tracking # from</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Containers</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>


    <h1>Dewar Details: <span class="dewar_name"></span></h1>

    <p class="help">This section shows contents and history for the selected dewar. Click the spyglass icon to view the contents of the container</p>

    <div class="left">
        <ul class="containers"></ul>
    </div>


    <div class="right table table-no-margin">
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