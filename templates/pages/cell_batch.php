    <h1>Data Collections for PDB Depositions</h1>
    <div class="table">
        <table class="robot_actions">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Release Date</th>
                    <th>PDB BL</th>
                    <th>BL Match</th>
                    <th>Author Match</th>
                    <th>Nearest</th>
                    <th>Beamlines</th>
                    <th>Datasets</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>


    <h1>Stats</h1>
    <p>Results prior to 2010-05 are excluded from the calculated statistics</p>
    
    <div class="plot_container left">
        <div id="visit_pie"></div>
        <p>Breakdown of PDB depositions</p>
    </div>

    <div class="plot_container right">
        <div id="pdbs"></div>
        <div id="pdbs2"></div>
        <p>Depositions by beamline / year (Top: ISpyB, Bottom: PDB)</p>
    </div>

    <div class="clear"></div>


    <h1 class="ph">Process</h1>
    <div class="pdbs data_collection">
        <select name="beamline">
            <option value="">Diamond</option>
            <option value="I02">I02</option>
            <option value="I03">I03</option>
            <option value="I04">I04</option>
            <option value="I04-1">I04-1</option>
            <option value="I24">I24</option>
        </select>

        <label>Tolerance: <input type="text" name="tol" value="0.01" /></label>
        <br />

        <textarea name="pdb_list"></textarea>
        <br />

        <span class="count">0</span> PDBs to process |
        <button name="process">Process</button>
        <span class="processing"></span>
    </div>