@extends('layouts.master')

@section('title')
    {{__('settings.System Settings') }}
@endsection

@section('main_content')

<div class="erp-table-section system-settings">
    <div class="container-fluid">
        <div class="card ">
            <div class="card-bodys">
                <div class="table-header ">
                    <div class="card-bodys">
                        <div class="table-header mb-0 border-0 p-16">
                            <h4>{{ __('settings.Note :') }} <span class="custom-warning">{{ __('settings.Don't Use Any Kind Of Space In The Input Fields') }}</span></h4>
                        </div>
                    </div>
                </div>

                <div class="order-form-section mt-4 p-16">
                    <div class="tab-content">
                        <div class="tab-pane fade active show" id="add-new-petty" role="tabpanel">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-4 mb-3">
                                    <div class="cards-header shadow">
                                        <div class="card-body">
                                            <ul class="nav nav-pills flex-column">
                                                <li class="nav-item">
                                                    <a href="#app" id="home-tab4" class="add-report-btn active nav-link" data-bs-toggle="tab">{{ __('settings.App') }}</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="#Drivers" class="add-report-btn nav-link" data-bs-toggle="tab">{{ __('settings.Drivers') }}</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="#storage" class="add-report-btn nav-link" data-bs-toggle="tab">{{ __('settings.Storage Settings') }}</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a href="#mail-configuration" class="add-report-btn nav-link" data-bs-toggle="tab">{{ __('settings.Mail Configuration') }}</a>
                                                </li>

                                                <li class="nav-item">
                                                    <a href="#other" class="add-report-btn nav-link" data-bs-toggle="tab">{{ __('settings.Others') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-8">
                                    <div class="cards-header shadow">
                                        <div class="card-body">
                                            <form action="{{ route('admin.system-settings.store') }}" method="post" enctype="multipart/form-data" class="ajaxform">
                                                @csrf

                                                <div class="tab-content no-padding">
                                                    <div class="tab-pane fade show active" id="app">
                                                        <div class="form-group">
                                                            <label>{{ __('settings.APP_NAME') }}</label>
                                                            <input type="text" name="APP_NAME" value="{{ env('APP_NAME') ?? '' }}"  required class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.APP_KEY') }}</label>
                                                            <input type="text" name="APP_KEY" value="{{ env('APP_KEY') ?? '' }}" required  class="form-control" readonly>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.APP_DEBUG') }}</label>
                                                            <select class="form-control" required name="APP_DEBUG">
                                                                <option value="true" @selected(env('APP_DEBUG') == true)>{{ __('settings.true (Developers Only)') }}</option>
                                                                <option value="false" @selected(env('APP_DEBUG') == false)>{{ __('settings.false') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.APP_URL') }}</label>
                                                            <input type="text" name="APP_URL" value="{{ env('APP_URL') ?? '' }}" required class="form-control">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.Firebase Service Account JSON') }}</label>
                                                            <input type="file" name="service_account_credentials" accept="application/json" class="form-control">
                                                            <small class="text-muted">{{ __('settings.Upload a valid Firebase service account JSON file.') }}</small>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="button-group text-center mt-4">
                                                                    <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="mail-configuration">
                                                        <div class="form-group">
                                                            <label for="QUEUE_MAIL" class="required">{{ __('settings.QUEUE_MAIL') }}</label>
                                                            <div class="gpt-up-down-arrow position-relative">
                                                            <select name="QUEUE_MAIL" id="QUEUE_MAIL" class="form-control">
                                                                <option @selected(env('QUEUE_MAIL') == true) value="true">{{ __('settings.true') }}</option>
                                                                <option @selected(env('QUEUE_MAIL') == false) value="false">{{ __('settings.false') }}</option>
                                                            </select>
                                                            <span></span>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_DRIVER_TYPE') }}</label>
                                                            <div class="gpt-up-down-arrow position-relative">
                                                                <select name="MAIL_DRIVER_TYPE" class="form-control" id="mail-driver-type-select">
                                                                    <option value="MAIL_MAILER" @selected(env('MAIL_DRIVER_TYPE') == 'MAIL_MAILER' ? 'selected' : '')>{{ __('settings.MAIL MAILER') }}</option>
                                                                    <option value="MAIL_DRIVER" @selected(env('MAIL_DRIVER_TYPE') == 'MAIL_DRIVER' ? 'selected' : '')>{{ __('settings.MAIL DRIVER') }}</option>
                                                                </select>
                                                                <span></span>
                                                            </div>
                                                        </div>
                                                        <div class="form-group">
                                                            <label id="mail-driver-label">{{ __('settings.MAIL DRIVER') }}</label>
                                                            <select name="MAIL_DRIVER" class="form-control">
                                                                <option @selected(env('MAIL_DRIVER') == 'sendmail') value="sendmail">{{ __('settings.sendmail') }}</option>
                                                                <option @selected(env('MAIL_DRIVER') == 'smtp') value="smtp">{{ __('settings.smtp') }}</option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_HOST') }}</label>
                                                            <input type="text"  name="MAIL_HOST" value="{{ env('MAIL_HOST') ?? '' }}" class="form-control" >
                                                        </div>

                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_PORT') }}</label>
                                                            <input type="text"  name="MAIL_PORT" value="{{ env('MAIL_PORT') ?? '' }}" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_USERNAME') }}</label>
                                                            <input type="text"   name="MAIL_USERNAME" value="{{ env('MAIL_USERNAME') ?? '' }}" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_PASSWORD') }}</label>
                                                            <input type="text" name="MAIL_PASSWORD" value="{{ env('DEMO_MODE') ? '....' : env('MAIL_PASSWORD') }}" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_ENCRYPTION') }}</label>
                                                            <input type="text"   name="MAIL_ENCRYPTION" value="{{ env('MAIL_ENCRYPTION') ?? '' }}" class="form-control" >
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_FROM_ADDRESS') }}</label>
                                                            <input type="text"   name="MAIL_FROM_ADDRESS" value="{{ env('MAIL_FROM_ADDRESS') ?? '' }}" class="form-control" >
                                                        </div>

                                                        <div class="form-group">
                                                            <label>{{ __('settings.MAIL_FROM_NAME') }}</label>
                                                            <input type="text"   name="MAIL_FROM_NAME" value="{{ env('MAIL_FROM_NAME') ?? '' }}" class="form-control" >
                                                        </div>

                                                        <span>{{ __('settings.Note :') }} <span class="text-danger">{{ __('settings.If you are using MAIL QUEUE after Changing The Mail Settings You Need To Restart Your Supervisor From Your Server') }}</span></span><br>

                                                        <span>{{ __('settings.QUEUE COMMAND Path :') }} <span class="text-danger">{{ __('settings.Configured for Z-Syst environment') }}</span></span><br>
                                                        <span>{{ __('settings.QUEUE COMMAND :') }} <span class="text-danger">{{ __('settings.php artisan queue:work') }}</span></span>
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="button-group text-center mt-4">
                                                                    <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="Drivers">
                                                        <div class="form-group">
                                                            <label for="CACHE_DRIVER">{{ __('settings.CACHE_DRIVER') }}</label>
                                                            <div class="gpt-up-down-arrow position-relative">
                                                                <select class="form-control" name="CACHE_DRIVER" required>
                                                                    <option value="array" {{ old('CACHE_DRIVER', 'file') == 'array' ? 'selected' : '' }}>{{ __('settings.Array (Low Performance)') }}</option>
                                                                    <option value="file" {{ old('CACHE_DRIVER', 'file') == 'file' ? 'selected' : '' }}>{{ __('settings.File (Good Performance)') }}</option>
                                                                    <option value="memcached" {{ old('CACHE_DRIVER', 'file') == 'memcached' ? 'selected' : '' }}>{{ __('settings.Memcached (Don't Enable If You Don't Have Memcached Extension)') }}</option>
                                                                    <option value="redis" {{ old('CACHE_DRIVER', 'file') == 'redis' ? 'selected' : '' }}>{{ __('settings.Redis (Don't Enable If You Don't Have phpredis Extension)') }}</option>
                                                                </select>
                                                                <span></span>
                                                            </div>

                                                            <small class="text-danger">{{ __('settings.Recommended') }} <strong>{{ __('settings.Memcached or Redis') }}</strong>{{ __('settings.Cache Driver For Height Performance Application And Optimize Call Database Query') }} </small>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.QUEUE_CONNECTION') }}</label>
                                                            <input type="text" required="" name="QUEUE_CONNECTION" class="form-control" value="{{ env('QUEUE_CONNECTION') ?? 'database' }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.SESSION_DRIVER') }}</label>
                                                            <input type="text" required="" name="SESSION_DRIVER" class="form-control" value="{{ env('SESSION_DRIVER') ?? 'file' }}">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.SESSION_LIFETIME') }}</label>
                                                            <input type="number" required="" name="SESSION_LIFETIME" class="form-control" value="{{ env('SESSION_LIFETIME') ?? 200 }}">
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="button-group text-center mt-4">
                                                                    <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    <div class="tab-pane fade" id="redis_method">
                                                        <div class="form-group">
                                                            <label>{{ __('settings.REDIS_PORT') }}</label>
                                                            <input type="text"  name="REDIS_PORT" class="form-control" value="6379">
                                                        </div>

                                                        <div class="form-group">
                                                            <label>{{ __('settings.REDIS_URL') }}</label>
                                                            <input type="text"  name="REDIS_URL" class="form-control" value="">
                                                        </div>

                                                        <div class="form-group">
                                                            <label>{{ __('settings.REDIS_PASSWORD') }}</label>
                                                            <input type="text"  name="REDIS_PASSWORD" class="form-control" value="">
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="storage">
                                                        <h6>{{ __('settings.Storage Settings') }}</h6>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.Storage Method') }}</label>
                                                            <div class="gpt-up-down-arrow position-relative">
                                                            <select class="form-control" name="FILESYSTEM_DISK">
                                                                <option @selected(env('FILESYSTEM_DISK') == 'public') value="public">{{ __('settings.public (uploads folder)') }}</option>
                                                                <option @selected(env('FILESYSTEM_DISK') == 's3') value="s3">{{ __('settings.AWS S3 Storage Bucket') }}</option>
                                                                <option @selected(env('FILESYSTEM_DISK') == 'wasabi') value="wasabi">{{ __('settings.Wasabi Storage Bucket') }}</option>
                                                            </select>
                                                            <span></span>
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <p class="custom-warning">{{ __('settings.Fill up this credentials if you want to use AWS S3 Storage Bucket') }}</p>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.AWS_ACCESS_KEY_ID') }}</label>
                                                            <input type="text"  name="AWS_ACCESS_KEY_ID" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.AWS_SECRET_ACCESS_KEY') }}</label>
                                                            <input type="text"  name="AWS_SECRET_ACCESS_KEY" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.AWS_DEFAULT_REGION') }}</label>
                                                            <input type="text"  name="AWS_DEFAULT_REGION" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.AWS_BUCKET') }}</label>
                                                            <input type="text"  name="AWS_BUCKET" class="form-control" value="">
                                                        </div>
                                                        <hr>
                                                        <p class="custom-warning">{{ __('settings.Fill up this credentials if you want to use Wasabi Storage Bucket') }}</p>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.WAS_ACCESS_KEY_ID') }}</label>
                                                            <input type="text"  name="WAS_ACCESS_KEY_ID" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.WAS_SECRET_ACCESS_KEY') }}</label>
                                                            <input type="text"  name="WAS_SECRET_ACCESS_KEY" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.WAS_DEFAULT_REGION') }}</label>
                                                            <input type="text"  name="WAS_DEFAULT_REGION" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.WAS_BUCKET') }}</label>
                                                            <input type="text"  name="WAS_BUCKET" class="form-control" value="">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>{{ __('settings.WAS_ENDPOINT') }}</label>
                                                            <input type="text"  name="WAS_ENDPOINT" class="form-control" value="">
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="button-group text-center mt-4">
                                                                    <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="tab-pane fade" id="other">
                                                        <div class="">
                                                            <div class="form-group">
                                                                <label>{{ __('settings.CACHE_LIFETIME') }}</label>
                                                                <input type="text"  name="CACHE_LIFETIME" class="form-control" value="{{ env('CACHE_LIFETIME') ?? '' }}">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="TIMEZONE">{{ __('settings.TIMEZONE') }}</label>
                                                                <div class="gpt-up-down-arrow position-relative">
                                                                    <select class="form-control" name="TIMEZONE" id="TIMEZONE" >
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Abidjan') value='Africa/Abidjan'>{{ __('settings.Africa/Abidjan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Accra') value='Africa/Accra'>{{ __('settings.Africa/Accra') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Addis_Ababa') value='Africa/Addis_Ababa'>{{ __('settings.Africa/Addis_Ababa') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Algiers') value='Africa/Algiers'>{{ __('settings.Africa/Algiers') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Asmara') value='Africa/Asmara'>{{ __('settings.Africa/Asmara') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Bamako') value='Africa/Bamako'>{{ __('settings.Africa/Bamako') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Bangui') value='Africa/Bangui'>{{ __('settings.Africa/Bangui') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Banjul') value='Africa/Banjul'>{{ __('settings.Africa/Banjul') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Bissau') value='Africa/Bissau'>{{ __('settings.Africa/Bissau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Blantyre') value='Africa/Blantyre'>{{ __('settings.Africa/Blantyre') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Brazzaville') value='Africa/Brazzaville'>{{ __('settings.Africa/Brazzaville') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Bujumbura') value='Africa/Bujumbura'>{{ __('settings.Africa/Bujumbura') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Cairo') value='Africa/Cairo'>{{ __('settings.Africa/Cairo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Casablanca') value='Africa/Casablanca'>{{ __('settings.Africa/Casablanca') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Ceuta') value='Africa/Ceuta'>{{ __('settings.Africa/Ceuta') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Conakry') value='Africa/Conakry'>{{ __('settings.Africa/Conakry') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Dakar') value='Africa/Dakar'>{{ __('settings.Africa/Dakar') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Dar_es_Salaam') value='Africa/Dar_es_Salaam'>{{ __('settings.Africa/Dar_es_Salaam') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Djibouti') value='Africa/Djibouti'>{{ __('settings.Africa/Djibouti') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Douala') value='Africa/Douala'>{{ __('settings.Africa/Douala') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/El_Aaiun') value='Africa/El_Aaiun'>{{ __('settings.Africa/El_Aaiun') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Freetown') value='Africa/Freetown'>{{ __('settings.Africa/Freetown') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Gaborone') value='Africa/Gaborone'>{{ __('settings.Africa/Gaborone') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Harare') value='Africa/Harare'>{{ __('settings.Africa/Harare') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Johannesburg') value='Africa/Johannesburg'>{{ __('settings.Africa/Johannesburg') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Juba') value='Africa/Juba'>{{ __('settings.Africa/Juba') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Kampala') value='Africa/Kampala'>{{ __('settings.Africa/Kampala') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Khartoum') value='Africa/Khartoum'>{{ __('settings.Africa/Khartoum') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Kigali') value='Africa/Kigali'>{{ __('settings.Africa/Kigali') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Kinshasa') value='Africa/Kinshasa'>{{ __('settings.Africa/Kinshasa') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Lagos') value='Africa/Lagos'>{{ __('settings.Africa/Lagos') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Libreville') value='Africa/Libreville'>{{ __('settings.Africa/Libreville') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Lome') value='Africa/Lome'>{{ __('settings.Africa/Lome') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Luanda') value='Africa/Luanda'>{{ __('settings.Africa/Luanda') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Lubumbashi') value='Africa/Lubumbashi'>{{ __('settings.Africa/Lubumbashi') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Lusaka') value='Africa/Lusaka'>{{ __('settings.Africa/Lusaka') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Malabo') value='Africa/Malabo'>{{ __('settings.Africa/Malabo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Maputo') value='Africa/Maputo'>{{ __('settings.Africa/Maputo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Maseru') value='Africa/Maseru'>{{ __('settings.Africa/Maseru') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Mbabane') value='Africa/Mbabane'>{{ __('settings.Africa/Mbabane') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Mogadishu') value='Africa/Mogadishu'>{{ __('settings.Africa/Mogadishu') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Monrovia') value='Africa/Monrovia'>{{ __('settings.Africa/Monrovia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Nairobi') value='Africa/Nairobi'>{{ __('settings.Africa/Nairobi') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Ndjamena') value='Africa/Ndjamena'>{{ __('settings.Africa/Ndjamena') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Niamey') value='Africa/Niamey'>{{ __('settings.Africa/Niamey') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Nouakchott') value='Africa/Nouakchott'>{{ __('settings.Africa/Nouakchott') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Ouagadougou') value='Africa/Ouagadougou'>{{ __('settings.Africa/Ouagadougou') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Porto-Novo') value='Africa/Porto-Novo'>{{ __('settings.Africa/Porto-Novo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Sao_Tome') value='Africa/Sao_Tome'>{{ __('settings.Africa/Sao_Tome') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Tripoli') value='Africa/Tripoli'>{{ __('settings.Africa/Tripoli') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Tunis') value='Africa/Tunis'>{{ __('settings.Africa/Tunis') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Africa/Windhoek') value='Africa/Windhoek'>{{ __('settings.Africa/Windhoek') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Adak') value='America/Adak'>{{ __('settings.America/Adak') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Anchorage') value='America/Anchorage'>{{ __('settings.America/Anchorage') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Anguilla') value='America/Anguilla'>{{ __('settings.America/Anguilla') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Antigua') value='America/Antigua'>{{ __('settings.America/Antigua') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Araguaina') value='America/Araguaina'>{{ __('settings.America/Araguaina') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Buenos_Aires') value='America/Argentina/Buenos_Aires'>{{ __('settings.America/Argentina/Buenos_Aires') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Catamarca') value='America/Argentina/Catamarca'>{{ __('settings.America/Argentina/Catamarca') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Cordoba') value='America/Argentina/Cordoba'>{{ __('settings.America/Argentina/Cordoba') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Jujuy') value='America/Argentina/Jujuy'>{{ __('settings.America/Argentina/Jujuy') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/La_Rioja') value='America/Argentina/La_Rioja'>{{ __('settings.America/Argentina/La_Rioja') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Mendoza') value='America/Argentina/Mendoza'>{{ __('settings.America/Argentina/Mendoza') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Rio_Gallegos') value='America/Argentina/Rio_Gallegos'>{{ __('settings.America/Argentina/Rio_Gallegos') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Salta') value='America/Argentina/Salta'>{{ __('settings.America/Argentina/Salta') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/San_Juan') value='America/Argentina/San_Juan'>{{ __('settings.America/Argentina/San_Juan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/San_Luis') value='America/Argentina/San_Luis'>{{ __('settings.America/Argentina/San_Luis') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Tucuman') value='America/Argentina/Tucuman'>{{ __('settings.America/Argentina/Tucuman') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Argentina/Ushuaia') value='America/Argentina/Ushuaia'>{{ __('settings.America/Argentina/Ushuaia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Aruba') value='America/Aruba'>{{ __('settings.America/Aruba') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Asuncion') value='America/Asuncion'>{{ __('settings.America/Asuncion') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Atikokan') value='America/Atikokan'>{{ __('settings.America/Atikokan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Bahia') value='America/Bahia'>{{ __('settings.America/Bahia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Bahia_Banderas') value='America/Bahia_Banderas'>{{ __('settings.America/Bahia_Banderas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Barbados') value='America/Barbados'>{{ __('settings.America/Barbados') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Belem') value='America/Belem'>{{ __('settings.America/Belem') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Belize') value='America/Belize'>{{ __('settings.America/Belize') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Blanc-Sablon') value='America/Blanc-Sablon'>{{ __('settings.America/Blanc-Sablon') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Boa_Vista') value='America/Boa_Vista'>{{ __('settings.America/Boa_Vista') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Bogota') value='America/Bogota'>{{ __('settings.America/Bogota') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Boise') value='America/Boise'>{{ __('settings.America/Boise') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Cambridge_Bay') value='America/Cambridge_Bay'>{{ __('settings.America/Cambridge_Bay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Campo_Grande') value='America/Campo_Grande'>{{ __('settings.America/Campo_Grande') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Cancun') value='America/Cancun'>{{ __('settings.America/Cancun') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Caracas') value='America/Caracas'>{{ __('settings.America/Caracas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Cayenne') value='America/Cayenne'>{{ __('settings.America/Cayenne') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Cayman') value='America/Cayman'>{{ __('settings.America/Cayman') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Chicago') value='America/Chicago'>{{ __('settings.America/Chicago') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Chihuahua') value='America/Chihuahua'>{{ __('settings.America/Chihuahua') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Costa_Rica') value='America/Costa_Rica'>{{ __('settings.America/Costa_Rica') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Creston') value='America/Creston'>{{ __('settings.America/Creston') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Cuiaba') value='America/Cuiaba'>{{ __('settings.America/Cuiaba') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Curacao') value='America/Curacao'>{{ __('settings.America/Curacao') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Danmarkshavn') value='America/Danmarkshavn'>{{ __('settings.America/Danmarkshavn') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Dawson') value='America/Dawson'>{{ __('settings.America/Dawson') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Dawson_Creek') value='America/Dawson_Creek'>{{ __('settings.America/Dawson_Creek') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Denver') value='America/Denver'>{{ __('settings.America/Denver') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Detroit') value='America/Detroit'>{{ __('settings.America/Detroit') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Dominica') value='America/Dominica'>{{ __('settings.America/Dominica') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Edmonton') value='America/Edmonton'>{{ __('settings.America/Edmonton') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Eirunepe') value='America/Eirunepe'>{{ __('settings.America/Eirunepe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/El_Salvador') value='America/El_Salvador'>{{ __('settings.America/El_Salvador') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Fort_Nelson') value='America/Fort_Nelson'>{{ __('settings.America/Fort_Nelson') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Fortaleza') value='America/Fortaleza'>{{ __('settings.America/Fortaleza') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Glace_Bay') value='America/Glace_Bay'>{{ __('settings.America/Glace_Bay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Godthab') value='America/Godthab'>{{ __('settings.America/Godthab') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Goose_Bay') value='America/Goose_Bay'>{{ __('settings.America/Goose_Bay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Grand_Turk') value='America/Grand_Turk'>{{ __('settings.America/Grand_Turk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Grenada') value='America/Grenada'>{{ __('settings.America/Grenada') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Guadeloupe') value='America/Guadeloupe'>{{ __('settings.America/Guadeloupe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Guatemala') value='America/Guatemala'>{{ __('settings.America/Guatemala') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Guayaquil') value='America/Guayaquil'>{{ __('settings.America/Guayaquil') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Guyana') value='America/Guyana'>{{ __('settings.America/Guyana') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Halifax') value='America/Halifax'>{{ __('settings.America/Halifax') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Havana') value='America/Havana'>{{ __('settings.America/Havana') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Hermosillo') value='America/Hermosillo'>{{ __('settings.America/Hermosillo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Indianapolis') value='America/Indiana/Indianapolis'>{{ __('settings.America/Indiana/Indianapolis') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Knox') value='America/Indiana/Knox'>{{ __('settings.America/Indiana/Knox') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Marengo') value='America/Indiana/Marengo'>{{ __('settings.America/Indiana/Marengo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Petersburg') value='America/Indiana/Petersburg'>{{ __('settings.America/Indiana/Petersburg') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Tell_City') value='America/Indiana/Tell_City'>{{ __('settings.America/Indiana/Tell_City') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Vevay') value='America/Indiana/Vevay'>{{ __('settings.America/Indiana/Vevay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Vincennes') value='America/Indiana/Vincennes'>{{ __('settings.America/Indiana/Vincennes') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Indiana/Winamac') value='America/Indiana/Winamac'>{{ __('settings.America/Indiana/Winamac') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Inuvik') value='America/Inuvik'>{{ __('settings.America/Inuvik') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Iqaluit') value='America/Iqaluit'>{{ __('settings.America/Iqaluit') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Jamaica') value='America/Jamaica'>{{ __('settings.America/Jamaica') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Juneau') value='America/Juneau'>{{ __('settings.America/Juneau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Kentucky/Louisville') value='America/Kentucky/Louisville'>{{ __('settings.America/Kentucky/Louisville') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Kentucky/Monticello') value='America/Kentucky/Monticello'>{{ __('settings.America/Kentucky/Monticello') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Kralendijk') value='America/Kralendijk'>{{ __('settings.America/Kralendijk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/La_Paz') value='America/La_Paz'>{{ __('settings.America/La_Paz') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Lima') value='America/Lima'>{{ __('settings.America/Lima') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Los_Angeles') value='America/Los_Angeles'>{{ __('settings.America/Los_Angeles') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Lower_Princes') value='America/Lower_Princes'>{{ __('settings.America/Lower_Princes') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Maceio') value='America/Maceio'>{{ __('settings.America/Maceio') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Managua') value='America/Managua'>{{ __('settings.America/Managua') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Manaus') value='America/Manaus'>{{ __('settings.America/Manaus') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Marigot') value='America/Marigot'>{{ __('settings.America/Marigot') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Martinique') value='America/Martinique'>{{ __('settings.America/Martinique') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Matamoros') value='America/Matamoros'>{{ __('settings.America/Matamoros') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Mazatlan') value='America/Mazatlan'>{{ __('settings.America/Mazatlan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Menominee') value='America/Menominee'>{{ __('settings.America/Menominee') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Merida') value='America/Merida'>{{ __('settings.America/Merida') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Metlakatla') value='America/Metlakatla'>{{ __('settings.America/Metlakatla') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Mexico_City') value='America/Mexico_City'>{{ __('settings.America/Mexico_City') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Miquelon') value='America/Miquelon'>{{ __('settings.America/Miquelon') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Moncton') value='America/Moncton'>{{ __('settings.America/Moncton') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Monterrey') value='America/Monterrey'>{{ __('settings.America/Monterrey') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Montevideo') value='America/Montevideo'>{{ __('settings.America/Montevideo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Montserrat') value='America/Montserrat'>{{ __('settings.America/Montserrat') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Nassau') value='America/Nassau'>{{ __('settings.America/Nassau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/New_York') value='America/New_York'>{{ __('settings.America/New_York') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Nipigon') value='America/Nipigon'>{{ __('settings.America/Nipigon') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Nome') value='America/Nome'>{{ __('settings.America/Nome') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Noronha') value='America/Noronha'>{{ __('settings.America/Noronha') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/North_Dakota/Beulah') value='America/North_Dakota/Beulah'>{{ __('settings.America/North_Dakota/Beulah') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/North_Dakota/Center') value='America/North_Dakota/Center'>{{ __('settings.America/North_Dakota/Center') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/North_Dakota/New_Salem') value='America/North_Dakota/New_Salem'>{{ __('settings.America/North_Dakota/New_Salem') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Ojinaga') value='America/Ojinaga'>{{ __('settings.America/Ojinaga') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Panama') value='America/Panama'>{{ __('settings.America/Panama') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Pangnirtung') value='America/Pangnirtung'>{{ __('settings.America/Pangnirtung') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Paramaribo') value='America/Paramaribo'>{{ __('settings.America/Paramaribo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Phoenix') value='America/Phoenix'>{{ __('settings.America/Phoenix') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Port-au-Prince') value='America/Port-au-Prince'>{{ __('settings.America/Port-au-Prince') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Port_of_Spain') value='America/Port_of_Spain'>{{ __('settings.America/Port_of_Spain') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Porto_Velho') value='America/Porto_Velho'>{{ __('settings.America/Porto_Velho') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Puerto_Rico') value='America/Puerto_Rico'>{{ __('settings.America/Puerto_Rico') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Punta_Arenas') value='America/Punta_Arenas'>{{ __('settings.America/Punta_Arenas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Rainy_River') value='America/Rainy_River'>{{ __('settings.America/Rainy_River') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Rankin_Inlet') value='America/Rankin_Inlet'>{{ __('settings.America/Rankin_Inlet') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Recife') value='America/Recife'>{{ __('settings.America/Recife') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Regina') value='America/Regina'>{{ __('settings.America/Regina') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Resolute') value='America/Resolute'>{{ __('settings.America/Resolute') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Rio_Branco') value='America/Rio_Branco'>{{ __('settings.America/Rio_Branco') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Santarem') value='America/Santarem'>{{ __('settings.America/Santarem') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Santiago') value='America/Santiago'>{{ __('settings.America/Santiago') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Santo_Domingo') value='America/Santo_Domingo'>{{ __('settings.America/Santo_Domingo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Sao_Paulo') value='America/Sao_Paulo'>{{ __('settings.America/Sao_Paulo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Scoresbysund') value='America/Scoresbysund'>{{ __('settings.America/Scoresbysund') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Sitka') value='America/Sitka'>{{ __('settings.America/Sitka') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Barthelemy') value='America/St_Barthelemy'>{{ __('settings.America/St_Barthelemy') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Johns') value='America/St_Johns'>{{ __('settings.America/St_Johns') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Kitts') value='America/St_Kitts'>{{ __('settings.America/St_Kitts') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Lucia') value='America/St_Lucia'>{{ __('settings.America/St_Lucia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Thomas') value='America/St_Thomas'>{{ __('settings.America/St_Thomas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/St_Vincent') value='America/St_Vincent'>{{ __('settings.America/St_Vincent') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Swift_Current') value='America/Swift_Current'>{{ __('settings.America/Swift_Current') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Tegucigalpa') value='America/Tegucigalpa'>{{ __('settings.America/Tegucigalpa') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Thule') value='America/Thule'>{{ __('settings.America/Thule') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Thunder_Bay') value='America/Thunder_Bay'>{{ __('settings.America/Thunder_Bay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Tijuana') value='America/Tijuana'>{{ __('settings.America/Tijuana') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Toronto') value='America/Toronto'>{{ __('settings.America/Toronto') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Tortola') value='America/Tortola'>{{ __('settings.America/Tortola') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Vancouver') value='America/Vancouver'>{{ __('settings.America/Vancouver') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Whitehorse') value='America/Whitehorse'>{{ __('settings.America/Whitehorse') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Winnipeg') value='America/Winnipeg'>{{ __('settings.America/Winnipeg') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Yakutat') value='America/Yakutat'>{{ __('settings.America/Yakutat') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'America/Yellowknife') value='America/Yellowknife'>{{ __('settings.America/Yellowknife') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Casey') value='Antarctica/Casey'>{{ __('settings.Antarctica/Casey') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Davis') value='Antarctica/Davis'>{{ __('settings.Antarctica/Davis') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/DumontDUrville') value='Antarctica/DumontDUrville'>{{ __('settings.Antarctica/DumontDUrville') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Macquarie') value='Antarctica/Macquarie'>{{ __('settings.Antarctica/Macquarie') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Mawson') value='Antarctica/Mawson'>{{ __('settings.Antarctica/Mawson') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/McMurdo') value='Antarctica/McMurdo'>{{ __('settings.Antarctica/McMurdo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Palmer') value='Antarctica/Palmer'>{{ __('settings.Antarctica/Palmer') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Rothera') value='Antarctica/Rothera'>{{ __('settings.Antarctica/Rothera') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Syowa') value='Antarctica/Syowa'>{{ __('settings.Antarctica/Syowa') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Troll') value='Antarctica/Troll'>{{ __('settings.Antarctica/Troll') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Antarctica/Vostok') value='Antarctica/Vostok'>{{ __('settings.Antarctica/Vostok') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Arctic/Longyearbyen') value='Arctic/Longyearbyen'>{{ __('settings.Arctic/Longyearbyen') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Aden') value='Asia/Aden'>{{ __('settings.Asia/Aden') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Almaty') value='Asia/Almaty'>{{ __('settings.Asia/Almaty') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Amman') value='Asia/Amman'>{{ __('settings.Asia/Amman') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Anadyr') value='Asia/Anadyr'>{{ __('settings.Asia/Anadyr') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Aqtau') value='Asia/Aqtau'>{{ __('settings.Asia/Aqtau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Aqtobe') value='Asia/Aqtobe'>{{ __('settings.Asia/Aqtobe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Ashgabat') value='Asia/Ashgabat'>{{ __('settings.Asia/Ashgabat') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Atyrau') value='Asia/Atyrau'>{{ __('settings.Asia/Atyrau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Baghdad') value='Asia/Baghdad'>{{ __('settings.Asia/Baghdad') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Bahrain') value='Asia/Bahrain'>{{ __('settings.Asia/Bahrain') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Baku') value='Asia/Baku'>{{ __('settings.Asia/Baku') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Bangkok') value='Asia/Bangkok'>{{ __('settings.Asia/Bangkok') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Barnaul') value='Asia/Barnaul'>{{ __('settings.Asia/Barnaul') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Beirut') value='Asia/Beirut'>{{ __('settings.Asia/Beirut') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Bishkek') value='Asia/Bishkek'>{{ __('settings.Asia/Bishkek') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Brunei') value='Asia/Brunei'>{{ __('settings.Asia/Brunei') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Chita') value='Asia/Chita'>{{ __('settings.Asia/Chita') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Choibalsan') value='Asia/Choibalsan'>{{ __('settings.Asia/Choibalsan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Colombo') value='Asia/Colombo'>{{ __('settings.Asia/Colombo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Damascus') value='Asia/Damascus'>{{ __('settings.Asia/Damascus') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Dhaka') value='Asia/Dhaka'>{{ __('settings.Asia/Dhaka') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Dili') value='Asia/Dili'>{{ __('settings.Asia/Dili') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Dubai') value='Asia/Dubai'>{{ __('settings.Asia/Dubai') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Dushanbe') value='Asia/Dushanbe'>{{ __('settings.Asia/Dushanbe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Famagusta') value='Asia/Famagusta'>{{ __('settings.Asia/Famagusta') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Gaza') value='Asia/Gaza'>{{ __('settings.Asia/Gaza') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Hebron') value='Asia/Hebron'>{{ __('settings.Asia/Hebron') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Ho_Chi_Minh') value='Asia/Ho_Chi_Minh'>{{ __('settings.Asia/Ho_Chi_Minh') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Hong_Kong') value='Asia/Hong_Kong'>{{ __('settings.Asia/Hong_Kong') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Hovd') value='Asia/Hovd'>{{ __('settings.Asia/Hovd') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Irkutsk') value='Asia/Irkutsk'>{{ __('settings.Asia/Irkutsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Jakarta') value='Asia/Jakarta'>{{ __('settings.Asia/Jakarta') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Jayapura') value='Asia/Jayapura'>{{ __('settings.Asia/Jayapura') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Jerusalem') value='Asia/Jerusalem'>{{ __('settings.Asia/Jerusalem') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kabul') value='Asia/Kabul'>{{ __('settings.Asia/Kabul') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kamchatka') value='Asia/Kamchatka'>{{ __('settings.Asia/Kamchatka') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Karachi') value='Asia/Karachi'>{{ __('settings.Asia/Karachi') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kathmandu') value='Asia/Kathmandu'>{{ __('settings.Asia/Kathmandu') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Khandyga') value='Asia/Khandyga'>{{ __('settings.Asia/Khandyga') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kolkata') value='Asia/Kolkata'>{{ __('settings.Asia/Kolkata') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Krasnoyarsk') value='Asia/Krasnoyarsk'>{{ __('settings.Asia/Krasnoyarsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kuala_Lumpur') value='Asia/Kuala_Lumpur'>{{ __('settings.Asia/Kuala_Lumpur') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kuching') value='Asia/Kuching'>{{ __('settings.Asia/Kuching') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Kuwait') value='Asia/Kuwait'>{{ __('settings.Asia/Kuwait') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Macau') value='Asia/Macau'>{{ __('settings.Asia/Macau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Magadan') value='Asia/Magadan'>{{ __('settings.Asia/Magadan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Makassar') value='Asia/Makassar'>{{ __('settings.Asia/Makassar') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Manila') value='Asia/Manila'>{{ __('settings.Asia/Manila') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Muscat') value='Asia/Muscat'>{{ __('settings.Asia/Muscat') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Nicosia') value='Asia/Nicosia'>{{ __('settings.Asia/Nicosia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Novokuznetsk') value='Asia/Novokuznetsk'>{{ __('settings.Asia/Novokuznetsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Novosibirsk') value='Asia/Novosibirsk'>{{ __('settings.Asia/Novosibirsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Omsk') value='Asia/Omsk'>{{ __('settings.Asia/Omsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Oral') value='Asia/Oral'>{{ __('settings.Asia/Oral') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Phnom_Penh') value='Asia/Phnom_Penh'>{{ __('settings.Asia/Phnom_Penh') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Pontianak') value='Asia/Pontianak'>{{ __('settings.Asia/Pontianak') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Pyongyang') value='Asia/Pyongyang'>{{ __('settings.Asia/Pyongyang') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Qatar') value='Asia/Qatar'>{{ __('settings.Asia/Qatar') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Qostanay') value='Asia/Qostanay'>{{ __('settings.Asia/Qostanay') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Qyzylorda') value='Asia/Qyzylorda'>{{ __('settings.Asia/Qyzylorda') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Riyadh') value='Asia/Riyadh'>{{ __('settings.Asia/Riyadh') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Sakhalin') value='Asia/Sakhalin'>{{ __('settings.Asia/Sakhalin') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Samarkand') value='Asia/Samarkand'>{{ __('settings.Asia/Samarkand') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Seoul') value='Asia/Seoul'>{{ __('settings.Asia/Seoul') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Shanghai') value='Asia/Shanghai'>{{ __('settings.Asia/Shanghai') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Singapore') value='Asia/Singapore'>{{ __('settings.Asia/Singapore') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Srednekolymsk') value='Asia/Srednekolymsk'>{{ __('settings.Asia/Srednekolymsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Taipei') value='Asia/Taipei'>{{ __('settings.Asia/Taipei') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Tashkent') value='Asia/Tashkent'>{{ __('settings.Asia/Tashkent') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Tbilisi') value='Asia/Tbilisi'>{{ __('settings.Asia/Tbilisi') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Tehran') value='Asia/Tehran'>{{ __('settings.Asia/Tehran') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Thimphu') value='Asia/Thimphu'>{{ __('settings.Asia/Thimphu') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Tokyo') value='Asia/Tokyo'>{{ __('settings.Asia/Tokyo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Tomsk') value='Asia/Tomsk'>{{ __('settings.Asia/Tomsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Ulaanbaatar') value='Asia/Ulaanbaatar'>{{ __('settings.Asia/Ulaanbaatar') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Urumqi') value='Asia/Urumqi'>{{ __('settings.Asia/Urumqi') }}</option> @selected('Asia/Ust-Nera')
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Ust-Nera') value='Asia/Ust-Nera'>{{ __('settings.Asia/Ust-Nera') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Vientiane') value='Asia/Vientiane'>{{ __('settings.Asia/Vientiane') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Vladivostok') value='Asia/Vladivostok'>{{ __('settings.Asia/Vladivostok') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Yakutsk') value='Asia/Yakutsk'>{{ __('settings.Asia/Yakutsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Yangon') value='Asia/Yangon'>{{ __('settings.Asia/Yangon') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Yekaterinburg') value='Asia/Yekaterinburg'>{{ __('settings.Asia/Yekaterinburg') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Asia/Yerevan') value='Asia/Yerevan'>{{ __('settings.Asia/Yerevan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Azores') value='Atlantic/Azores'>{{ __('settings.Atlantic/Azores') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Bermuda') value='Atlantic/Bermuda'>{{ __('settings.Atlantic/Bermuda') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Canary') value='Atlantic/Canary'>{{ __('settings.Atlantic/Canary') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Cape_Verde') value='Atlantic/Cape_Verde'>{{ __('settings.Atlantic/Cape_Verde') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Faroe') value='Atlantic/Faroe'>{{ __('settings.Atlantic/Faroe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Madeira') value='Atlantic/Madeira'>{{ __('settings.Atlantic/Madeira') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Reykjavik') value='Atlantic/Reykjavik'>{{ __('settings.Atlantic/Reykjavik') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/South_Georgia') value='Atlantic/South_Georgia'>{{ __('settings.Atlantic/South_Georgia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/St_Helena') value='Atlantic/St_Helena'>{{ __('settings.Atlantic/St_Helena') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Atlantic/Stanley') value='Atlantic/Stanley'>{{ __('settings.Atlantic/Stanley') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Adelaide') value='Australia/Adelaide'>{{ __('settings.Australia/Adelaide') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Brisbane') value='Australia/Brisbane'>{{ __('settings.Australia/Brisbane') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Broken_Hill') value='Australia/Broken_Hill'>{{ __('settings.Australia/Broken_Hill') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Currie') value='Australia/Currie'>{{ __('settings.Australia/Currie') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Darwin') value='Australia/Darwin'>{{ __('settings.Australia/Darwin') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Eucla') value='Australia/Eucla'>{{ __('settings.Australia/Eucla') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Hobart') value='Australia/Hobart'>{{ __('settings.Australia/Hobart') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Lindeman') value='Australia/Lindeman'>{{ __('settings.Australia/Lindeman') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Lord_Howe') value='Australia/Lord_Howe'>{{ __('settings.Australia/Lord_Howe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Melbourne') value='Australia/Melbourne'>{{ __('settings.Australia/Melbourne') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Perth') value='Australia/Perth'>{{ __('settings.Australia/Perth') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Australia/Sydney') value='Australia/Sydney'>{{ __('settings.Australia/Sydney') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Amsterdam') value='Europe/Amsterdam'>{{ __('settings.Europe/Amsterdam') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Andorra') value='Europe/Andorra'>{{ __('settings.Europe/Andorra') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Astrakhan') value='Europe/Astrakhan'>{{ __('settings.Europe/Astrakhan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Athens') value='Europe/Athens'>{{ __('settings.Europe/Athens') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Belgrade') value='Europe/Belgrade'>{{ __('settings.Europe/Belgrade') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Berlin') value='Europe/Berlin'>{{ __('settings.Europe/Berlin') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Bratislava') value='Europe/Bratislava'>{{ __('settings.Europe/Bratislava') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Brussels') value='Europe/Brussels'>{{ __('settings.Europe/Brussels') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Bucharest') value='Europe/Bucharest'>{{ __('settings.Europe/Bucharest') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Budapest') value='Europe/Budapest'>{{ __('settings.Europe/Budapest') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Busingen') value='Europe/Busingen'>{{ __('settings.Europe/Busingen') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Chisinau') value='Europe/Chisinau'>{{ __('settings.Europe/Chisinau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Copenhagen') value='Europe/Copenhagen'>{{ __('settings.Europe/Copenhagen') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Dublin') value='Europe/Dublin'>{{ __('settings.Europe/Dublin') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Gibraltar') value='Europe/Gibraltar'>{{ __('settings.Europe/Gibraltar') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Guernsey') value='Europe/Guernsey'>{{ __('settings.Europe/Guernsey') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Helsinki') value='Europe/Helsinki'>{{ __('settings.Europe/Helsinki') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Isle_of_Man') value='Europe/Isle_of_Man'>{{ __('settings.Europe/Isle_of_Man') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Istanbul') value='Europe/Istanbul'>{{ __('settings.Europe/Istanbul') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Jersey') value='Europe/Jersey'>{{ __('settings.Europe/Jersey') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Kaliningrad') value='Europe/Kaliningrad'>{{ __('settings.Europe/Kaliningrad') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Kiev') value='Europe/Kiev'>{{ __('settings.Europe/Kiev') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Kirov') value='Europe/Kirov'>{{ __('settings.Europe/Kirov') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Lisbon') value='Europe/Lisbon'>{{ __('settings.Europe/Lisbon') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Ljubljana') value='Europe/Ljubljana'>{{ __('settings.Europe/Ljubljana') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/London') value='Europe/London'>{{ __('settings.Europe/London') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Luxembourg') value='Europe/Luxembourg'>{{ __('settings.Europe/Luxembourg') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Madrid') value='Europe/Madrid'>{{ __('settings.Europe/Madrid') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Malta') value='Europe/Malta'>{{ __('settings.Europe/Malta') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Mariehamn') value='Europe/Mariehamn'>{{ __('settings.Europe/Mariehamn') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Minsk') value='Europe/Minsk'>{{ __('settings.Europe/Minsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Monaco') value='Europe/Monaco'>{{ __('settings.Europe/Monaco') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Moscow') value='Europe/Moscow'>{{ __('settings.Europe/Moscow') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Oslo') value='Europe/Oslo'>{{ __('settings.Europe/Oslo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Paris') value='Europe/Paris'>{{ __('settings.Europe/Paris') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Podgorica') value='Europe/Podgorica'>{{ __('settings.Europe/Podgorica') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Prague') value='Europe/Prague'>{{ __('settings.Europe/Prague') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Riga') value='Europe/Riga'>{{ __('settings.Europe/Riga') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Rome') value='Europe/Rome'>{{ __('settings.Europe/Rome') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Samara') value='Europe/Samara'>{{ __('settings.Europe/Samara') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/San_Marino') value='Europe/San_Marino'>{{ __('settings.Europe/San_Marino') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Sarajevo') value='Europe/Sarajevo'>{{ __('settings.Europe/Sarajevo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Saratov') value='Europe/Saratov'>{{ __('settings.Europe/Saratov') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Simferopol') value='Europe/Simferopol'>{{ __('settings.Europe/Simferopol') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Skopje') value='Europe/Skopje'>{{ __('settings.Europe/Skopje') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Sofia') value='Europe/Sofia'>{{ __('settings.Europe/Sofia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Stockholm') value='Europe/Stockholm'>{{ __('settings.Europe/Stockholm') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Tallinn') value='Europe/Tallinn'>{{ __('settings.Europe/Tallinn') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Tirane') value='Europe/Tirane'>{{ __('settings.Europe/Tirane') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Ulyanovsk') value='Europe/Ulyanovsk'>{{ __('settings.Europe/Ulyanovsk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Uzhgorod') value='Europe/Uzhgorod'>{{ __('settings.Europe/Uzhgorod') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Vaduz') value='Europe/Vaduz'>{{ __('settings.Europe/Vaduz') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Vatican') value='Europe/Vatican'>{{ __('settings.Europe/Vatican') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Vienna') value='Europe/Vienna'>{{ __('settings.Europe/Vienna') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Vilnius') value='Europe/Vilnius'>{{ __('settings.Europe/Vilnius') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Volgograd') value='Europe/Volgograd'>{{ __('settings.Europe/Volgograd') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Warsaw') value='Europe/Warsaw'>{{ __('settings.Europe/Warsaw') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Zagreb') value='Europe/Zagreb'>{{ __('settings.Europe/Zagreb') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Zaporozhye') value='Europe/Zaporozhye'>{{ __('settings.Europe/Zaporozhye') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Europe/Zurich') value='Europe/Zurich'>{{ __('settings.Europe/Zurich') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Antananarivo') value='Indian/Antananarivo'>{{ __('settings.Indian/Antananarivo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Chagos') value='Indian/Chagos'>{{ __('settings.Indian/Chagos') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Christmas') value='Indian/Christmas'>{{ __('settings.Indian/Christmas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Cocos') value='Indian/Cocos'>{{ __('settings.Indian/Cocos') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Comoro') value='Indian/Comoro'>{{ __('settings.Indian/Comoro') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Kerguelen') value='Indian/Kerguelen'>{{ __('settings.Indian/Kerguelen') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Mahe') value='Indian/Mahe'>{{ __('settings.Indian/Mahe') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Maldives') value='Indian/Maldives'>{{ __('settings.Indian/Maldives') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Mauritius') value='Indian/Mauritius'>{{ __('settings.Indian/Mauritius') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Mayotte') value='Indian/Mayotte'>{{ __('settings.Indian/Mayotte') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Indian/Reunion') value='Indian/Reunion'>{{ __('settings.Indian/Reunion') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Apia') value='Pacific/Apia'>{{ __('settings.Pacific/Apia') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Auckland') value='Pacific/Auckland'>{{ __('settings.Pacific/Auckland') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Bougainville') value='Pacific/Bougainville'>{{ __('settings.Pacific/Bougainville') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Chatham') value='Pacific/Chatham'>{{ __('settings.Pacific/Chatham') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Chuuk') value='Pacific/Chuuk'>{{ __('settings.Pacific/Chuuk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Easter') value='Pacific/Easter'>{{ __('settings.Pacific/Easter') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Efate') value='Pacific/Efate'>{{ __('settings.Pacific/Efate') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Enderbury') value='Pacific/Enderbury'>{{ __('settings.Pacific/Enderbury') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Fakaofo') value='Pacific/Fakaofo'>{{ __('settings.Pacific/Fakaofo') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Fiji') value='Pacific/Fiji'>{{ __('settings.Pacific/Fiji') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Funafuti') value='Pacific/Funafuti'>{{ __('settings.Pacific/Funafuti') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Galapagos') value='Pacific/Galapagos'>{{ __('settings.Pacific/Galapagos') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Gambier') value='Pacific/Gambier'>{{ __('settings.Pacific/Gambier') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Guadalcanal') value='Pacific/Guadalcanal'>{{ __('settings.Pacific/Guadalcanal') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Guam') value='Pacific/Guam'>{{ __('settings.Pacific/Guam') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Honolulu') value='Pacific/Honolulu'>{{ __('settings.Pacific/Honolulu') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Kiritimati') value='Pacific/Kiritimati'>{{ __('settings.Pacific/Kiritimati') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Kosrae') value='Pacific/Kosrae'>{{ __('settings.Pacific/Kosrae') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Kwajalein') value='Pacific/Kwajalein'>{{ __('settings.Pacific/Kwajalein') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Majuro') value='Pacific/Majuro'>{{ __('settings.Pacific/Majuro') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Marquesas') value='Pacific/Marquesas'>{{ __('settings.Pacific/Marquesas') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Midway') value='Pacific/Midway'>{{ __('settings.Pacific/Midway') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Nauru') value='Pacific/Nauru'>{{ __('settings.Pacific/Nauru') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Niue') value='Pacific/Niue'>{{ __('settings.Pacific/Niue') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Norfolk') value='Pacific/Norfolk'>{{ __('settings.Pacific/Norfolk') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Noumea') value='Pacific/Noumea'>{{ __('settings.Pacific/Noumea') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Pago_Pago') value='Pacific/Pago_Pago'>{{ __('settings.Pacific/Pago_Pago') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Palau') value='Pacific/Palau'>{{ __('settings.Pacific/Palau') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Pitcairn') value='Pacific/Pitcairn'>{{ __('settings.Pacific/Pitcairn') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Pohnpei') value='Pacific/Pohnpei'>{{ __('settings.Pacific/Pohnpei') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Port_Moresby') value='Pacific/Port_Moresby'>{{ __('settings.Pacific/Port_Moresby') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Rarotonga') value='Pacific/Rarotonga'>{{ __('settings.Pacific/Rarotonga') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Saipan') value='Pacific/Saipan'>{{ __('settings.Pacific/Saipan') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Tahiti') value='Pacific/Tahiti'>{{ __('settings.Pacific/Tahiti') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Tarawa') value='Pacific/Tarawa'>{{ __('settings.Pacific/Tarawa') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Tongatapu') value='Pacific/Tongatapu'>{{ __('settings.Pacific/Tongatapu') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Wake') value='Pacific/Wake'>{{ __('settings.Pacific/Wake') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'Pacific/Wallis') value='Pacific/Wallis'>{{ __('settings.Pacific/Wallis') }}</option>
                                                                        <option @selected(env('TIMEZONE') == 'UTC') value='UTC'>{{ __('settings.UTC') }}</option>
                                                                    </select>
                                                                <span></span>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-lg-12">
                                                                    <div class="button-group text-center mt-4">
                                                                        <button class="theme-btn m-2 submit-btn">{{ __('common.Update') }}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
