    <h1>View Protein</h1>

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

            <li>
                <span class="label">Sequence</span>
                <span class="seq"><?php echo $prot['SEQUENCE'] ?></span>
            </li>

            <li>
                <span class="label">Moecular Mass</span>
                <span class="mass"><?php echo $prot['MOLECULARMASS'] ?></span>
            </li>

        </ul>

    </div>

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