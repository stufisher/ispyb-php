    <h1><?php echo $f['TITLE'] ?></h1>

    <div class="form border">
        <ul>

            <li>
                <span class="label">Beamline</span>
                <?php echo $f['BEAMLINE'] ?>
            </li>

            <li>
                <span class="label">Start Date/Time</span>
                <?php echo $f['STARTTIME'] ?>
            </li>

            <li>
                <span class="label">Visit ID</span>
                <?php echo $f['VISIT'] ?>
            </li>

            <li>
                <span class="label">System</span>
                <?php echo $f['SYSTEM'] ?>
            </li>

            <li>
                <span class="label">Component & Sub Component</span>
                <?php echo $f['COMPONENT'] ?>
                <?php if ($f['SUBCOMPONENT']) echo ' &raquo; ' . $f['SUBCOMPONENT']; ?>
            </li>

            <li>
                <span class="label">Beamtime Lost</span>
                <?php echo $f['BEAMTIMELOST'] ? 'Yes' : 'No' ?>
            </li>

            <?php if ($f['BEAMTIMELOST']): ?>
            <li class="beamtime_lost">
                <span class="label">Beamtime Lost Between</span>
                <?php echo $f['BEAMTIMELOST_STARTTIME'] ?> -
                <?php echo $f['BEAMTIMELOST_ENDTIME'] ?>
                (<?php echo $f['LOST'] ?>hr)
            </li>
            <?php endif; ?>

            <li>
                <div class="text"><?php echo $f['DESCRIPTION'] ?></div>
                <span class="label">Description</span>

            </li>

            <li>
                <span class="label">Fault Resolved</span>
                <?php echo $f['RESOLVED'] ? 'Yes' : 'No' ?>
            </li>

            <?php if ($f['RESOLVED']): ?>
            <li class="resolution">
                <span class="label">End Date/Time</spaN>
                <?php echo $f['ENDTIME'] ?>
            </li>

            <li class="resolution">
                <div class="text"><?php echo $f['RESOLUTION'] ?></div>
                <span class="label">Resolution</span>
            </li>
            <?php endif; ?>

        </ul>
    </div>
