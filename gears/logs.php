<div class="wrapper">
    <form action="" method="post">
        <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
        <button type="submit" name="clear_logs">Clear logs</button>
    </form>

    <!--
    <form action="" method="post">
      <input type="hidden" name="token" value="<?= encode_html(sha1(session_id())) ?>">
      <button type="submit" name="save_logs">Save logs</button>
    </form>
  -->

</div>

<div class="logs_panel">
    <?php $control->show_logs(); ?>
</div>