    <h1>Add Protein</h1>

    <form method="post" id="add_protein">

    <div class="form">
        <ul>

            <li>
                <span class="label">Name</span>
                <span class="name"><input type="text" name="name" /></span>
            </li>

            <li>
                <span class="label">Acronym</span>
                <span class="acronym"><input type="text" name="acronym" required /></span>
            </li>

            <li>
                <span class="label">Sequence</span>
                <span class="seq"><textarea name="seq" ></textarea></span>
            </li>

            <li>
                <span class="label">Moecular Mass</span>
                <span class="mass"><input type="text" name="mass" /></span>
            </li>

        </ul>

        <button name="submit" value="1" type="submit" class="submit">Add Protein</button>
    </div>

    </form>