        <div class="error" title="Error in container definition"></div>
        <div class="confirm"></div>

        <h1>Container Allocation for <?php echo $vis ?> on <?php echo $bl ?></h1>

        <p class="help">This page allows you to allocate samples from ISpyB to the beamline sample changer. Drag and drop containers on to the locations on the beamline. Shipments and Dewars can be expanded by clicking on their titles</p>

        <!--
        <div class="add" title="Add New Container">
            <label for="shipment">Shipment:</label>
            <div class="cpadding"><select name="shipment"></select></div>

            
            <label for="dewar">Dewar:</label>
            <div class="cpadding"><select name="dewar"></select></div>

            <label for="title">Container Name:</label>
            <div class="padding"><input type="text" name="title" /></div>
            <div class="samples">
                <table>
                    <thead>
                        <th>#</th>
                        <th>Protein Acronym</th>
                        <th>Sample Name</th>
                        <th>Comment</th>
                        <th></th>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

        <div class="contents">
            <div class="samples">
                <table>
                    <thead>
                        <th>#</th>
                        <th>Protein Acronym</th>
                        <th>Sample Name</th>
                        <th>Comment</th>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
        -->


        <div class="drag_container">

        <div class="c_holder">
            <h1>Assigned Containers: <?php echo $bl ?> Sample Changer</h1>
            <div id="assigned"></div>
            <div class="clear"></div>
        </div>


        <div class="c_holder">
            <!--<span class="r"><button id="add" title="Click to create a new container">Add New Container</button></span>-->
            <h1>Unassigned Containers</h1>
            <div id="unassigned"></div>
            <div class="clear"></div>
        </div>

        </div>
