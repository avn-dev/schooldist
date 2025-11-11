
<form onsubmit="return false">
    <input type="hidden" name="state" value="{\TsTuition\Entity\Block\Unit::STATE_CANCELLED}" />
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group">
                {'You are about to skip the lesson. Please enter a reason for this.'|L10N}
            </div>
        </div>
        <div class="col-xs-12" style="margi">
            <div class="form-group">
                <label>{'Comment'|L10N} *</label>
                <textarea name="comment" class="form-control"></textarea>
            </div>
        </div>
    </div>
</form>
