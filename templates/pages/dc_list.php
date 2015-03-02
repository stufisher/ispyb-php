
    <div id="rd">
        <div class="rd_plot"></div>
        Plot of R_d vs frame difference.<br />
        See <a href="http://journals.iucr.org/d/issues/2006/01/00/ba5081/index.html">Diederichs, 2006, Acta D62, 96-101</a>
    </div>

    <div id="distl_full">
        <div class="distl"></div>
    </div>

    <div class="sample_status">
        <div class="key_holder">
            <i class="fa fa-key"></i>

            <div class="key">
                <p>Inner ring: Protein by colour</p>
                <p>Outer ring: Sample status</p>
                <ul>
                    <li>Loaded</li>
                    <li>Screened</li>
                    <li>Auto Indexed</li>
                    <li>Data Collected</li>
                    <li>Auto Integrated</li>
                </ul>
            </div>
        </div>

        <div class="handle">
            <span class="text" title="Hide / Show Sample Changer Overview. Click to Dock">Sample Changer
                <span class="controls">
                    <a href="#" class="clearf"><i class="fa fa-filter"></i> Clear Filter</a>
                </span>
            </span>
        </div>

        <canvas></canvas>

        <div class="ranking">
            <label>
                <input type="checkbox" name="rank" />
                Rank By
            </label>:
            <select name="param">
                <option value="SCRESOLUTION" data-inverted="1" data-check="SC">AI Resolution</option>
                <option value="SCCOMPLETENESS" data-check="SC" data-min="0.85">AI Completeness</option>
                <option value="DCRESOLUTION" data-inverted="1" data-check="DC">AP Resolution</option>
                <option value="DCCOMPLETENESS" data-check="DC" data-min="0.85">AP Completeness</option>
            </select>
        </div>

        <div class="details form">
            <ul>
                <li>
                    <span class="label">Sample:</span>
                    <span class="sname"></span>
                </li>
                <li><span class="label">Protein:</span> <span class="pname"></span></li>
                <!--<li><span class="label">Container:</span> <span class="cname"></span></li>-->
                <li><span class="label">Loaded:</span> <span class="loaded"></span></li>
                <li><span class="label">Screened:</span> <span class="screened"></span></li>
                <li><span class="label">Data:</span> <span class="data"></span></li>
            </ul>
        </div>
    </div>

    <p class="help">This page shows all data collections for the selected visit. If the visit is ongoing the page will automatically update as new data is collected. Auto processing results will be displayed</p>

    <?php if ($is_sample): ?>
    <h1>Data Collections for <?php echo $sl ?></h1>
    <?php endif; ?>


    <?php if ($cams): ?>
    <h1 class="status"><?php echo $bl ?> Webcams &amp; Beamline Status</h1>
    <div class="status">
        <div class="pvs"></div>

        <div class="webcam"><img src="" alt="webcam1" /></div>
        <div class="webcam"><img src="" alt="webcam2" /></div>
    </div>
    <?php endif ?>


    <?php if ($is_visit): ?>
    <div class="ra">
        <a href="/dc/summary/visit/<?php echo $vis ?>" title="Data Summary" class="summary">Summary</a>
        <a href="/vstat/visit/<?php echo $vis ?>" title="Visit Statistics" class="vstat">Visit Stats</a>
        <?php if ($this->staff): ?>
            <a href="/status/bl/<?php echo $bl ?>" title="Beamline Status" class="blstat">Beamline Status</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="filter clearfix" title="Click to filter the current list to specified data collection types">
        <ul>
            <li id="dc">Data Collections</li>
            <li id="fc">Full Data</li>
            <li id="ap">Auto Integrated</li>
            <li id="sc">Screening</li>
            <li id="ed">Edge Scans</li>
            <li id="fl">MCA Spectra</li>
            <li id="rb">Robot Actions</li>
            <li id="ac">Sample Actions</li>
            <li id="flag">Favourite</li>
        </ul>
    </div>


    <?php if ($cams): ?>
    <div class="log border">
        <h1>Log</h1>
        <ul></ul>
    </div>
    <?php endif; ?>


    <div class="time_wrap clearfix">
        <div class="times" title="Click to filter by time"></div>
    </div>


    <div class="search hide" title="Search the current data collections">
        <input type="text" name="search" placeholder="&#xf002;" />
    </div>

    <div class="page_wrap clearfix">
        <div class="per_page">
            <select name="pp">
            <?php foreach (array(5,15,25,50,100,500) as $a): ?>
                <option value="<?php echo $a ?>"><?php echo $a ?></option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="pages pp" title="Click to change pages"></div>
    </div>

    <?php if ($dcid): ?>
    <h1 class="message nou"><a href="/dc/visit/<?php echo $vis ?>">View All Data Collections</a></h1>
    <?php endif; ?>

    <div class="data_collections"></div>

    <div class="page_wrap clearfix" title="Click to change pages">
        <div class="per_page">
            <select name="pp">
            <?php foreach (array(5,15,25,50,100,500) as $a): ?>
                <option value="<?php echo $a ?>"><?php echo $a ?></option>
            <?php endforeach; ?>
            </select>
        </div>

        <div class="pages pp"></div>
    </div>

