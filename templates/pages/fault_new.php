
    <h1>New Fault Report</h1>

    <form method="post" id="add_fault" enctype="multipart/form-data">

    <div class="form border">
        <ul>

            <li>
                <label>Beamline
                    <span class="small">Beamline where fault occured</span>
                </label>
                <select name="beamline" required></select>
            </li>

            <li>
                <label>Start Date/Time
                    <span class="small">Time the fault started</span>
                </label>
                <input class="half datetime" type="text" name="start" required datetime />
            </li>


            <li>
                <label>Visit ID
                    <span class="small">The visit during which the fault occured</span>
                </label>
                <select name="session" required></select>
            </li>

            <li>
                <label>System
                    <span class="small">The overreaching system responsible for the fault</span>
                </label>
                <select name="system" required></select>
            </li>

            <li>
                <label>Component
                    <span class="small">The component and sub-component at fault</span>
                </label>
                <select name="component" required></select>
                <select name="sub_component" required></select>
            </li>

            <li>
                <label>Beamtime Lost
                <span class="small">Was beamtime lost as a result?</span>
                </label>
                <select name="beamtime_lost" required>
                    <option value='0'>No</option>
                    <option value='1'>Yes</option>
                </select>
            </li>

            <li class="beamtime_lost">
                <label>Beamtime Lost Between
                <span class="small">Time and date between which beamtime was lost</span>
                </label>
                <input class="half" type="text" name="blstart" required />
                <input class="half" type="text" name="blend" required />
            </li>

            <li>
                <label>Summary
                    <span class="small">A short summart of the fault</span>
                </label>
                <input type="text" name="title" required />
            </li>

            <li>
                <label>Description
                    <span class="small">Full description of the fault</span>
                </label>
                <textarea name="desc" required /></textarea>
            </li>

            <li>
                <label>Attachment
                    <span class="small">Attachment to add to the report</span>
                </label>
                <input type="file" name="userfile1" />
            </li>

            <li>
                <label>Assignee
                    <span class="small">An optional assignee for the fault</span>
                </label>
                <input class="half" type="text" name="assignee" />
            </li>


            <li>
                <label>Fault Resolved
                <span class="small">Has the fault been resolved?</span>
                </label>
                <select name="resolved" required>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                    <option value="2">Partially</option>
                </select>
            </li>

            <li class="resolution">
                <label>End Date/Time
                    <span class="small">Time the fault was resolved</span>
                </label>
                <input class="half" type="text" name="end" required/>
            </li>

            <li class="resolution">
                <label>Resolution / Workaround
                    <span class="small">How the fault was resolved</span>
                </label>
                <textarea name="resolution" required></textarea>
            </li>

            <button name="submit" value="1" type="submit" class="submit">Submit Report</button>


        </ul>
    </div>

    </form>

