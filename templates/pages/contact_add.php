

    <h1>Add New Home Lab Contact</h1>

    <form method="post" id="add_contact">

    <div class="form">
        <ul>


            <li>
                <label>Card Name
                    <span class="small">Name for the Contact Card</span>
                </label>
                <input type="text" name="cardname" required title="Name of the contact card. Accepts A-z0-9-_" />
            </li>


            <li class="head">Contact Details</li>

            <li>
                <label>Contact Family Name
                    <span class="small">The persons family name</span>
                </label>
                <input type="text" name="familyname" required />
            </li>


            <li>
                <label>Contact First Name
                    <span class="small">The persons first name</span>
                </label>
                <input type="text" name="givenname" required />
            </li>

            <li>
                <label>Contact Phone Number
                    <span class="small">The persons phone number</span>
                </label>
                <input type="text" name="phone" />
            </li>

            <li>
                <label>Contact Email
                    <span class="small">The persons email address</span>
                </label>
                <input type="text" name="email" email />
            </li>


            <li class="head">Contact Laboratory Details</li>

            <li>
                <label>Laboratory Name
                    <span class="small">The contacts laboratory name</span>
                </label>
                <input type="text" name="labname" required />
            </li>

            <li>
                <label>Laboratory Address
                    <span class="small">The contacts laboratory address</span>
                </label>
                <textarea name="address" required></textarea>
            </li>

            <li class="head">
                Dewar Return Details
                <br /><span class="small">The following information is used for each shipment associated with this contact</span>
            </li>

            <li>
                <label>Courier Company
                    <span class="small">Courier company to use to return dewars to home lab</span>
                </label>
                <input type="text" name="courier" required />
            </li>


            <li>
                <label>Courier Account No.
                <span class="small">Courier account number for returning dewars to home lab</span>
                </label>
                <input type="text" name="courieraccount" required />
            </li>

            <li>
                <label>Billing Reference
                    <span class="small">Billing reference to use when returning dewars to home lab</span>
                </label>
                <input type="text" name="billingreference" />
            </li>

            <li>
                <label>Average customs value of dewar
                    <span class="small">The average customs value of a dewar</span>
                </label>
                <input type="text" name="customsvalue" />
            </li>

            <li>
                <label>Average transport value of dewar
                    <span class="small">The average transport value of a dewar</span>
                </label>
                <input type="text" name="transportvalue" />
            </li>

            <button name="submit" value="1" type="submit" class="submit">Add Home Lab Contact</button>


        </ul>
    </div>

    </form>

