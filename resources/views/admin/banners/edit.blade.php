<div class="modal modal-md fade" id="edit-banner-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('business.Edit Advertising') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="post" enctype="multipart/form-data"
                    class="ajaxform_instant_reload edit-imageUrl-form mb-0">
                    @csrf
                    @method('put')

                    <div class="mt-3">
                        <label>{{ __('business.Advertising Name') }}</label>
                        <input type="text" name="name" required class="form-control" id="name" placeholder="{{ __('common.Enter Name') }}">
                    </div>

                    <div class="mt-3">
                        <label></label>
                        <div class="upload-img-v2 chosen-img">
                            <label class="upload-v4 d-flex align-items-center justify-content-center">
                                <div class="img-wrp">
                                    <img src="{{ asset('assets/images/icons/upload-icon.svg') }}" alt="user"
                                        id="edit-imageUrl">
                                </div>
                                <input type="file" name="imageUrl" class="d-none"
                                    data-preview="#edit-imageUrl"
                                    accept="image/*">
                            </label>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label>{{ __('common.Status') }}</label>
                        <div class="form-control d-flex justify-content-between align-items-center radio-switcher">
                            <p class="dynamic-text">{{ __('common.Active') }}</p>
                            <label class="switch m-0 top-0">
                                <input type="checkbox" name="status" class="change-text edit-status" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-5">
                            <button type="reset" class="theme-btn border-btn m-2" data-bs-dismiss="modal" aria-label="Close">{{ __('common.Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('common.Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
