
<div class="modal modal-lg fade common-validation-modal" id="holidays-edit-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ __('Edit Holiday') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="personal-info">
                    <form action="" method="post" enctype="multipart/form-data"
                        class="ajaxform_instant_reload editHoldayForm">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Name') }}</label>
                                <input type="text" name="name" id="name" required class="form-control" placeholder="{{ __('Enter Name') }}">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('Start Date') }}</label>
                                <input type="date" name="start_date" id="start_date" required class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{ __('End Date') }}</label>
                                <input type="date" name="end_date" id="end_date" required class="form-control">
                            </div>
                            <div class="col-lg-6 mb-2">
                                <label>{{__('Description')}}</label>
                                <textarea name="description" id="description" class="form-control" placeholder="{{ __('Enter Description') }}"></textarea>
                            </div>
                         </div>
                        <div class="col-lg-12">
                            <div class="button-group text-center mt-5">
                                <a href="{{ route('hrm.holidays.index') }}" class="theme-btn border-btn m-2">{{ __('Cancel') }}</a>
                                <button class="theme-btn m-2 submit-btn">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
