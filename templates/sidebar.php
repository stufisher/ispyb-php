    <div id="sidebar">

        <ul class="clearfix">
            <li class="help"><a href="#" title="Click to toggle help on and off">Help</a></li>
            <li class="feedback"><a href="/feedback">Feedback</a></li>

            <li><a href="/proposal">Proposals</a></li>

            <li>
                <span class="current"><?php echo $prop ? $prop : 'No Proposal' ?></span>
                <?php if ($prop): ?>
                <ul>
                    <li><a href="/dc">View All Data</a></li>

                    <li><a href="/proposal/visits">Visits</a></li>

                    <li><a href="/cal/proposal">Calendar</a></li>

                    <li><a href="/samples/proposal">Prepare Experiment</a></li>

                    <li><a href="/shipment">Shipments</a></li>

                    <li><a href="/sample">Samples</a></li>

                    <li><a href="/sample/proteins">Proteins</a></li>

                    <li><a href="/contact">Lab Contacts</a></li>

                    <li><a href="/vstat/proposal">Statistics</a></li>
                </ul>
                <?php endif; ?>
            </li>

            <li><a href="/projects">Projects</a></li>

            <li><a href="/cell">Unit Cell Search</a>
                <?php if ($this->staff): ?>
                <ul>
                    <li><a href="/cell/batch">RSCB vs ISpyB</a></li>
                </ul>
                <?php endif; ?>
            </li>

        </ul>

    </div>

    <div class="project">
        <p><span class="b">Item:</span> <span class="title"></span></p>
        <p><span class="b">Select a project:</span>
        <select name="pid"></select></p>
    </div>