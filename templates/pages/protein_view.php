    <div id="add_pdb">
        <div class="form">
        <form id="ap">
            <ul>
                <li>
                    <label>File:
                        <input type="file" name="pdb_file" />
                    </label>
                </li>

                <li>
                    <label>PDB Code:
                        <input type="text" name="pdb_code" />
                    </label>
                </li>

                <li>
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