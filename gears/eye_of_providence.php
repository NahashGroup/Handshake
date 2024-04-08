<div class="center">
    <h2>Exchanges statistics</h2>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Exchange</th>
                    <th>Date/Time</th>
                    <th>Sum</th>
                    <th>Converted Sum</th>
                    <th>Cryptocurrency</th>
                    <th>Depot address</th>
                    <th>Status</th>
                    <th>Completed Message</th>
                </tr>
            </thead>
            <tbody id="exchangeData">
                <?php $control->eye_of_providence("exchanges"); ?>
            </tbody>
        </table>
    </div>


    <h2>Wallets statistics</h2>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Wallet</th>
                    <th>Address</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php $control->eye_of_providence("wallets"); ?>
            </tbody>
        </table>
    </div>
</div>