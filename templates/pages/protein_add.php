    <!--<div id="add_pdb">
        <div class="form">
        <form id="ap">
            <ul>
                <li>
                    <label>Name:
                        <input type="text" name="name" />
                    </label>
                </li>

                <li>
                    <label>File:
                        <input type="file" name="pdb_file" />
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
    </div>-->

    <h1>Add Protein</h1>

    <form method="post" id="add_protein">

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

            <li>
                <label>PDB
                    <span class="small">Upload new PDB to automatically launch dimple</span>
                </label>
                <span class="pdb">
                    <span class="file">
                        <input type="file" name="new_pdb[]" />
                        <button class="delete">Delete File</button>
                    </span>
                </span>
            </li>

            <li>
                <label>PDB
                    <span class="small">Use existing PDB to automatically launch dimple</span>
                </label>

                <span class="pdb">
                    <select name="existing_pdb" multiple="multiple"></select>
                </span>
            </li>

        </ul>

        <button name="submit" value="1" type="submit" class="submit">Add Protein</button>
    </div>

    </form>