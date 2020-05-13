<style>

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

<div class="form-group row ">
  {!! Form::label('title', trans("lang.offer_title"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::text('title', null,  ['class' => 'form-control','placeholder'=>  trans("lang.offer_title_help")]) !!}
    <div class="form-text text-muted">
      {{ trans("lang.offer_title_help") }}
    </div>
  </div>
</div>
<!-- Description Field -->
<div class="form-group row ">
  {!! Form::label('description', trans("lang.offer_description"), ['class' => 'col-3 control-label text-right']) !!}
  <div class="col-9">
    {!! Form::textarea('description', null, ['class' => 'form-control','placeholder'=>
     trans("lang.offer_description_help")  ]) !!}
    <div class="form-text text-muted">{{ trans("lang.offer_description_help") }}</div>
  </div>
</div>
</div>



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

<input type="hidden" name="banners_id" id="banners_id" value="{{ !empty($offer->redirect_url) ? $offer->redirect_url : ''}}">

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
 
    <div class="container form-group row">
        {!! Form::label('is_active', trans("lang.offer_active"),['class' => 'col-4 switch control-label text-right']) !!}
        <div class="col-8">
            <label class="switch" for="checkbox">
            <?php if (isset($offer) && $offer->is_active == '1'){ ?>
              <input type="checkbox" checked="checked" id="checkbox">
            <?php } else{ ?>
              <input type="checkbox"  id="checkbox">
            <?php } ?>

                <div class="slider round"></div>
            </label>
            
            <div class="form-text text-muted">
                   {{ trans("lang.offer_active") }}
            </div>
        </div>
        <input type="hidden" value="{!! setting('offer_active')  !!}" name="is_active" id="is_active">
    </div>                
</div>

@prepend('scripts')
<script type="text/javascript">

$(document).ready(function(){

    var check;
     $("#is_active").val("0");
    $('#checkbox').change(function () {
    var ckbox = $('#checkbox');
        if(ckbox.is(':checked'))
        {
          $("#is_active").val("1");  
        }
        else{
          $("#is_active").val("0");
        }  
    }); 

     $("#types").on("change",function(){

        $("#restaurant").show();

        var id = $(".select2 option:selected").val();
        var banners_id = $("#banners_id").val();
        
        $("#type_banners").val('');        
        $("#type_banners").val($("#type_selected.select2 option:selected").text());                
        $("label[for='restaurant_id']").text($("#type_selected.select2 option:selected").text());
        
        $.ajax({
            url: "{{ url('offer/getType') }}",
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
</div>
<!-- Submit Field -->
<div class="form-group col-12 text-right">
  <button type="submit" class="btn btn-{{setting('theme_color')}}" ><i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.offer')}}</button>
  <a href="{!! route('offer.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>
