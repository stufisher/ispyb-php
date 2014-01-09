    <h1>Send Feedback</h1>

    <form method="post" id="send_feedback">

    <div class="form">
        <ul>

            <li>
                <label>Your Name</label>
                <input type="text" name="name" value="<?php echo $user ?>" required />
            </li>

            <li>
                <label>Your Email Address</label>
                <input name="email" type="email" value="<?php echo $email ?>" required />
            </li>

            <li>
                <label>Feedback</label>
                <textarea name="feedback" required></textarea>
            </li>

        </ul>

        <div class="button">
        <button name="submit" value="1" type="submit" class="submit">Send Feedback</button>
        </div>

    </div>

    </form>