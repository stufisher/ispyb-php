        <div class="error" title="Error in container definition"></div>
        <div class="confirm"></div>

        <h1>Container Allocation for <?php echo $vis ?> on <?php echo $bl ?></h1>

        <p class="help">This page allows you to allocate samples from ISpyB to the beamline sample changer. Drag and drop containers on to the locations on the beamline. Shipments and Dewars can be expanded by clicking on their titles</p>


        <div class="drag_container">

        <div class="c_holder">
            <h1>Assigned Containers: <?php echo $bl ?> Sample Changer</h1>
            <div id="assigned"></div>
            <div class="clear"></div>
        </div>

        <div class="ra"><a id="add" href="/shipment/addc/visit/<?php echo $vis ?>" title="Click to create a new container">Add New Container</a></div>

        <div class="c_holder">
            <h1>Unassigned Containers</h1>
            <div id="unassigned"></div>
            <div class="clear"></div>
        </div>

        </div>
