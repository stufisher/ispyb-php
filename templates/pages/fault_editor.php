    <h1>Fault Database Editor</h1>

    <div class="confirm"></div>

    <button name="add_beamline" class="editor">Add Beamline</button>
    <table class="robot_actions beamlines">
        <thead>
            <tr>
                <th>ID</th>
                <th class="span">Name</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>


    <button name="add_system" class="editor">Add System</button>
    <table class="robot_actions systems">
        <thead>
            <tr>
                <th>ID</th>
                <th class="span2">Name</th>
                <th class="span3">Beamlines</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>


    <button name="add_component" class="editor">Add Component</button>
    <table class="robot_actions components">
        <thead>
            <tr>
                <th>ID</th>
                <th class="span3">Name</th>
                <th class="span3">Description</th>
                <th class="span3">Beamlines</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5">Select a system to view components</td></tr>
        </tbody>
    </table>

    <button name="add_subcomponent" class="editor">Add Subcomponent</button>
    <table class="robot_actions subcomponents">
        <thead>
            <tr>
                <th>ID</th>
                <th class="span3">Name</th>
                <th class="span3">Description</th>
                <th class="span3">Beamlines</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5">Select a component to view subcomponents</td></tr>
        </tbody>
    </table>
