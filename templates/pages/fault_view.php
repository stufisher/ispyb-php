    <h1><span class="title"><?php echo $f['TITLE'] ?></span></h1>

    <div class="form">
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



            <li class="clearfix">
                <div class="description text">
                    <?php echo $f['DESCRIPTION'] ?>
                </div>
                <span class="label">Description</span>

            </li>


            <?php if ($f['ATTACHMENT']): ?>
            <li>

                <?php if ($f['ATTACH_IMAGE']): ?>
                    <div class="image text">
                        <a href="/image/fa/fid/<?php echo $f['FAULTID'] ?>"><img src="/image/fa/fid/<?php echo $f['FAULTID'] ?>" alt="<?php echo $f['ATTACHMENT'] ?>" /></a>
                    </div>
                <?php else: ?>
                    <div class="attachment text">
                        <?php echo $f['ATTACHMENT'] ?>
                        <a href="/image/fa/fid/<?php echo $f['FAULTID'] ?>">Download</a>
                    </div>
                <?php endif; ?>
                <span class="label">Attachment</span>
            </li>
            <?php endif; ?>

            <li>
                <span class="label">Reported By</span>
                <span class="owner"><?php echo $f['REPORTER'] ?></span>
            </li>

            <?php if ($f['ASSIGNEE']): ?>
            <li>
                <span class="label">Assignee</span>
                <span class="assignee"><?php echo $f['ASSIGNEE'] ?></span>
            </li>
            <?php endif; ?>

            <li>
                <span class="label">Fault Resolved</span>
                <span class="resolved"><?php echo $f['RESOLVED']  == 2 ? 'Partial' : ($f['RESOLVED'] ? 'Yes' : 'No') ?></span>
            </li>

            <li class="fresolved">
                <span class="label">End Date/Time</span>
                <span class="endtime"><?php echo $f['ENDTIME'] ?></span>
            </li>

            <li class="fresolved clearfix">
                <div class="resolution text"><?php echo $f['RESOLUTION'] ?></div>
                <span class="label">Resolution</span>
            </li>

            <?php if ($f['ELOGID']): ?>
            <li>
                <span class="label">eLog Entry</span>
                <span class="elogid"><a href="https://elog.pri.diamond.ac.uk/php/elog/cs_logentdtlret.php?log_entry_id=<?php echo $f['ELOGID'] ?>">Go to eLog</a></span>
            </li>
            <?php endif; ?>

        </ul>

        <div class="clear"></div>
    </div>
