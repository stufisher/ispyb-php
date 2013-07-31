
    <h1>New Fault Report</h1>

    <form>

    <div class="form border">
        <ul>

            <li>
                <label>Beamline
                    <span class="small">Beamline where fault occured</span>
                </label>
                <select name="beamline">
                    <option value="i03">i03</option>
                    <option value="i04">i04</option>
                </select>
            </li>

            <li>
                <label>Start Date/Time
                    <span class="small">Time the fault started</span>
                </label>
                <input class="half" type="text" name="start" />
            </li>


            <li>
                <label>Visit ID
                    <span class="small">The visit during which the fault occured</span>
                </label>
                <select name="visit"><select>
            </li>

            <li>
                <label>System
                    <span class="small">The overreaching system responsible for the fault</span>
                </label>
                <select name="system"></select>
            </li>

            <li>
                <label>Component
                    <span class="small">The component and sub-component at fault</span>
                </label>
                <select name="component"></select>
                <select name="sub_component"></select>
            </li>

            <li>
                <label>Beamtime Lost
                <span class="small">Was beamtime lost as a result?</span>
                </label>
                <select name="beamtime_lost">
                    <option value='0'>No</option>
                    <option value='1'>Yes</option>
                </select>
            </li>

            <li class="beamtime_lost">
                <label>Beamtime Lost Between
                <span class="small">Time and date between which beamtime was lost</span>
                </label>
                <input class="half" type="text" name="blstart" />
                <input class="half" type="text" name="blend" />
            </li>

            <li>
                <label>Title
                    <span class="small">Fault title</span>
                </label>
                <input type="text" name="title" />
            </li>

            <li>
                <label>Description
                    <span class="small">Description of the fault</span>
                </label>
                <textarea name="desc" /></textarea>
            </li>


            <li>
                <label>Fault Resolved
                <span class="small">Has the fault been resolved?</span>
                </label>
                <select name="resolved">
                    <option value='0'>No</option>
                    <option value='1'>Yes</option>
                </select>
            </li>

            <li class="resolution">
                <label>End Date/Time
                    <span class="small">Time the fault was resolved</span>
                </label>
                <input class="half" type="text" name="end" />
            </li>

            <li class="resolution">
                <label>Resolution
                    <span class="small">How the fault was resolved</span>
                </label>
                <textarea name="resolution"></textarea>
            </li>

            <button type="submit">Submit Report</button>


        </ul>
    </div>

    </form>

