
        <htmlpageheader name="header1">
            <div class="header ca small">
            <?php echo $info['VISIT'] ?> on <?php echo $info['BEAMLINENAME'] ?> at <?php echo $info['ST'] ?>
            </div>
        </htmlpageheader>

        <sethtmlpageheader name="header1" value="on" show-this-page="1" />

        <htmlpagefooter name="footer1">
            <div class="header ca small">
                Page {PAGENO} of {nbpg}
            </div>
        </htmlpagefooter>

        <sethtmlpagefooter name="footer1" value="on" show-this-page="1" />

        <table class="small border">
            <thead>
                <tr class="head">
                    <th>Image Prefix</th>
                    <th>Run No.</th>
                    <th>No. Images</th>
                    <th>Resolution</th>
                    <th>Wavelength</th>
                    <th>Osc Range</th>
                    <th>Space Group</th>
                    <th>Unit Cell</th>
                    <th>Completeness</th>
                    <th>Rmerge</th>
                    <th>Processed Resolution</th>
                    <th>Comments</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($dcs as $i => $d): ?>
                <tr <?php echo $d['NUMBEROFIMAGES'] < 10 ? 'class="screen"' : '' ?>>
                    <td><?php echo $d['IMAGEPREFIX'] ?></td>
                    <td><?php echo $d['DATACOLLECTIONNUMBER'] ?></td>
                    <td><?php echo $d['NUMBEROFIMAGES'] ?></td>
                    <td><?php echo $d['RESOLUTION'] ?></td>
                    <td><?php echo number_format($d['WAVELENGTH'],2) ?></td>
                    <td><?php echo number_format($d['AXISRANGE'],2) ?></td>
                    <td><?php echo $d[''] ?></td>
                    <td><?php echo $d[''] ?></td>
                    <td><?php echo $d[''] ?></td>
                    <td><?php echo $d[''] ?></td>
                    <td><?php echo $d[''] ?></td>
                    <td><?php echo $d['COMMENTS'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

