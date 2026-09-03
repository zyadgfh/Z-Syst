
<div class="modal fade" id="approved-reject-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{__('common.Reason')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post" class="ajaxform_instant_reload approve-reject-form">
                @csrf
                <div class="modal-body">
                    <textarea name="reason" cols="2" rows="5" class="form-control mt-3" placeholder="{{ __('common.Reason') }}"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="theme-btn border-btn" data-bs-dismiss="modal">{{__('common.Close')}}</button>
                    <button type="submit" class="theme-btn submit-btn">{{__('common.Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
