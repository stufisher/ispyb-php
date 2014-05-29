    <div id="add_pdb">
        <div class="form">
        <form id="ap">
            <ul>
                <li class="clearfix">
                    <label>File:
                        <input type="file" name="pdb_file" />
                    </label>
                </li>

                <li class="clearfix">
                    <label>PDB Code:
                        <input type="text" name="pdb_code" />
                    </label>
                </li>

                <li class="clearfix">
                    <label>Existing PDB:
                        <select name="existing_pdb"></select>
                    </label>
                </li>

                <li>
                    <label>Progress:
                        <div class="progress"></div>
                    </label>
                </li>
            </ul>
        </form>
        </div>
    </div>

    <h1>View Protein</h1>

    <p class="help">This page shows details for the selected protein and a list of samples which make use of it</p>

    <div class="form">
        <ul>
            <li>
                <span class="label">Name</span>
                <span class="name"><?php echo $prot['NAME'] ?></span>
            </li>

            <li>
                <span class="label">Acronym</span>
                <span class="acronym"><?php echo $prot['ACRONYM'] ?></span>
            </li>

            <li class="clearfix reorder">
                <div class="seq text"><?php echo $prot['SEQUENCE'] ?></div>
                <span class="label">Sequence</span>
            </li>

            <li>
                <span class="label">Molecular Mass</span>
                <span class="mass"><?php echo $prot['MOLECULARMASS'] ?></span>
            </li>

            <li class="clearfix">
                <span class="label">Associated PDB Files<br />
                    <button class="add">Add PDB File</button>
                </span>
                <span class="pdb floated">
                    <ul class="visits clearfix"></ul>
                </span>
            </li>

        </ul>

    </div>

    <h1>Samples using this Protein</h1>

    <div class="table">
        <table class="robot_actions samples">
            <thead>
                <tr>
                    <th>Id</th>
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




    <h1>Data Collections for this Protein</h1>

    <p class="help">This table shows all data collections for the selected protein. They can be further filtered by clicking the filter buttons below</p>

    <div class="tabs">

        <ul>
            <li><a href="#data">Data Collections</a></li>
            <li><a href="#edge">Edge Scans</a></li>
            <li><a href="#mca">Fluorescence Spectra</a></li>
        </ul>


        <div id="data">
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
        </div>

        <div id="edge" class="table">
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

        <div id="mca" class="table">
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
                                  
    </div>
