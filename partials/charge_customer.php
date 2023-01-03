<form action="" method="post" autocomplete="on" id="charge">
    <input type="hidden" name="charge_customer" value="1" />
    <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" />

    <div class="mt-3" id="example-table"></div>

    <input type="hidden" name="mandy_customer_id" value="">

    <input type="hidden" name="wasaike_customer_id" value="">

    <input type="hidden" name="customer_name" value="">

    <input type="hidden" name="last4" value="">

    <div class="row g-3 mt-0 mb-3">
        <div class="col-1" style="min-width: 105px;">
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="shop" value="mandy" checked>
                    Mandy
                </label>
            </div>
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="shop" value="wasaike">
                    Wasaike
                </label>
            </div>
        </div>
        <div class="form-floating col">
            <input type="text" class="amount form-control" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" autocomplete="off" pattern="\d*" required placeholder="Amount">
            <label for="amount">Amount</label>
        </div>
    </div>

    <div class="float-end">
        <button class="btn btn-primary" type="submit" name="authorize" value="1">Authorize</button>
        <button class="btn btn-primary" type="submit" name="capture" value="1">Capture</button>
    </div>
</form>
