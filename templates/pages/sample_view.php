    <h1>Sample Details</h1>

    <p class="help">This pages shows details and history for the selected sample</p>

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

            <?php if ($samp['CONTAINERID']): ?>
            <li>
                <span class="label">Container</span>
                <span class="cont"><a href="/shipment/cid/<?php echo $samp['CONTAINERID'] ?>"><?php echo $samp['CONTAINER'] ?></a></span>
            </li>
            <?php endif; ?>

            <?php if ($samp['DEWAR']): ?>
            <li>
                <span class="label">Dewar</span>
                <span class="dew"><?php echo $samp['DEWAR'] ?></span>
            </li>
            <?php endif; ?>

            <?php if ($samp['SHIPPINGID']): ?>
            <li>
                <span class="label">Shipment</span>
                <span class="ship"><a href="/shipment/sid/<?php echo $samp['SHIPPINGID'] ?>"><?php echo $samp['SHIPMENT'] ?></a></span>
            </li>
            <?php endif; ?>

            <li>
                <span class="label">Snapshots</span>
                <span class="snapshots sample">
                    <?php foreach ($sn as $i => $s): ?>
                        <?php if ($i < 4): ?>
                            <a href="/image/id/<?php echo $s[0] ?>/f/1/n/<?php echo $s[1] ?>" rel="lightbox-sn" title="Crystal Snapshot <?php echo ($i+1) ?>"><img src="/image/id/<?php echo $s[0] ?>/n/<?php echo $s[1] ?>" alt="Crystal Snapshot <?php echo ($i+1) ?>" /></a>
                        <?php else: ?>
                            <a href="/image/id/<?php echo $s[0] ?>/f/1/n/<?php echo $s[1] ?>" rel="lightbox-sn" title="Crystal Snapshot <?php echo ($i+1) ?>"></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!sizeof($sn)): ?>
                        No snapshots available for this sample
                    <?php endif; ?>
                </span>
            </li>

        </ul>
    </div>

    <h1>Sample History</h1>
    <span class="r"><a href="/dc/sid/<?php echo $samp['BLSAMPLEID'] ?>">View All Details</a></span>
    <div class="page_wrap clearfix">
        <div class="pages"></div>
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