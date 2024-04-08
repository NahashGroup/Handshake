<div class="card">
    <div class="center">
        <form action="" method="POST">

            <p> Authentication Token </p>
            <input class="center-placeholder" maxlength="64" type="auth_token" name="auth_token"
                placeholder="Your Authentication Token here" style="width: 60%;">

            <figure>
                <figcaption>Please fill in the captcha before accessing the exchange :</figcaption>
                <br>
                <img src="captcha/image.php" width="130" height="35" alt="captcha">
            </figure>

            <input class="center-placeholder" maxlength="4" type="text" name="code" placeholder="Enter the captcha here">

            <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">

            <button type="submit" name="login" style="display: block; margin: 20px auto;">Access the Escrow</button>
        </form>
    </div>
</div>