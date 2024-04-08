<?php if ($add_escrow_case === "enabled") { ?>

<?php if (!isset($_SESSION['auth_token']) && !isset($_GET["join"])) { ?>


    <div class="card">
    <div class="center">
        <p>
        <h3>« This page is designed specifically for sellers, 
            allowing them to submit an Escrow request. 
            It is important to note that only sellers can initiate an Escrow request. »</h3>
        <p>
    </div>
        <form action="" method="POST">
            <div class="center">
                <p>Transaction amount : </p>
                <input class="center-placeholder" maxlength="100" type="text" name="sum" placeholder="Amount (€)"
                    style="width: 200px;">

                <p>Cryptocurrency used : </p>

                <select name="cryptocurrency">
                    <option value="0">Select...</option>
                    <option value="bitcoin">Bitcoin</option>
                    <option value="monero">Monero</option>
                </select>

                <p>Make sure that the refund address matches the address of your wallet for the selected cryptocurrency.</p>
                <input class="center-placeholder" maxlength="106" type="text" name="refund_address"
                    placeholder="Bitcoin or Monero address, depending on the cryptocurrency selected."
                    style="width: 55%;">

                <figure>
                    <figcaption>Fill in this captcha before opening a request :</figcaption>
                    <br>
                    <img src="captcha/image.php" width="130" height="35" alt="captcha">
                </figure>

                <input class="center-placeholder" maxlength="10" type="text" name="code" placeholder="Enter the captcha here" />

                <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

                <p><button type="submit" name="add">Request an Escrow</button></p>
            </div>
        </form>
    </div>

<?php } elseif (!isset($_SESSION['auth_token']) && isset($_GET["join"]) && $exchange_data = $everyone->get_exchange_data($_GET["join"])) { ?>


    <div class="card">
    <div class="center">
        <p>
        <h3>« You have received an Escrow request for the amount of
            <?= encode_html($exchange_data["sum"]) ?>€, to be paid in
            <?= encode_html($exchange_data["cryptocurrency"]) ?>. To accept, please fill in the form below. Once completed, you will be redirected to the appropriate page. »
        </h3>
        <p>
    </div>
        <form action="" method="POST">
            <div class="center">
                <p>Make sure the refund address you provide matches your wallet address 
                    and the cryptocurrency selected by the seller (<?= encode_html($exchange_data["cryptocurrency"]) ?>) :
                </p>
                <input class="center-placeholder" maxlength="106" type="text" name="refund_address"
                    placeholder="Bitcoin or Monero address, depending on the cryptocurrency chosen by the seller."
                    style="width: 55%;">

                <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

                <input type="hidden" name="cryptocurrency" value="<?= encode_html($exchange_data["cryptocurrency"]) ?>">

                <input type="hidden" name="exchange" value="<?= encode_html($exchange_data["exchange"]) ?>">


                <p><button type="submit" name="join">Join Escrow</button></p>
            </div>
        </form>
    </div>

<?php } ?>

<?php } else {
echo '<div class="center"> The Escrow request page is temporarily disabled. We apologise for any inconvenience caused. </div>';
} ?>