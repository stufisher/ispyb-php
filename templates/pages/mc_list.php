
    <h1>Data Collections for <?php echo $visit ?></h1>

    <a href="/mc/blend/visit/<?php echo $visit ?>">Blend Datasets</a>

    <div class="search">
        <label for="search">Search:</label> <input type="text" name="search" /> <label for="dir">Directory: </label><select name="dir"></select>
    </div>
    <div class="count"></div>

    <div><span class="jobs">0</span> job(s) running</div>

    <div class="options data_collection">
        <label for="all">Select </label><button name="all">All</button>
        <label for="minspots">Min # Spots</label><input type="text" name="minspots" />

        <label for="start">Start Image #</label><input type="text" name="start" value="0" />
        <label for="end">End Image #</label><input type="text" name="end" value="0" />
    </div>

    <div class="cell data_collection">
        <div>
            <label for="sgsel">Suggested</label> <select name="suggested"></select>
        </div>

        <label for="sg">Spacegroup</label> <input type="text" name="sg" />

        <label for="a">a</label> <input type="text" name="a" />
        <label for="b">b</label> <input type="text" name="b" />
        <label for="c">c</label> <input type="text" name="c" />
        <label for="alpha">&alpha;</label> <input type="text" name="alpha" />
        <label for="beta">&beta;</label> <input type="text" name="beta" />
        <label for="gamma">&gamma;</label> <input type="text" name="gamma" />

        <label for="res">High Resolution</label><input type="text" name="res" />

        <button name="integrate">Integrate</button>
    </div>

    <div class="data_collections"></div>


