
    <div class="error" title="Error in container definition">
        <p class="message"></p>
    </div>

    <div class="paste">
        <p>Press ctrl-v to paste container contents from the clipboard</p>
        <textarea name="pasted"></textarea>
    </div>

    <div class="confirm"></div>

    <a name="top"></a>
    <h1 class="no_mobile">Add Container</h1>

    <p class="help">This page allows you to add containers to the selected dewar and shipment. If the protein you want to use isnt listed type in a new name and press tab. This will create a new protein</p>

    <form method="post" id="add_container">

    <div class="form clearfix puck_wrap">
        <button class="pf no_mobile" title="Paste container contents from clipboard"><i class="fa fa-paste"></i> Paste from Spreadsheet</button>

        <div class="puck" title="Click to jump to a position in the puck">
            <canvas></canvas>
        </div>

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
                <span class="label">Puck Name</span>
                <span><input type="text" name="container" /></span>
            </li>
        </ul>
    </div>


    <button class="clone_puck" title="Clone entire puck from first sample">Clone Puck</button>
    <button class="clear_puck" title="Clear entire puck">Clear Puck</button>

    <div class="table sample">
        <table class="robot_actions samples form reflow">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Protein Acronym</th>
                    <th>Sample Name</th>
                    <th>Spacegroup</th>
                    <th>Barcode</th>
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
                    <td><select class="sg" name="sg[]"><?php echo $sgs ?></select></td>
                    <td><input class="code" type="text" name="b[]" /></td>
                    <td><input class="comment" type="text" name="c[]" /></td>
                    <td>
                        <button class="clone" title="Clone this sample">Clone Sample</button>
                        <!--<button class="insert" title="Insert Row">Insert Row</button>-->
                        <button class="delete" title="Remove this sample">Delete Sample</button>
                        <span class="top r"><a href="#top">Top</a>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div class="form">
        <button name="submit" value="1" type="submit" class="submit">Add Container</button>
    </div>

    </form>