<div class="card">
  <div class="center">

    <h2>Why? 🤔</h2>

    <p> Using an Escrow to carry out cryptocurrency transactions has become essential in
      exchanges on the Darknet.
      Cryptocurrencies offer a number of advantages, such as the speed of transactions, security and decentralisation.
      decentralisation,
      but they also present inherent risks, such as scams and disputes.
      That's where <?php if (isset($site_name)) { echo encode_html($site_name); } ?> comes in. To offer a reliable and secure solution.
    </p>

    <h2>How ? 🧐</h2>

    <p> To initiate an Escrow, simply click on "Request Escrow".
      You will be redirected to a page dedicated to sellers, where you can create an Escrow request for your
      client.
      Only sellers are authorised to initiate such a request.
      Once the request has been created, a specific link will be generated.
      You will need to provide this link to your customer so that they can accept the Escrow.
      The funds will be held on the platform until the transaction is fully finalised.
      If no problems arise, the funds will be transferred to the seller.
      In the event of a dispute raised by the buyer, a mediation process will be initiated between the seller and the buyer to resolve the problem.
      buyer to resolve the issue.
      Don't worry, you'll be guided through every step of the process, whatever your case. We
      assist you every step of the way to ensure a smooth and enjoyable experience.</p>

    <h2> Usage fees: what are they? 💸 </h2>

    <p> Transaction fees on our platform are differentiated for Bitcoin and Monero,
      due to the particularities of each cryptocurrency. </p>

    <h3> Bitcoin : </h3>

    <p> To meet the specific requirements of Bitcoin transactions,
      we have put in place an adapted pricing structure which is currently 
      <?php if (isset($bitcoin_fee)) { echo encode_html($bitcoin_fee) . " sat/kB"; } ?>.
      We charge a fee of 15% on these transactions for a minimum of €25,
      a necessary measure to absorb the high costs inherent in using the Bitcoin blockchain.
      This fee not only offsets the notoriously expensive costs associated with the blockchain,
      but they also guarantee enhanced security for these transactions.
      It is important to note that, despite this higher fee rate,
      our margin on Bitcoin transactions remains minimal.
      Our objective remains to provide a secure and efficient service,
      while managing the costs imposed by blockchain technology.
    </p>

    <h3> Monero : </h3>

    <p> For Monero transactions, on the other hand, fees are set at 6% for a minimum of €10.
      This difference is explained by the fact that transaction costs on the Monero blockchain are
      are generally lower,
      and the security processes, while robust, involve lower operational costs.
      This rate therefore allows us to make a small profit,
      while offering users a more advantageous rate compared with Bitcoin. </p>

    <h2> The little lines 📝 </h2>

    <p> Our automated transactions are subject to certain time limits.
      If the transaction fee is insufficient for confirmation within 7 days, the transaction will be
      closed without any possibility of intervention.
      However, you may contact us to try to recover the funds.
      If the seller does not confirm dispatch of the item, we will automatically refund the customer
      within 7 days and the transaction is closed.
      Once the seller has confirmed shipment, the customer has 14 days to release the funds via their
      user interface,
      except in the event of a dispute. If the customer does not declare a dispute or does not confirm receipt of the item
      within this period
      the funds are automatically transferred to the seller.

      In the event of a dispute, the service administrator intervenes to settle the matter.
      The assessment is based on several criteria: examination of the facts
      analysis of the evidence provided by both parties (seller and customer),
      and considerations specific to the case, such as the quality of the service or the conformity of the products
      delivered.
    <p>

    <div class="center">
      <a href="?location=add_escrow">
        <button type="submit">Request an Escrow</button>
      </a>
    </div>

  </div>
</div>