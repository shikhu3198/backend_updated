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

<div class="container form-group row">
       
        <div class="col-8">

          <label class="switch">
            
            <?php if ($is_active == '1'){ ?>
              <input type="checkbox" checked data-id="{{$id}}">
            <?php } else{ ?>
              <input type="checkbox" data-id="{{$id}}">
            <?php } ?>
            
                <span class="slider round"></span>
            </label>
           
        </div>

        <input type="hidden" value="{!! setting('offer_active')  !!}" name="is_active" class="is_active">
        
    </div>                

@prepend('scripts')
<script type="text/javascript">

$(document).ready(function(){

    $(".is_active").val("0");
    
    $('input[type=checkbox]').on("change",function () {
        alert(1);
        var user_id = $(this).attr('data-id');
        
       if($(this).prop('checked') == true)
        {
          $(".is_active").val("1");
        }
        else{
          $(".is_active").val("0");
        }  
        var active = $(".is_active").val();
          
        $.ajax({
          type: "GET",
          url: "{{ url('settings/drivers/checkActiveOrNot') }}/"+user_id,
          data:{'active':active,'user_id':user_id},
          success:function(res)
          {
            console.log(res);
          }
        });
        
    }); 

});
</script>
@endprepend