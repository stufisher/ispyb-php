
    <script src="/templates/js/unmerged/glmol/Three49custom.js"></script>
    <script src="/templates/js/unmerged/glmol/gunzip.min.js"></script>
    <script src="/templates/js/unmerged/glmol/GLmol.js"></script>
    <script src="/templates/js/unmerged/glmol/MarchingSquares.js"></script>

    <h1 class="no_mobile"><span class="visit"><?php echo $d['VIS']?>: </span><?php echo $d['DIR'] ?><?php echo $d['FT'] ?></h1>

    <p class="help">This page is an interactive map and model viewer. You can rotate the view using left mouse. Mousewheel zooms in and out. You can navigate through residues by pressing space or shift+space. Toggle fullscreen with the icon in the top right. CTRL+ left click to translate</p>

    <textarea wrap="off" id="glmol01_src" style="display:none; width: 100%; height: 8em; overflow:scroll;"></textarea>

    <div class="image_container border" id="map_model">
        <div class="image">
            <div class="fullscreen">
                <a href="#" class="fullscreen" title="Click to go fullscreen"><i class=" fa fa-arrows-alt fa-2x"></i></a>
            </div>

            <div class="status_bar"></div>

            <div class="controls">
                <div class="residues">
                    <div class="navigate">
                        <select name="chain"></select> <select name="residue"></select> <button name="previous">Prev</button> <button name="next">Next</button>
                    </div>
                    <div class="buttons">
                        
                    </div>
                </div>

                <div class="maps">
                    <span class="wrap">Map 1: <span class="value">1.5</span>rms<div class="m1"></div></span>
                </div>

                <div class="mousemode">
                    Mousemode:<br />
                    <label><input type="radio" name="glmol01_mouseMode" value="0" checked="checked" /> Rotate</label></br>
                    <label><input type="radio" name="glmol01_mouseMode" value="1" /> Translate</label></br>
                    <label><input type="radio" name="glmol01_mouseMode" value="2" /> Zoom</label></br>
                    <label><input type="radio" name="glmol01_mouseMode" value="3" /> Slab</label></br>
                </div>

                <!--
                <div class="mousemode">
                    <label><input type="checkbox" name="symmat" value="1" /> Show Symmetry Mates</label>
                </div>
                -->

                <div class="table peaks">
                    <table class="peaks">
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
            </div>

            <div id="glmol01" style="background: #000">
            </div>

        </div>
    </div>

