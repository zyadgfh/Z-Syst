<div class="modal modal-md fade" id="create-banner-modal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ __('Add New Advertising') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.banners.store') }}" method="post" enctype="multipart/form-data" class="ajaxform_instant_reload">
                    @csrf

                    <div class="mt-3">
                        <label>{{ __('Advertising Name') }}</label>
                        <input type="text" name="name" required class="form-control" placeholder="{{ __('Enter Name') }}">
                    </div>

                    <div class="mt-3">
                        <label>{{ __('Status') }}</label>
                        <div
                            class="form-control bg-light d-flex justify-content-between align-items-center radio-switcher">
                            <p class="dynamic-text mb-0">{{ __('Active') }}</p>
                            <label class="switch m-0 top-0">
                                <input type="checkbox" name="status" class="change-text" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-3 position-relative">
                        <label class="upload-img-label">{{ __('Image') }}</label>
                        <div class="chosen-img">
                            <label class="d-flex align-items-center gap-2">
                                <input type="file" name="imageUrl" class="form-control bg-light"
                                    data-preview="#profile-img"
                                    accept="image/*">
                                <div class="img-wrp">
                                    <img src="{{ asset('assets/images/icons/empty-img.svg') }}" alt="user"
                                        id="profile-img">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="button-group text-center mt-3">
                            <button type="reset" class="theme-btn border-btn m-2" data-bs-dismiss="modal" aria-label="Close">{{ __('Cancel') }}</button>
                            <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
