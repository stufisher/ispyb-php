    <h1>Project Details</h1>

    <p class="help">This pages shows details and history for the selected project</p>

    <div class="ra"><button class="implicit" title="Click to change between showing explicity added project members and those implied by added proteins or samples">Implicit Project Members</button></div>

    <div class="form">
        <ul>

            <li>
                <span class="label">Name</span>
                <span class="name"><?php echo $proj['TITLE'] ?></span>
            </li>

        </ul>
    </div>

    <h1>Proteins</h1>

    <p class="help">Click on a protein below to show only samples using that particular protein, click again to show all samples</p>

    <div class="table">
        <table class="robot_actions proteins flt">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Acronym</th>
                    <th>Mass</th>
                    <th>Sequence</th>
                    <th>Samples</th>
                    <th>Data Collections</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>


    <h1>Samples</h1>

    <p class="help">Click on a sample below to show only data collections using that particular sample, click again to show all data collections</p>

    <div class="table">
        <table class="robot_actions samples flt">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Protein</th>
                    <th>Spacegroup</th>
                    <th>Comment</th>
                    <th>Shipment</th>
                    <th>Dewar</th>
                    <th>Container</th>
                    <th>Snapshot</th>
                    <th>Data Collections</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>



    <h1>Data Collections</h1>

    <p class="help">This table shows all data collections for the selected project. They can be further filtered by clicking the filter buttons below</p>

    <div class="filter clearfix" title="Click to filter the current list to specified data collection types">
        <ul>
            <li id="fc">Data Collections</li>
            <li id="sc">Screening</li>
            <li id="gr">Grid Scans</li>
            <li id="flag">Favourite</li>
        </ul>
    </div>

    <div class="table">
        <table class="robot_actions dcs">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Template</th>
                    <th>Sample</th>
                    <th>&Omega; Osc (&deg;)</th>
                    <th># Img</th>
                    <th>&lambda; (&#197;)</th>
                    <th>Trans (%)</th>
                    <th>Exp (s)</th>
                    <th>Comment</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>


    <h1>Energy Scans</h1>

    <div class="table">
        <table class="robot_actions energy">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Element</th>
                    <th>Sample</th>
                    <th>Trans (%)</th>
                    <th>Exp (s)</th>
                    <th>Peak</th>
                    <th>Inf</th>
                    <th>Comment</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>


    <h1>Fluorescence Spectra</h1>

    <div class="table">
        <table class="robot_actions mca">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sample</th>
                    <th>Energy (eV)</th>
                    <th>Trans (%)</th>
                    <th>Exp (s)</th>
                    <th>Elements</th>
                    <th>Comment</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
