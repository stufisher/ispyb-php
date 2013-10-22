<!-- Allocate Container -->
<div data-role="page" id="allocation">

    <div data-role="header" data-theme="b" data-position="fixed">
        <a href="/samples/bl" data-icon="home" data-iconpos="notext" data-transition="fade">Home</a>
        <h1><?php echo $bl ?> - <?php echo $title ?></h1>

        <div data-role="navbar">
            <ul>
                <li><a href="#allocation" class="ui-btn-active">Allocate Containers</a></li>
                <li><a href="#registration">Add Container</a></li>
            </ul>
        </div>
    </div>

    <div data-role="content">
        <div class="ui-grid-c">
            <?php for ($i = 0; $i < (($bl == 'i04-1' | $bl == 'i24') ? 9 : 10); $i++): ?>
            <div class="ui-block-<?php echo chr(($i % 4)+97) ?>">
                <div class="pos" data-role="collapsible" class="assigned" data-content-theme="d" data-collapsed="false">
                    <h3>Position <?php echo ($i+1) ?></h3>
                    <ul data-role="listview" data-inset="true" class="blp blp<?php echo ($i+1) ?>"></ul>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div data-role="collapsible-set" id="shipments"></div>

    </div>

</div>


<!-- Register Container -->
<div data-role="page" id="registration">

    <div data-role="header" data-theme="b">
        <a href="/samples/bl" data-icon="home" data-iconpos="notext" data-transition="fade">Home</a>
        <h1><?php echo $bl ?> - <?php echo $title ?></h1>

        <a href="#add_protein" data-icon="plus" data-rel="dialog" data-role="button">Add Protein</a>

        <div data-role="navbar">
            <ul>
                <li><a href="#allocation">Allocate Containers</a></li>
                <li><a href="#registration" class="ui-btn-active">Add Container</a></li>
            </ul>
        </div>
    </div>

    <div data-role="content">

        <a class="submit" href="#" data-role="button" data-theme="b">Save</a>

        <label for="container">Container Name</label>
        <input type="text" name="container" id="container" />

        <label for="pos">Sample Changer Location</label>
        <select name="pos" id="pos">
            <option value="-1"> - </option>
            <?php for ($i = 0; $i < (($bl == 'i04-1' | $bl == 'i24') ? 9 : 10); $i++): ?>
            <option value="<?php echo ($i+1) ?>">Position <?php echo ($i+1) ?></option>
            <?php endfor; ?>
        </select>


        <table>
            <thead>
                <th>#</th>
                <th>Protein Acronym</th>
                <th>Sample Name</th>
                <th>Comment</th>
                <th>Clone</th>
                <th>Delete</th>
            </thead>
            
            <tbody>
                <?php for ($i = 0; $i < 16; $i++): ?>
                <tr>
                    <td><?php echo ($i+1) ?></td>
                    <td><select class="protein" name="p<?php echo ($i+1) ?>"></select></td>
                    <td><input class="sname"  type="text" name="n<?php echo ($i+1) ?>"/></td>
                    <td><input class="comment" type="text" name="c<?php echo ($i+1) ?>"/></td>
                    <td><a class="clone" href="#clone" data-role="button" data-icon="plus">Clone</a></td>
                    <td><a class="delete" href="#delete" data-role="button" data-icon="delete">Delete</a></td>
                </tr>
                <?php endfor; ?>
            </tbody>
            
        </table>
      
        <a class="submit" href="#" data-role="button" data-theme="b">Save</a>
    </div>


    <div data-role="footer" data-position="fixed">
        <div id="virtualKeyboard"></div>
    </div>

</div>


<!-- Error Dialog -->
<div data-role="dialog" id="error">

    <div data-role="header">
        <h1>Container Error</h1>
    </div>
    
    <div data-role="content">
        <p class="message"></p>
        <a href="#" data-role="button" data-rel="back" data-theme="d">Ok</a>
    </div>

</div>



<!-- Assign Container -->
<div data-role="page" id="assign">

    <div data-role="header">
        <h1>Assign Container</h1>
    </div>
    
    <div data-role="content">
        <p>Assign <span class="container"></span> to: </p>
        <fieldset data-role="controlgroup" data-mini="true">
            <?php for ($i = 0; $i < (($bl == 'i04-1' | $bl == 'i24') ? 9 : 10); $i++): ?>
            <input type="radio" name="position" id="pos<?php echo ($i+1) ?>" value="<?php echo ($i+1) ?>" />
            <label for="pos<?php echo ($i+1) ?>">Position <?php echo ($i+1) ?></label>
            <?php endfor; ?>
            
        </fieldset>

        <fieldset class="ui-grid-a">
            <div class="ui-block-a"><a href="#" data-role="button" data-rel="back" data-theme="d">Cancel</a></div>
            <div class="ui-block-b"><a class="submit" href="#" data-role="button" data-theme="b">Ok</a></div>
	    </fieldset>
    </div>

</div>


<!-- Unassign Container -->
<div data-role="page" id="unassign">

    <div data-role="header">
        <h1>Unassign Container</h1>
    </div>
    
    <div data-role="content">
        <p>Unssign <span class="container"></span></p>

        <fieldset class="ui-grid-a">
            <div class="ui-block-a"><a href="#" data-role="button" data-rel="back" data-theme="d">Cancel</a></div>
            <div class="ui-block-b"><a class="submit" href="#" data-role="button" data-theme="b">Ok</a></div>
	    </fieldset>
    </div>

</div>    
    
    
    
<!-- View Container -->    
<div data-role="page" id="view">

    <div data-role="header">
        <a href="#" data-rel="back" data-icon="arrow-l">Back</a>
        <h1>View Container</h1>
    </div>
    
    <div data-role="content">
    
        <h1 class="name"></h1>
        
        <table>
            <thead>
                <th>#</th>
                <th>Protein Acronym</th>
                <th>Sample Name</th>
                <th>Comment</th>
            </thead>
            <tbody>
            </tbody>
        </table>
        
    </div>
    
</div>

    
    
<!-- Add Protein -->   
<div data-role="page" id="add_protein">

    <div data-role="header">
        <h1>Add Protein</h1>
    </div>
    
    <div data-role="content">
        <label for="protein">Protein Acronym</label>
        <input type="text" name="protein" id="protein" />

        <fieldset class="ui-grid-a">
            <div class="ui-block-a"><a href="#" data-role="button" data-rel="back" data-theme="d">Cancel</a></div>
            <div class="ui-block-b"><a class="submit" href="#" data-role="button" data-theme="b">Ok</a></div>
	    </fieldset>
    </div>


    <div data-role="footer" data-position="fixed">
        <div id="virtualKeyboard2"></div>
    </div>

</div>
