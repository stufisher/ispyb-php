
    <script src="/templates/js/unmerged/glmol/Three49custom.js"></script>
    <script src="/templates/js/unmerged/glmol/gunzip.min.js"></script>
    <script src="/templates/js/unmerged/glmol/GLmol.js"></script>
    <script src="/templates/js/unmerged/glmol/MarchingSquares.js"></script>

    <h1 class="no_mobile"><span class="visit"><?php echo $d['VIS']?>: </span><?php echo $d['DIR'] ?><?php echo $d['FT'] ?></h1>

    <p class="help">This page is a full scale diffraction image viewer. Mousehweel zooms in and out, drag click to pan around the image. Press &gt; to go to the next image and &lt; the previous.</p>

    <textarea wrap="off" id="glmol01_src" style="display:none; width: 100%; height: 8em; overflow:scroll;"></textarea>

    <div class="image_container border">
        <div class="image">
            <div id="peaks" class="table">
                <table>
                    <thead>
                        <tr>
                            <th>X</th>
                            <th>Y</th>
                            <th>Z</th>
                            <th>Height</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="controls">

            </div>

            <div id="glmol01" style="background: #000"></div>
        </div>
    </div>

