<form action="" method="post" autocomplete="on" id="scheduled_captures">
    <input type="hidden" name="scheduled_captures" value="1" />
    <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" />

    <div class="mt-3" id="scheduled_captures_table"></div>
</form>
