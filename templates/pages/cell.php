    <h1>Unit Cell Search</h1>

    <p class="help">Use this page to search for data collections corresponding to a unit cell or pdb code. Tolerance is by default set to 1%, if you cant find a corresponding data collection increase this value.</p>
    <p class="help">Results are ordered by &quot;Distance&quot; from the search unit cell parameters</p>

    <div class="cell_param data_collection">
        <label>PDB Code: <input type="text" name="pdb" /></label>

        <button name="get_pdb" title="Retrieve details for the selected PDB code">Get</button>

        <label>a <input type="text" name="a" value="" /></label>
        <label>b <input type="text" name="b" value="" /></label>
        <label>c <input type="text" name="c" value="" /></label>
        <label>&alpha; <input type="text" name="al" value="" /></label>
        <label>&beta; <input type="text" name="be" value="" /></label>
        <label>&gamma; <input type="text" name="ga" value="" /></label>

        <label>Res <input type="text" name="res" title="Resolution limit to match to data collection" /></label>
        <!--<label>SG <input type="text" name="sg" /></label>-->

        <label>Tolerance: <input type="text" name="tol" value="0.01" title="Tolerance for cell parameter search, default 1%" /></label>

        <button name="lookup" title="Search for data collections corresponding to the unit cell supplied">Look Up</button>
    </div>

    <div class="pdb_details_not_found data_collection">
        No PDB found corresponding to that code
    </div>

    <div class="pdb_details data_collection">
        <h1 class="title"></h1>
        <ul>
            <li>Beamline: <span class="beamline"></span></li>
            <li>Date: <span class="date"></span>, Resolution: <span class="res"></span></li>
            <li>Author: <span class="author"></span></li>
            <li>Citation: <span class="citation"></span></li>
        </ul>
        <div class="clear"></div>
    </div>


    <div class="search">
        <span class="count"></span> Data Collections Found
    </div>


    <div class="data_collections">

    </div>
