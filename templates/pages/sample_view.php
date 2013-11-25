    <h1>Sample Details &amp; History</h1>
    <div class="form">
        <ul>

            <li>
                <span class="label">Name</span>
                <span class="name"><?php echo $samp['NAME'] ?></span>
            </li>

            <li>
                <span class="label">Protein Acronym</span>
                <span class="acronym"><?php echo $samp['ACRONYM'] ?></span> [<a href="/sample/proteins/pid/<?php echo $samp['PROTEINID'] ?>">View All</a>]
            </li>

            <li>
                <span class="label">Spacegroup</span>
                <span class="sg"><?php echo $samp['SPACEGROUP'] ?></span>
            </li>

            <li>
                <span class="label">Comment</span>
                <span class="comment"><?php echo $samp['COMMENTS'] ?></span>
            </li>

            <li>
                <span class="label">Snapshots</span>
                <span class="snapshots sample">
                    <?php foreach ($sn as $i => $s): ?>
                        <?php if ($i < 2): ?>
                        <a href="/image/id/<?php echo $s ?>/f/1" rel="lightbox-sn" title="Crystal Snapshot <?php echo ($i+1) ?>"><img src="/image/id/<?php echo $s ?>" alt="Crystal Snapshot <?php echo ($i+1) ?>" /></a>
                        <?php else: ?>
                        <a href="/image/id/<?php echo $s ?>/f/1" rel="lightbox-sn" title="Crystal Snapshot <?php echo ($i+1) ?>"></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!sizeof($sn)): ?>
                        No snapshots available for this sample
                    <?php endif; ?>
                </span>
            </li>

        </ul>
    </div>


    <div class="page_wrap">
        <div class="pages"></div>
        <div class="clear"></div>
    </div>

    <div class="table history">
        <table class="robot_actions history">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Details</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>