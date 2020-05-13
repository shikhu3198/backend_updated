@if($customFields)
<h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
  <!-- Restaurant Id Field -->
<div class="form-group row " id="types">
  {!! Form::label('type_id', trans("lang.types"),['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::select('type_id', $type, null, ['class' => 'select2 form-control', 'id' => 'type_selected']) !!}
    <div class="form-text text-muted">{{ trans("lang.types_help") }}</div>
  </div>
  <input type="hidden" id="type_banners" name="type" value="">
</div>

<input type="hidden" name="banners_id" id="banners_id" value="{{ !empty($banners->redirect_url) ? $banners->redirect_url : ''}}">

        <!-- Restaurant Id Field -->
    <div class="form-group row " id="restaurant" style="display: none;">
        {!! Form::label('restaurant_id','Restaurant',['class' => 'col-3 control-label text-right data','id' => 'multi_data']) !!}
        <div class="col-9" id="hideDiv">
            <select id="multiple_data" name="redirect_url" class="select2 form-control">
                <option value="0">------------</option>
            </select>   

            <div class="form-text text-muted">{{ trans("lang.food_restaurant_id_help") }}</div>
        </div>
        <div id="responseDiv" style="display: none;">
            <input type="text" name="redirect_url" id="redirect_url" value="" placeholder= '{{ trans("lang.redirect_url") }}'>
            <div class="form-text text-muted">{{ trans("lang.redirect_url_help") }}</div>
        </div>
    </div>

</div>

@prepend('scripts')
<script type="text/javascript">

$(document).ready(function(){

     $("#types").on("change",function(){

        $("#restaurant").show();

        var id = $(".select2 option:selected").val();
        var banners_id = $("#banners_id").val();
        
        $("#type_banners").val('');        
        $("#type_banners").val($("#type_selected.select2 option:selected").text());                
        $("label[for='restaurant_id']").text($("#type_selected.select2 option:selected").text());
        
        $.ajax({
            url: "{{ url('banners/getType') }}",
            method: "GET",
            data: {"id": id},
            success: function(msg) {
                
                if(id == 1 || id == 2 || id == 3) 
                {
                    $("#hideDiv").show();
                    $("#responseDiv").hide();

                    var response = JSON.parse(msg);
                    $("#redirect_url").removeAttr('name');
                    $("#multiple_data").empty();
                    $.each(response, function(val, text) {  
                        
                        $("#multiple_data").append(
                            $('<option value='+val+'>'+text+'</option>')
                        );
                        
                    });   
                    if(banners_id == ''){
                        $("#multiple_data").select2("text",'--------');
                    }
                    else{
                        $("#multiple_data").select2("val",banners_id);  
                    } 
                }
                if(id == 4) 
                {
                    $("#redirect_url").attr('name','redirect_url');  
                    $("#responseDiv").show();
                    if($.isNumeric(banners_id) == true)
                    {
                        $("#redirect_url").val('');    
                    }
                    else
                    {
                        $("#redirect_url").val(banners_id);
                    }
                    $("#hideDiv").hide();
                }
            }
        });
    });
    
    $('#types').trigger('change');
});

</script>
@endprepend

<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
<!-- Image Field -->
<div class="form-group row">
  {!! Form::label('image', trans("lang.food_image"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    <div style="width: 100%" class="dropzone image" id="image" data-field="image">
      <input type="hidden" name="image">
    </div>
    <a href="#loadMediaModal" data-dropzone="image" data-toggle="modal" data-target="#mediaModal" class="btn btn-outline-{{setting('theme_color','primary')}} btn-sm float-right mt-1">{{ trans('lang.media_select')}}</a>
    <div class="form-text text-muted w-50">
      {{ trans("lang.food_image_help") }}
    </div>
  </div>
</div>
@prepend('scripts')
<script type="text/javascript">
    var var15671147171873255749ble = '';
    @if(isset($banners) && $banners->hasMedia('image'))
    var15671147171873255749ble = {
        name: "{!! $banners->getFirstMedia('image')->name !!}",
        size: "{!! $banners->getFirstMedia('image')->size !!}",
        type: "{!! $banners->getFirstMedia('image')->mime_type !!}",
        collection_name: "{!! $banners->getFirstMedia('image')->collection_name !!}"};
    @endif
    var dz_var15671147171873255749ble = $(".dropzone.image").dropzone({
        url: "{!!url('uploads/store')!!}",
        addRemoveLinks: true,
        maxFiles: 1,
        init: function () {
        @if(isset($banners) && $banners->hasMedia('image'))
            dzInit(this,var15671147171873255749ble,'{!! url($banners->getFirstMediaUrl('image','thumb')) !!}')
        @endif
        },
        accept: function(file, done) {
            dzAccept(file,done,this.element,"{!!config('medialibrary.icons_folder')!!}");
        },
        sending: function (file, xhr, formData) {
            dzSending(this,file,formData,'{!! csrf_token() !!}');
        },
        maxfilesexceeded: function (file) {
            dz_var15671147171873255749ble[0].mockFile = '';
            dzMaxfile(this,file);
        },
        complete: function (file) {
            dzComplete(this, file, var15671147171873255749ble, dz_var15671147171873255749ble[0].mockFile);
            dz_var15671147171873255749ble[0].mockFile = file;
        },
        removedfile: function (file) {
            dzRemoveFile(
                file, var15671147171873255749ble, '{!! url("foods/remove-media") !!}',
                'image', '{!! isset($banners) ? $banners->id : 0 !!}', '{!! url("uplaods/clear") !!}', '{!! csrf_token() !!}'
            );
        }
    });
    dz_var15671147171873255749ble[0].mockFile = var15671147171873255749ble;
    dropzoneFields['image'] = dz_var15671147171873255749ble;
</script>
@endprepend

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
  <button type="submit" class="btn btn-{{setting('theme_color')}}" ><i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.banners')}}</button>
  <a href="{!! route('banners.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>
