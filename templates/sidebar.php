    <div id="sidebar">

        <ul class="clearfix">
            <li class="help"><a href="#" title="Click to toggle help on and off">Help</a></li>
            <li class="feedback"><a href="/feedback">Feedback</a></li>

            <li><a href="/">Upcoming Visits</a>
                <?php if ($this->staff): ?>
                <ul>
                    <li><a href="/dc">Calendar</a></li>
                </ul>
                <?php endif; ?>
            </li>
            <li><a href="/cell">Unit Cell Search</a>
                <?php if ($this->staff): ?>
                <ul>
                    <li><a href="/cell/batch">PDB vs Unit Cell</a></li>
                </ul>
                <?php endif; ?>
            </li>

            <li><a href="/proposal">Proposals</a></li>

            <li>
                <span class="current" title="Click to change the currently selected proposal"><?php echo $prop ?></span>
                <?php if ($prop): ?>
                <ul>
                    <li><a href="/proposal/visits">Visits</a></li>

                    <li><a href="/dc/proposal">Calendar</a></li>

                    <li><a href="/samples/proposal">Prepare Experiment</a></li>

                    <li><a href="/shipment">Shipments</a></li>

                    <li><a href="/sample">Samples</a></li>

                    <li><a href="/sample/proteins">Proteins</a></li>

                    <li><a href="/contact">Lab Contacts</a></li>

                    <li><a href="/vstat/proposal">Statistics</a></li>

                    <li><a href="/projects">Projects</a></li>
                </ul>
                <?php endif; ?>
            </li>

        </ul>

        <a class="pull">Menu</a>

    </div>

    <div class="project">
        <p><span class="b">Item:</span> <span class="title"></span></p>
        <p><span class="b">Select a project:</span>
        <select name="pid"></select></p>
    </div>