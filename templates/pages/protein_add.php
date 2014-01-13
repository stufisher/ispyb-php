    <h1>Add Protein</h1>

    <form method="post" id="add_protein" enctype="multipart/form-data">

    <div class="form">
        <ul>

            <li>
                <label>Name
                    <span class="small">Name of the protein</span>
                </label>
                <span class="name"><input type="text" name="name" /></span>
            </li>

            <li>
                <label>Acronym
                    <span class="small">Short form of the protein name</span>
                </label>
                <span class="acronym"><input type="text" name="acronym" required /></span>
            </li>


            <li>
                <label>Molecular Mass
                    <span class="small">Mass of the protein in daltons</span>
                </label>
                <span class="mass"><input type="text" name="mass" /></span>
            </li>

            <li>
                <label>Sequence
                    <span class="small">Used to automatically run MrBump</span>
                </label>
                <span class="seq"><textarea name="seq" ></textarea></span>
            </li>

            <li class="clearfix reorder">
                <div class="floated pdb">
                    <span class="file">
                        <input type="file" class="new_pdb" name="new_pdb[]" />
                        <button class="delete">Delete File</button>
                    </span>
                </div>
                <label>PDB
                    <span class="small">Upload a new PDB to automatically launch dimple</span>
                </label>
            </li>

            <li>
                <label>
                    <span class="small">Use an existing PDB to automatically launch dimple</span>
                </label>

                <span class="exist_pdb">
                    <select name="existing_pdb[]" multiple="multiple"></select>
                </span>
            </li>

            <li>
                <label>
                    <span class="small">List of RCSB PDB Codes to use, comma separated</span>
                </label>

                <span class="rcsb_pdb">
                    <input type="text" name="pdb_codes" />
                </span>
            </li>

        </ul>

        <button name="submit" value="1" type="submit" class="submit">Add Protein</button>
    </div>

    </form>