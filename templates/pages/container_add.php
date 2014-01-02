
    <div class="error" title="Error in container definition">
        <p class="message"></p>
    </div>

    <div class="confirm"></div>

    <h1>Add Container</h1>

    <p class="help">This page allows you to add containers to the selected dewar and shipment. If the protein you want to use isnt listed type in a new name and press tab. This will create a new protein</p>

    <form method="post" id="add_container">

    <div class="form">
        <ul>
            <li>
                <span class="label">Shipment</span>
                <span><a href="/shipment/sid/<?php echo $dewar['SHIPPINGID'] ?>"><?php echo $dewar['SHIPMENT'] ?></a></span>
            </li>

            <li>
                <span class="label">Dewar</span>
                <span><?php echo $dewar['DEWAR'] ?></span>
            </li>

            <li>
                <span class="label">Container Name</span>
                <span><input type="text" name="container" /></span>
            </li>
        </ul>
    </div>

    <div class="table sample">
        <table class="robot_actions samples form tw">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Protein Acronym</th>
                    <th>Sample Name</th>
                    <th>Spacegroup</th>
                    <th>Comment</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                <?php for ($i = 0; $i < 16; $i++): ?>
                <tr>
                    <td><?php echo ($i+1)?></td>
                    <td><select class="protein" name="p[]"></select>
                    <td><input class="sname" type="text" name="n[]" /></td>
                    <td><select name="sg[]"><?php echo $sgs ?></select>
                    <td><input class="comment" type="text" name="c[]" /></td>
                    <td><button class="clone" title="Clone this sample">Clone Sample</button> <button class="delete" title="Remove this sample">Delete Sample</button>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <button name="submit" value="1" type="submit" class="submit">Add Container</button>

    </form>