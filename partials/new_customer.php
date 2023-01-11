<form action="" method="post" autocomplete="on" id="add_card">
    <input type="hidden" name="new_customer" value="1" />
    <input type="hidden" name="isTesting" value="<?php echo isset($_SESSION['form_data']['isTesting']) ? $_SESSION['form_data']['isTesting'] : 0; ?>" required />
    <div class="form-floating mb-3 mt-3">
        <input type="text" class="name form-control" name="name" placeholder="Name" value="<?php echo isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : '' ?>" required>
        <label for="name">Name</label>
    </div>

    <div class="form-floating mb-3 input-group">
        <input type="text" class="cc-number form-control" name="cc-number" value="<?php echo isset($_SESSION['form_data']['cc-number']) ? $_SESSION['form_data']['cc-number'] : '' ?>" pattern="\d*" x-autocompletetype="cc-number" placeholder="Card number" required>
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Use Test Card...</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item visa" href="#">Visa</a></li>
            <li><a class="dropdown-item visa-debit" href="#">Visa (debit)</a></li>
        </ul>
        <label for="cc-number">Card number</label>
    </div>

    <div class="row g-3 mb-3">
        <div class="form-floating col">
            <input type="text" class="cc-exp form-control" name="cc-exp" value="<?php echo isset($_SESSION['form_data']['cc-exp']) ? $_SESSION['form_data']['cc-exp'] : '' ?>" x-autocompletetype="cc-exp" placeholder="MM/YY Expires" required maxlength="9">
            <label for="cc-exp">MM/YY</label>
        </div>
        <div class="form-floating col-3">
            <input type="text" class="cc-cvc form-control" name="cc-cvc" value="<?php echo isset($_SESSION['form_data']['cc-cvc']) ? $_SESSION['form_data']['cc-cvc'] : '' ?>" pattern="\d*" x-autocompletetype="cc-csc" placeholder="CVC" autocomplete="off">
            <label for="cc-cvc">CVC</label>
        </div>
        <div class="form-floating col">
            <input autocomplete="off" type="text" id="arrive_at" class="form-control" name="arrive_at" placeholder="Arrival Date" value="<?php echo isset($_SESSION['form_data']['arrive_at']) ? $_SESSION['form_data']['arrive_at'] : '' ?>">
            <label for="arrive_at">Arrival Date</label>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-1" style="min-width: 105px;">
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="shop" value="wasaike" checked>
                    Wasaike
                </label>
            </div>
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="shop" value="mandy">
                    Mandy
                </label>
            </div>
        </div>
        <div class="form-floating col">
            <input type="number" class="amount form-control" name="amount" value="<?php echo isset($_SESSION['form_data']['amount']) ? $_SESSION['form_data']['amount'] : '' ?>" autocomplete="off" required placeholder="Total">
            <label for="amount">Total</label>
        </div>

        <div class="col-1" style="min-width: 85px;">
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="charge_percent" value="50" checked>
                    50%
                </label>
            </div>
            <div class="form-check">
                <label class="form-check-label w-100" role="button">
                    <input class="form-check-input" type="radio" name="charge_percent" value="100">
                    100%
                </label>
            </div>
        </div>
    </div>

    <div class="alert alert-danger validation passed" role="alert"></div>

    <div class="float-end">
        <button class="btn btn-primary" type="submit" id="authorize" name="authorize" value="1">Authorize</button>
        <button class="btn btn-primary" type="submit" id="capture" name="capture" value="1">Capture</button>
    </div>
</form>
<?php
$_SESSION['form_data'] = [];
