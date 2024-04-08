<div class="containersquares">
  <div class="boxsquares">
    <div class="contentsquares">
      <h2>Edit Site Informations</h2>
      <form action="" method="POST">

        <input type="text" maxlength="20" placeholder="<?php if (isset($site_name)) { echo encode_html($site_name); } ?>" name="site_name">
        <input type="text" maxlength="200" placeholder="<?php if (isset($site_description)) { echo encode_html($site_description); } ?>"
          name="site_description">
        <input type="text" maxlength="200" placeholder="<?php if (isset($site_keywords)) { echo encode_html($site_keywords); } ?>" name="site_keywords">

        <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
        <input type="submit" name="modify_site_data" value="Edit">
      </form>
    </div>
  </div>
  <div class="boxsquares">
    <div class="contentsquares">
      <h2>Tools</h2>
      <form action="?location=logs" method="POST">
        <button type="submit">Logs <img src="design/img/logs.png" height="20" width="20"></button>
      </form>
      <form action="?location=eye_of_providence" method="POST">
        <button type="submit">Eye Of Providence <img src="design/img/eye.png" height="20" width="20"></button>
      </form>
    </div>
  </div>
  <div class="containersquares">
    <div class="boxsquares">
      <div class="contentsquares">
        <h2>Refresh deposits</h2>
        <form action="" method="POST">
          <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
          <input type="submit" name="check_and_update_exchanges" value="Refresh">
        </form>
      </div>
    </div>
    <div class="containersquares">
      <div class="boxsquares">
        <div class="contentsquares">
          <h2>Close an Escrow request</h2>
          <form action="" method="POST">
            <p><input type="text" maxlength="32" placeholder="exchange" name="exchange"></p>
            <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
            <input type="submit" name="close_escrow" value="Close">
          </form>
        </div>
      </div>
      <div class="containersquares">
        <div class="boxsquares">
          <div class="contentsquares">
            <h2>Complete an Escrow request (Paid)</h2>
            <form action="" method="POST">
              <p><input type="text" maxlength="32" placeholder="exchange" name="exchange"></p>
              <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
              <p>Message to complete the exchange :</p>
              <textarea maxlength='600' placeholder='You can only send 600 characters per message.'
                name='completed_message' width="250px" height="130"></textarea>
              <p>Transfer funds to :</p>
              <select name='role'>
                <option value='0'>Select...</option>
                <option value='vendor'>Seller</option>
                <option value='customer'>Customer</option>
                <option value='admin'>Administrator</option>
              </select>
              <input type="submit" name="complete_escrow" value="Complete">
            </form>
          </div>
        </div>
        <div class="boxsquares">
          <div class="contentsquares">
            <h2>Change the status of an Escrow request (Not paid)</h2>
            <form action="" method="POST">
              <p><input type="text" maxlength="32" placeholder="exchange" name="exchange"></p>
              <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
              <p>New exchange status :</p>
              <select name='escrow_status'>
                <option value='0'>Select...</option>
                <option value='waiting'>Waiting</option>
                <option value='in_progress'>In progress</option>
                <option value='delivered'>Delivered</option>
                <option value='disputed'>Disputed</option>
                <option value='completed'>Completed</option>
              </select>
              <p>Message if "Completed" is selected (empty for null) :</p>
              <textarea maxlength='600' placeholder='You can only send 600 characters per message.'
                name='completed_message' width="250px" height="130"></textarea>
              <input type="submit" name="change_escrow_status" value="Complete">
            </form>
          </div>
        </div>
        <div class="boxsquares">
          <div class="contentsquares">
            <h2>Enable/Disable Escrow requests</h2>
            <form action="" method="POST">
              <select name="add_escrow_case">
                <?php if (isset($add_escrow_case) && $add_escrow_case === "enabled") {
                  echo '<option value="enabled">Enabled</option>';
                  echo '<option value="disabled">Disabled</option>';
                } else {
                  echo '<option value="disabled">Disabled</option>';
                  echo '<option value="enabled">Enabled</option>';
                } ?>
              </select>
              <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
              <input type="submit" name="enable_or_disable_add_escrow_case" value="Enable/Disable">
            </form>
          </div>
        </div>
        <div class="boxsquares">
          <div class="contentsquares">
            <h2>Edit cryptocurrency fees</h2>
            <form action="" method="POST">
              <p> Bitcoin : </p>
              <input type="text" maxlength="6" placeholder="<?php if (isset($bitcoin_fee)) { echo encode_html($bitcoin_fee); } ?>" name="bitcoin_fee">
              <p> Monero : </p>
              <select name="monero_fee">
                <?php
                $options = [
                  "0" => "Default",
                  "1" => "Low",
                  "2" => "Medium",
                  "3" => "High",
                  "4" => "Urgent"
                ];

                if (array_key_exists($monero_fee, $options)) {
                  echo '<option value="' . encode_html($monero_fee) . '">' . encode_html($options[$monero_fee]) . '</option>';
                }

                // Affiche les autres options
                foreach ($options as $value => $label) {
                  if ($value != $monero_fee) {
                    echo '<option value="' . encode_html($value) . '">' . encode_html($label) . '</option>';
                  }
                }
                ?>
              </select>
              <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
              <input type="submit" name="modify_cryptos_fees" value="Edit">
            </form>
          </div>
        </div>
      </div>