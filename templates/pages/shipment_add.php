
    <div class="terms"></div>

    <h1>Add New Shipment</h1>

    <form method="post" id="add_shipment">

    <div class="form">
        <ul>

            <li>
                <label>Name
                    <span class="small">Name for the shipment</span>
                </label>
                <input type="text" name="shippingname" required />
            </li>

            <li>
                <label>Number of Dewars
                    <span class="small">Number of dewars to automatically create for this shipment</span>
                </label>
                <input type="text" name="dewars" value="1" />
            </li>

            <li class="d clearfix">
                <label>Facility Dewar Codes
                    <span class="small">Unique code for each dewar of the shipment</span>
                </label>
                <div class="floated">
                <span class="fcodes"></span>
                </div>
            </li>

            <li class="d">
                <label>First Experiment
                    <span class="small">First experiment for the dewars in this shipment</span>
                </label>
                <select name="exp"></select>
            </li>

            <li>
                <label>Safety Level
                    <span class="small">The safety level of the shipment</span>
                </label>
                <select name="safety" required>
                    <option value="Green">Green</option>
                    <option value="Yellow">Yellow</option>
                    <option value="Red">Red</option>
                </select>
            </li>

            <li>
                <label>Comments
                    <span class="small">Comment for the shipment</span>
                </label>
                <textarea name="comment"></textarea>
            </li>

            <li>
                <label>Outgoing Lab Contact
                    <span class="small">Lab contact for outgoing transport | <a href="/contact">Add/Edit</a></span>
                </label>
                <select name="lcout" required><?php echo $cards ?></select>
            </li>

            <li>
                <label>Return Lab Contact
                    <span class="small">Lab contact for return transport | <a href="/contact">Add/Edit</a></span>
                </label>
                <select name="lcret" required><?php echo $cards ?></select>
            </li>

            <li>
                <label>Shipping Date
                    <span class="small">Date shipment left lab</span>
                </label>
                <input class="half" type="text" name="shippingdate" />
            </li>


            <li>
                <label>Delivery Date
                    <span class="small">Estimated date of delivery at facility</span>
                </label>
                <input class="half" type="text" name="deliverydate" />
            </li>

            <li>
                <label>Courier Name
                    <span class="small">Courier name for the return shipment</span>
                </label>
                <input type="text" name="couriername" required /> <button name="dls">Use Diamond Account (UK ONLY)</button>
            </li>

            <li>
                <label>Courier Account Number
                <span class="small">Courier account number for return shipment</span>
                </label>
                <input type="text" name="courierno" required />
            </li>

            <button name="submit" value="1" type="submit" class="submit">Add Shipment</button>


        </ul>
    </div>

    </form>

