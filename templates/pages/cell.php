    <h1>Unit Cell Search</h1>

    <div class="cell_param data_collection">
        <label>PDB Code: <input type="text" name="pdb" /></label>

        <button name="get_pdb">Get</button>

        <label>a <input type="text" name="a" value="" /></label>
        <label>b <input type="text" name="b" value="" /></label>
        <label>c <input type="text" name="c" value="" /></label>
        <label>&alpha; <input type="text" name="al" value="" /></label>
        <label>&beta; <input type="text" name="be" value="" /></label>
        <label>&gamma; <input type="text" name="ga" value="" /></label>

        <label>Res <input type="text" name="res" /></label>
        <!--<label>SG <input type="text" name="sg" /></label>-->

        <label>Tolerance: <input type="text" name="tol" value="0.01" /></label>

        <button name="lookup">Look Up</button>
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
