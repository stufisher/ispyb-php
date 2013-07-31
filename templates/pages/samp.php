        <h1>Sample Creation &amp; Allocation</h1>

        <div class="error" title="Error in container definition"></div>

        <div class="confirm"></div>

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
                    </th>
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
                    </th>
                    <tbody></tbody>
                </table>
            </div>

        </div>


        <div class="drag_container">

        <div class="data_collection">
            <h1>Assigned Containers: <?php echo $bl ?> Sample Changer</h1>
            <div id="assigned"></div>
            <div class="clear"></div>
        </div>


        <div class="data_collection">
            <span class="r"><button id="add">Add New Container</button></span>
            <h1>Unassigned Containers</h1>
            <div id="unassigned"></div>
            <div class="clear"></div>
        </div>

        </div>
