    <h1><span class="title"><?php echo $f['TITLE'] ?></span></h1>

    <div class="form border">
        <ul>

            <li>
                <span class="label">Beamline</span>
                <span class="beamline"><?php echo $f['BEAMLINE'] ?></span>
            </li>

            <li>
                <span class="label">Start Date/Time</span>
                <span class="starttime"><?php echo $f['STARTTIME'] ?></span>
            </li>

            <li>
                <span class="label">Visit ID</span>
                <span class="visit"><?php echo $f['VISIT'] ?></span>
            </li>

            <li>
                <span class="label">System</span>
                <span class="system"><?php echo $f['SYSTEM'] ?></span>
            </li>

            <li>
                <span class="label">Component & Sub Component</span>
                <span class="component"><?php echo $f['COMPONENT'] ?></span>
                <?php if ($f['SUBCOMPONENT']) echo ' &raquo; ' . '<span class="subcomponent">'.$f['SUBCOMPONENT'].'</span>'; ?>
            </li>

            <li>
                <span class="label">Beamtime Lost</span>
                <span class="btl"><?php echo $f['BEAMTIMELOST'] ? 'Yes' : 'No' ?></span>
            </li>

            <li class="beamtime_lost">
                <span class="label">Beamtime Lost Between</span>
                <span class="btl_start"><?php echo $f['BEAMTIMELOST_STARTTIME'] ?></span> -
                <span class="btl_end"><?php echo $f['BEAMTIMELOST_ENDTIME'] ?></span>
                (<span class="lost"><?php echo $f['LOST'] ?></span>hr)
            </li>



            <li>
                <div class="description text"><?php echo $f['DESCRIPTION']->read($f['DESCRIPTION']->size()) ?></div>
                <span class="label">Description</span>

            </li>


            <li>
                <span class="label">Fault Resolved</span>
                <span class="resolved"><?php echo $f['RESOLVED']  == 2 ? 'Partial' : ($f['RESOLVED'] ? 'Yes' : 'No') ?></span>
            </li>

            <li class="fresolved">
                <span class="label">End Date/Time</span>
                <span class="endtime"><?php echo $f['ENDTIME'] ?></span>
            </li>

            <li class="fresolved">
                <div class="resolution text"><?php echo $f['RESOLUTION'] ? $f['RESOLUTION']->read($f['RESOLUTION']->size()) : '' ?></div>
                <span class="label">Resolution</span>
            </li>

        </ul>

        <div class="clear"></div>
    </div>
