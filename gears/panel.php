<?php

$status = $exchange_data["status"];

$steps = [
    "waiting" => "Awaiting funding",
    "in_progress" => "Awaiting delivery",
    "delivered" => "Delivery made",
    "completed" => "Paid-up funds"
];

echo '<div class="road-list">'
. '<div class="steps-container">';
foreach ($steps as $step => $label) {

    $is_active = $status === $step;

    echo '<div class="step-wrapper">'
    . '<div class="step' . ($is_active ? ' active' : '') . '"></div>'
    . '<div class="step-label">' . encode_html($label) . '</div>'
    . '</div>';

    if ($step !== array_key_last($steps)) {
        echo '<div class="line"></div>';
    }
}
echo '</div>'
. '</div>';

switch ($status) {
    case "waiting":
        if ($_SESSION['role'] === "customer") {
            $message = "<p>Dear customer, the seller is asking for the sum of " . encode_html($exchange_data["sum"]) . "€ in " . encode_html($exchange_data["cryptocurrency"]) . ".</p> 
                        <p>The total amount to be sent may turn out to be higher than the initial amount planned, due to the cryptocurrency transfer fees that you have to pay.</p>
                        <p> <p>Please send the following EXACT value : " . encode_html($exchange_data["converted_sum"]) . " to the address below : </p>
                        <p> → " . encode_html($exchange_data["deposit_address"]) . " ← </p> 
                        <p>( This address benefits from enhanced security thanks to our service, preventing the seller from immediately accessing the funds without first obtaining your explicit consent. ).</p>
                        <img src='design/img/waiting.gif' width='200' height='200' alt='waiting'>
                        <p>We await receipt of your funds. Please note that the receipt time may vary depending on the current state of the network and the conditions of the blockchain.</p>
                        <p>Current balance available at deposit address : " . encode_html($user->check_crypto_balance($exchange_data["cryptocurrency"], $exchange_data["deposit_address"], $exchange_data["account_index"])) . "</p>";
        } elseif ($_SESSION['role'] === 'vendor') {
            $message = "<p>Dear seller, please forward the following link to your customer:</p> 
                        <p> → ?location=add_escrow&join=" . encode_html($exchange_data["exchange"]) . " ← </p> 
                        <p>This link will give the recipient the opportunity to accept the Escrow conditions and make the corresponding payment.</p> 
                        <p>Once this has been done, the page will automatically update to show the next steps in our Escrow process.</p>
                        <p>Thank you for choosing " . encode_html($site_name) . " for your transactions.</p>";
        }
        break;

    case "in_progress":
        if ($_SESSION['role'] === "customer") {
            $message = "<p>Dear customer, we confirm receipt of your funds and have contacted the seller accordingly.</p> 
                        <p>If there is no sign of life from the seller within 7 days of the following date " . encode_html(convert_date($exchange_data["timestamp"])) . "</p>
                        <p>Then you will receive a full refund by " . encode_html($exchange_data["cryptocurrency"]) . " to the following cryptocurrency address : </p>
                        <p> → " . encode_html($_SESSION['refund_address']) . " ← </p>
                        <p>( Address of the cryptocurrency you provided to our service ).</p>";
        } elseif ($_SESSION['role'] === "vendor") {
            $user->confirm_delivery($token, $exchange_data["exchange"]);
            $message = "<p>Dear seller, we can confirm that your customer's funds are protected with the utmost vigilance within our system.</p> 
                        <p>It is now your responsibility to take the necessary measures.</p> 
                        <p>Please transmit the goods or services due to the customer, then click on the *Confirm delivery* button to finalise the transaction.</p> 
                        <p>If you fail to do so, the customer will receive a full refund within 7 days from the following date : " . encode_html(convert_date($exchange_data["timestamp"])) . ".</p> 
                        <p>We are grateful for the trust you have placed in us.</p>
                         <form action='' method='POST'>
                          <input type='hidden' name='token' value='" . encode_html(sha1(session_id())) . "'>
                          <p><button type='submit' name='confirm_delivery'>Confirm delivery</button></p>
                         </form>";
        }
        break;
    case "delivered":
        if ($_SESSION['role'] === "customer") {
            $user->confirm_receipt($token, $exchange_data["exchange"]);
            $user->report_dispute($token, $exchange_data["exchange"]);
            $message = "<p>Dear customer, the seller has confirmed that he has delivered the goods intended for you.</p> 
                        <p>You have 14 days from the following date : " . encode_html(convert_date($exchange_data["timestamp"])) . ", to confirm receipt of the goods or report a dispute. If no action is taken by you after this time, the funds will automatically be transferred to the seller.</p> 
                        <p>To confirm receipt, please click on the *Confirm receipt* button. If the seller does not respect the conditions of the exchange, please click on *Report a dispute*.</p>
                         <form action='' method='POST'>
                          <input type='hidden' name='token' value='" . encode_html(sha1(session_id())) . "'>
                          <p><button type='submit' name='confirm_receipt'>Confirm receipt</button></p>
                         </form>
                         <form action='' method='POST'>
                          <input type='hidden' name='token' value='" . encode_html(sha1(session_id())) . "'>
                          <p><button type='submit' name='report_dispute'>Report a dispute</button></p>
                         </form>";
        } elseif ($_SESSION['role'] === "vendor") {
            $message = "<p>Dear seller, we are awaiting confirmation of receipt from your customer.</p> 
                        <p>Please remind your customer to transfer the funds, provided that you have of course sent the agreed goods or services.</p> 
                        <p>If your customer does not react or does not release the funds manually, they will automatically be paid to you within 14 days of this date : " . encode_html(convert_date($exchange_data["timestamp"])) . " by our service.</p>";
        }
        break;
    case "disputed":
        $user->send_dispute_message($token, $_SESSION['auth_token'], $exchange_data["exchange"]);
        if ($_SESSION['role'] === "customer") {
            $message = "<p>Dear customer, following your notification of a dispute, you now have access to a direct communication channel between you and the seller.</p> 
                        <p>We also have access to this conversation and can intervene if necessary to arbitrate and deliberate.</p> 
                        <p>We would like to remind you that it is essential to be discerning and serious in your dealings.</p> 
                        <p>If there are no previous messages, you need to initiate the conversation by explaining why you decided to open this dispute.</p> 
                        <p>If you do not react or stop reacting, we will be forced to take a decision in favour of the seller and release the funds in his favour.</p>
                         <div class='display_messages'>" . $user->display_messages($exchange_data["exchange"]) . "</div>";
        } elseif ($_SESSION['role'] === "vendor") {
            $message = "<p>Dear seller, we would like to inform you that a dispute has been reported. As a result, you now have access to a direct communication channel between you and the customer.</p> 
                        <p>We also have access to this conversation and can intervene if necessary to arbitrate and deliberate.</p> 
                        <p>We would like to remind you that it is essential to be discerning and serious in your dealings.</p> 
                        <p>If the customer does not first provide a clear explanation of why the dispute has arisen, the funds will be transferred to you.</p> 
                        <p>If you do not react or stop reacting, we will be obliged to take a decision in favour of the customer and release the funds in his favour.</p>
                         <div class='display_messages'>" . $user->display_messages($exchange_data["exchange"]) . "</div>";
        } elseif ($_SESSION['role'] === "admin") {
            $control->complete_escrow($token);
            $message = "<p>Dear administrator, a dispute has been reported and requires your attention.</p>
                         <div class='display_messages'>" . $user->display_messages($exchange_data["exchange"]) . "</div>
                          <form action='' method='POST'>
                           <input type='hidden' name='token' value='" . encode_html(sha1(session_id())) . "'>
                           <input type='hidden' name='exchange' value='" . encode_html($exchange_data["exchange"]) . "'>
                           <p>Message to end the dispute : </p>
                           <textarea maxlength='600' placeholder='You can only send 600 characters per message.' name='completed_message'  width='250px' height='130'></textarea>
                           <p>Transfer funds to : </p>
                           <select name='role'>
                            <option value='0'>Select...</option>
                            <option value='vendor'>Seller</option>
                            <option value='customer'>Customer</option>
                            <option value='admin'>Administrator</option>
                           </select>
                           <p><button type='submit' name='complete_escrow'>End the dispute</button></p>
                          </form>";
        }
        break;
    case "completed":
        if ($_SESSION['role'] === "customer") {
            $message = "<p>Dear customer, the exchange is now complete.</p> 
                        <p>If you read this message after confirming receipt, it means that the funds will soon be sent to the seller, and you have no further action to take.</p> 
                        <p>We wish you an excellent morning, day or evening, depending on the time of day.</p> 
                        <p>In the event of a dispute being resolved by us and a decision being taken, the message from our department will be as follows : " . encode_html($exchange_data["completed_message"]) . ".</p> 
                        <p>( In the event of a dispute in your favour, we will promptly send the sum to the address you have provided. ).</p>";
        } elseif ($_SESSION['role'] === "vendor") {
            $message = "<p>Dear seller, the exchange is now complete.</p> 
                        <p>If you are reading this message and there is no dispute, this means that you will receive the funds due to you (" . encode_html($exchange_data["sum"]) . "€ so " . encode_html($exchange_data["converted_sum"]) . " in " . encode_html($exchange_data["cryptocurrency"]) . ") quickly to the address you have provided : 
                        <p> → " . encode_html($_SESSION['refund_address']) . " ← </p>
                        <p>In the event of a dispute and deliberation, here is the message from our department : " . encode_html($exchange_data["completed_message"]) . ".</p> 
                        <p>( In the event of a dispute in your favour, we will promptly send the sum to the address you have provided. ).</p>";
        }
        break;
    case "closed":
        if ($_SESSION['role'] === "customer") {
            $message = "<p>Dear customer, the exchange is now closed.</p>";
        } elseif ($_SESSION['role'] === "vendor") {
            $message = "<p>Dear seller, the exchange is now closed.</p>";
        } elseif ($_SESSION['role'] === "admin") {
            $message = "<p>Dear administrator, the exchange is now closed.</p>";
        }
        break;

    default:
        $message = "Welcome to your exchange.";
}

echo '<div class="card">'
    . '<div class="center">'
    . '<p>Your token is ' . encode_html($_SESSION['auth_token']) . ' - keep it very preciously it is necessary to connect you to this exchange.</p>'
    . '<p>' . $message . '</p>'
    . '</div>'
    . '</div>';

?>