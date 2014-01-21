    <div class="epics">
        <h1></h1>
        <div class="motors"></div>
    </div>

    <h1><?php echo $bl ?> Beamline Status</h1>
    <div class="status pv">
        <div class="pvs"></div>
    </div>


    <h1>EPICS Screens</h1>
    <div class="screens"></div>


    <h1 class="status webcams">Webcams</h1>
    <div class="status webcams">
        <div class="webcam"><img src="/image/cam/bl/<?php echo $bl ?>/n/0" alt="webcam1" /></div>
        <div class="webcam"><img src="/image/cam/bl/<?php echo $bl ?>/n/1" alt="webcam2" /></div>
    </div>


    <h1 class="status oavs">OAV</h1>
    <div class="status oavs">
        <div class="oav"><img src="" alt="oav" /></div>
    </div>


    <h1>GDA Log</h1>

    <div class="log gda">
        <ul></ul>
    </div>


    <h1>User Schedule</h1>

    <div class="table">
        <table class="schedule">
            <thead>
                <tr>
                    <th>Start</th>
                    <th>End</th>
                    <th>Visit</th>
                    <th>Local Contact</th>
                    <th>On Call</th>
                    <th>Type</th>
                </tr>
            </thead>

            <tbody>
            </tbody>
        </table>
    </div>