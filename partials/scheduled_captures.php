<form action="" method="post" autocomplete="on" id="scheduled_captures">
    <input type="hidden" name="scheduled_captures" value="1" />
    <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" />

    <div class="mt-3" id="scheduled_captures_table"></div>
    <br>
    <div class="form-check form-switch text-end">
        <label class="form-check-label" role="button">
            <input class="form-check-input" type="checkbox" role="switch" name="is_show_captured" id="flexSwitchCheckChecked2" value="0" >
            Show Captured</label>
    </div>
    <div class="form-check form-switch text-end">
        <label class="form-check-label" role="button">
            <input class="form-check-input" type="checkbox" role="switch" name="is_show_refunded" id="flexSwitchCheckChecked3" value="0" >
            Show Refunded</label>
    </div>
</form>
