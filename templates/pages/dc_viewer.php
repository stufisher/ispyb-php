
    <h1 class="dcv_imp"><span class="visit"><?php echo $d['VIS']?>: </span><?php echo $d['DIR'] ?><?php echo $d['FT'] ?></h1>

    <p class="help">This page is a full scale diffraction image viewer. Mousehweel zooms in and out, drag click to pan around the image. Press &gt; to go to the next image and &lt; the previous.</p>

    <div class="data_collection dc_params">
        <ul class="full clearfix">
            <li>Exposure: <?php echo number_format($d['EXPOSURETIME'],3) ?>s</li>
            <li>Transmission: <?php echo number_format($d['TRANSMISSION'],3) ?>%</li>
            <li>Resolution: <?php echo number_format($d['RES'],2) ?>&#197;</li>
            <li>Wavelength: <?php echo number_format($d['LAM'],2) ?>&#197;</li>
            <li>Oscillation: <?php echo number_format($d['AXISRANGE'],2) ?>&deg;</li>
        </ul>
    </div>

    <div class="image_controls border clearfix">

        <div class="im_col">
            <abbr title="Hotkey: B increases, b decreases">B</abbr>rightness: <span id="bval">0</span> <div id="brightness"></div>
        </div>

        <div class="im_col">
            <abbr title="Hotkey: C increases, c decreases">C</abbr>ontrast: <span id="cval">0</span> <div id="contrast"></div>
        </div>

        <div class="im_size im_col">
            <abbr title="Hotkey: Z zooms in, z zooms out">Z</abbr>oom: <span id="zval">0</span>% <div id="zoom"></div>
        </div>

        <div class="im_col toggles">
            <label><abbr title="Hotkey: R toggles resolution rings">R</abbr>esolution Rings: <input type="checkbox" name="res" value="1" /></label>
            <label>Ice Rings: <input type="checkbox" name="ice" value="1" /></label>
            <label><abbr title="Hotkey: I toggles image inversion">I</abbr>nvert: <input type="checkbox" name="invert" value="1" /></label>
        </div>

        <div class="im_num">
            <button name="prev" title="Load previous image">&lt;</button>
            <input type="text" name="num" value="1" title="Load the image with this number" />/<?php echo $d['NUM'] ?>
            <button name="next" title="Load next image">&gt;</button>
        </div>
    </div>

    <div class="image_container border">
        <div class="im_highlight"></div>
        <div class="im_progress"></div>

        <div class="im_cur" title="Zoomed region, click on the main image to select the region to zoom">
            <!--<p>X: <span id="x">0</span>px, <span id="x_mm">0</span>mm</p>
            <p>Y: <span id="y">0</span>px, <span id="y_mm">0</span>mm</p>-->
            <p title="Diffraction resolution at cursor position">Resolution: <span id="res">0</span>&#197;</p>
        </div>

        <div class="yprofile"></div>
        <div class="xprofile"></div>

        <div class="im_zoom">
            <canvas id="im_zoom" width="200" height="100"></canvas>
        </div>

        <div class="image">
            <canvas id="img"></canvas>
        </div>
    </div>

