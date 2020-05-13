<style>
label.switch {
    margin: 1px 0px;
}
.switch {
  display: inline-block;
  height: 34px;
  position: relative;
  width: 60px;
}

.switch input {
  display:none;
}

.slider {
  background-color: #ccc;
  bottom: 0;
  cursor: pointer;
  left: 0;
  position: absolute;
  right: 0;
  top: 0;
  transition: .4s;
}

.slider:before {
  background-color: #fff;
  bottom: 4px;
  content: "";
  height: 26px;
  left: 4px;
  position: absolute;
  transition: .4s;
  width: 26px;
}

input:checked + .slider {
  background-color: #66bb6a;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
</style>
@if($customFields)
<h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
<!-- Name Field -->
<div class="form-group row ">
  {!! Form::label('name', trans("lang.restaurant_name"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('name', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_name_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_name_help") }}
    </div>
  </div>
</div>

<!-- Description Field -->
<div class="form-group row ">
  {!! Form::label('description', trans("lang.restaurant_description"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::textarea('description', null, ['class' => 'form-control','placeholder'=>
     trans("lang.restaurant_description_placeholder")  ]) !!}
    <div class="form-text text-muted">{{ trans("lang.restaurant_description_help") }}</div>
  </div>
</div>

<!-- Image Field -->
<div class="form-group row">
  {!! Form::label('image', trans("lang.restaurant_image"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    <div style="width: 100%" class="dropzone image" id="image" data-field="image">
      <input type="hidden" name="image">
    </div>
    <a href="#loadMediaModal" data-dropzone="image" data-toggle="modal" data-target="#mediaModal" class="btn btn-outline-{{setting('theme_color','primary')}} btn-sm float-right mt-1">{{ trans('lang.media_select')}}</a>
    <div class="form-text text-muted w-50">
      {{ trans("lang.restaurant_image_help") }}
    </div>
  </div>
</div>
@prepend('scripts')
<script type="text/javascript">
    var var15671147011688676454ble = '';
    @if(isset($restaurant) && $restaurant->hasMedia('image'))
    var15671147011688676454ble = {
        name: "{!! $restaurant->getFirstMedia('image')->name !!}",
        size: "{!! $restaurant->getFirstMedia('image')->size !!}",
        type: "{!! $restaurant->getFirstMedia('image')->mime_type !!}",
        collection_name: "{!! $restaurant->getFirstMedia('image')->collection_name !!}"};
    @endif
    var dz_var15671147011688676454ble = $(".dropzone.image").dropzone({
        url: "{!!url('uploads/store')!!}",
        addRemoveLinks: true,
        maxFiles: 1,
        init: function () {
        @if(isset($restaurant) && $restaurant->hasMedia('image'))
            dzInit(this,var15671147011688676454ble,'{!! url($restaurant->getFirstMediaUrl('image','thumb')) !!}')
        @endif
        },
        accept: function(file, done) {
            dzAccept(file,done,this.element,"{!!config('medialibrary.icons_folder')!!}");
        },
        sending: function (file, xhr, formData) {
            dzSending(this,file,formData,'{!! csrf_token() !!}');
        },
        maxfilesexceeded: function (file) {
            dz_var15671147011688676454ble[0].mockFile = '';
            dzMaxfile(this,file);
        },
        complete: function (file) {
            dzComplete(this, file, var15671147011688676454ble, dz_var15671147011688676454ble[0].mockFile);
            dz_var15671147011688676454ble[0].mockFile = file;
        },
        removedfile: function (file) {
            dzRemoveFile(
                file, var15671147011688676454ble, '{!! url("restaurants/remove-media") !!}',
                'image', '{!! isset($restaurant) ? $restaurant->id : 0 !!}', '{!! url("uplaods/clear") !!}', '{!! csrf_token() !!}'
            );
        }
    });
    dz_var15671147011688676454ble[0].mockFile = var15671147011688676454ble;
    dropzoneFields['image'] = dz_var15671147011688676454ble;
</script>
@endprepend

@hasrole('admin')
<!-- Users Field -->
<div class="form-group row ">
    {!! Form::label('users[]', trans("lang.restaurant_users"),['class' => 'col-3 control-label text-right']) !!}
    <div class="col-9">
        {!! Form::select('users[]', $user, $usersSelected, ['class' => 'select2 form-control' , 'multiple'=>'multiple']) !!}
        <div class="form-text text-muted">{{ trans("lang.restaurant_users_help") }}</div>
    </div>
</div>
@endhasrole

@hasanyrole('admin|manager')
<!-- Users Field -->
    <div class="form-group row ">
        {!! Form::label('drivers[]', trans("lang.restaurant_drivers"),['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::select('drivers[]', $drivers, $driversSelected, ['class' => 'select2 form-control' , 'multiple'=>'multiple']) !!}
            <div class="form-text text-muted">{{ trans("lang.restaurant_drivers_help") }}</div>
        </div>
    </div>
@endhasrole

@hasanyrole('admin|manager')
<!-- delivery_fee Field -->
    <div class="form-group row ">
        {!! Form::label('delivery_fee', trans("lang.restaurant_delivery_fee"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::number('delivery_fee', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_delivery_fee_placeholder")]) !!}
            <div class="form-text text-muted">
                {{ trans("lang.restaurant_delivery_fee_help") }}
            </div>
        </div>
    </div>
@endhasrole

<!-- min_order_price Field -->
    <div class="form-group row ">
        {!! Form::label('min_order_price', trans("lang.restaurant_min_order_price"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::number('min_order_price', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_min_order_price_placeholder")]) !!}
            <div class="form-text text-muted">
                {{ trans("lang.restaurant_min_order_price_help") }}
            </div>
        </div>
    </div>


</div>
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">

<!-- Longitude Field -->
<div class="form-group row ">
  {!! Form::label('longitude', trans("lang.restaurant_longitude"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('longitude', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_longitude_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_longitude_help") }}
    </div>
  </div>
</div>

<!-- Latitude Field -->
<div class="form-group row ">
  {!! Form::label('latitude', trans("lang.restaurant_latitude"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('latitude', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_latitude_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_latitude_help") }}
    </div>
  </div>
</div>


<!-- Phone Field -->
<div class="form-group row ">
  {!! Form::label('phone', trans("lang.restaurant_phone"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('phone', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_phone_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_phone_help") }}
    </div>
  </div>
</div>

<!-- Mobile Field -->
<div class="form-group row ">
  {!! Form::label('mobile', trans("lang.restaurant_mobile"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('mobile', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_mobile_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_mobile_help") }}
    </div>
  </div>
</div>

<!-- Information Field -->
<div class="form-group row ">
  {!! Form::label('information', trans("lang.restaurant_information"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::textarea('information', null, ['class' => 'form-control','placeholder'=>
     trans("lang.restaurant_information_placeholder")  ]) !!}
    <div class="form-text text-muted">{{ trans("lang.restaurant_information_help") }}</div>
  </div>
</div>

<div class="form-group row ">
  {!! Form::label('res_category_id', trans("lang.food_category_id"),['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::select('res_category_id', $res_category, null, ['class' => 'select2 form-control']) !!}
    <div class="form-text text-muted">{{ trans("lang.food_category_id_help") }}</div>
  </div>
</div>

<!-- Address Field -->
<div class="form-group row ">
  {!! Form::label('address', trans("lang.restaurant_address"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('address', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_address_placeholder")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.restaurant_address_help") }}
    </div>
  </div>
</div>

@hasanyrole('admin|manager')
<!-- admin_commission Field -->
    <div class="form-group row ">
        {!! Form::label('admin_commission', trans("lang.restaurant_admin_commission"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::number('admin_commission', null,  ['class' => 'form-control','placeholder'=>  trans("lang.restaurant_admin_commission_placeholder")]) !!}
            <div class="form-text text-muted">
                {{ trans("lang.restaurant_admin_commission_help") }}
            </div>
        </div>
    </div>
@endhasrole
</div>

<div style="flex: 100%;max-width: 100%;padding: 0 4px;" class="column">

<div class="form-group row ">
  
  <div class="col-1"></div>
  <div class="col-2">
    {!! Form::label('week', trans("lang.restaurant_week"),['class' => 'control-label text-right']) !!}
    @foreach($week as $w)
      {!! Form::text('week[]', $w,  ['class' => 'form-control','readonly','placeholder'=>  trans("lang.restaurant_mobile_placeholder")]) !!}
      <div class="form-text text-muted"></div>
    @endforeach
  </div>
&nbsp;&nbsp;&nbsp;&nbsp;

  <div class="col-1.5">
    {!! Form::label('start_time', trans("lang.restaurant_starttime"),['class' => 'control-label text-right']) !!}
    @for ($x = 0; $x < 7; $x++)

      {!! Form::select('start_time[]', $hours, $startTimeSelected[$x] ?? null, ['class' => 'form-control']) !!}
      <div class="form-text text-muted"></div>
    @endfor
  </div>
&nbsp;&nbsp;&nbsp;&nbsp;
  <div class="col-1.5">
    {!! Form::label('start_minutes', trans("lang.restaurant_startminutes"),['class' => 'control-label text-right']) !!}
    @for ($x = 0; $x < 7; $x++)
      {!! Form::select('start_minutes[]', $minutes, $startMinutesSelected[$x] ?? null, ['class' => 'form-control']) !!}
      <div class="form-text text-muted"></div>
    @endfor
  </div>
&nbsp;&nbsp;&nbsp;&nbsp;
  <div class="col-1.5">
    {!! Form::label('end_time', trans("lang.restaurant_endtime"),['class' => 'control-label text-right']) !!}
    @for ($x = 0; $x < 7; $x++)
      {!! Form::select('end_time[]', $hours, $endTimeSelected[$x] ?? null, ['class' => 'form-control']) !!}
      <div class="form-text text-muted"></div>
    @endfor
  </div>
&nbsp;&nbsp;&nbsp;&nbsp;
  <div class="col-1.5">
    {!! Form::label('end_minutes', trans("lang.restaurant_endminutes"),['class' => 'control-label text-right']) !!}
    @for ($x = 0; $x < 7; $x++)
      {!! Form::select('end_minutes[]', $minutes, $endMinutesSelected[$x] ?? null, ['class' => 'form-control']) !!}
      <div class="form-text text-muted"></div>
    @endfor
  </div>
&nbsp;&nbsp;&nbsp;&nbsp;
    <div class="col-1">
      {!! Form::label('is_open', trans("lang.restaurant_open_close"),['class' => 'control-label text-right']) !!}
      @for ($x = 0; $x < 7; $x++)
        
        <label class="switch">
          @if((count($isOpenSelected) > 0) && $isOpenSelected[$x] == 1)
            <input type="checkbox" data-id="{{$x}}" checked="checked">
          @else
            <input type="checkbox" data-id="{{$x}}">
          @endif
          <span class="slider round"></span>
        </label>

        <input type="hidden" value="{{ $isOpenSelected[$x] ?? '0' }}" name="is_open[]" id="is_open" class="is_open_{{$x}}">
      @endfor
    </div>
             
</div>
</div>


@if($customFields)
<div class="clearfix"></div>
<div class="col-12 custom-field-container">
  <h5 class="col-12 pb-4">{!! trans('lang.custom_field_plural') !!}</h5>
  {!! $customFields !!}
</div>
@endif
<!-- Submit Field -->
<div class="form-group col-12 text-right">
  <button type="submit" class="btn btn-{{setting('theme_color')}}" ><i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.restaurant')}}</button>
  <a href="{!! route('restaurants.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>

@prepend('scripts')
<script type="text/javascript">

$(document).ready(function(){

    $(".is_open").val("0");
    
    $('input[type=checkbox]').on("change",function () {
        
        var id = $(this).attr('data-id')
       if($(this).prop('checked') == true)
        {
          $(".is_open_"+id).val("1");
        }
        else{
          $(".is_open_"+id).val("0");
        }  
        
    }); 

});
</script>
@endprepend