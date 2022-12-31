<form action="" method="post" autocomplete="on" id="charge">
    <input type="hidden" name="isTesting" value="<?php echo isset($_POST['isTesting']) ? $_POST['isTesting'] : 0; ?>" />

    <div class="mt-3" id="example-table"></div>

    <input type="hidden" name="mandy_customer_id" value="">

    <input type="hidden" name="wasaike_customer_id" value="">

    <input type="hidden" name="customer_name" value="">

    <input type="hidden" name="last4" value="">

    <div class="row g-3 mt-0 mb-3">
        <div class="col-1" style="min-width: 105px;">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="shop" value="mandy" id="shop-mandy" checked>
                <label class="form-check-label w-100" for="shop-mandy">
                    Mandy
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="shop" value="wasaike" id="shop-wasaike">
                <label class="form-check-label w-100" for="shop-wasaike">
                    Wasaike
                </label>
            </div>
        </div>
        <div class="form-floating col">
            <input type="text" class="amount form-control" name="amount" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : '' ?>" autocomplete="off" pattern="\d*" required placeholder="Total">
            <label for="amount">Total</label>
        </div>

        <div class="col-1" style="min-width: 85px;">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="charge_percent" value="100" id="charge_percent-100" checked>
                <label class="form-check-label w-100" for="charge_percent-100">
                    100%
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="charge_percent" value="50" id="charge_percent-50">
                <label class="form-check-label w-100" for="charge_percent-50">
                    50%
                </label>
            </div>
        </div>
    </div>

    <div class="float-end">
        <button class="btn btn-primary" type="submit" name="charge_customer" value="1">Capture</button>
    </div>
</form>
